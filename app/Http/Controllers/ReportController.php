<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Ledger;
use App\Models\LedgerEntry;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\Sale;
use App\Services\AuditLogService;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService,
        protected AuditLogService $auditLogService
    )
    {
    }

    public function index()
    {
        $this->accountingService->ensureDefaultLedgers();
        $ledgers = Ledger::query()->orderBy('type')->orderBy('name')->get();
        $defaultFilters = $this->resolveRange('monthly', null, null);

        return view('admin.reports.index', [
            'ledgers' => $ledgers,
            'defaultFilters' => [
                'report_type' => 'sales',
                'period' => 'monthly',
                'start_date' => $defaultFilters['start']->toDateString(),
                'end_date' => $defaultFilters['end']->toDateString(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->buildReportPayload($request));
    }

    public function exportExcel(Request $request)
    {
        $payload = $this->buildReportPayload($request);
        $fileName = ($payload['filters']['report_type'] ?? 'report') . '-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new ReportExport($payload['report'], $payload['filters']), $fileName);
    }

    public function accountingSnapshot(): JsonResponse
    {
        $this->accountingService->ensureDefaultLedgers();

        return response()->json($this->buildAccountingSnapshot());
    }

    public function storeLedger(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:ledgers,code',
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])],
            'description' => 'nullable|string|max:1000',
        ]);

        $ledger = Ledger::query()->create([
            'name' => $request->name,
            'code' => $request->code,
            'type' => $request->type,
            'description' => $request->description,
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->auditLogService->log(
            'accounting',
            'ledger_created',
            'Ledger "' . $ledger->name . '" created.',
            $ledger,
            [],
            $ledger->only(['name', 'code', 'type', 'description', 'is_system', 'is_active'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Ledger created successfully.',
            'ledger' => $ledger,
        ]);
    }

    public function storeTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_type' => ['required', Rule::in(['income', 'expense'])],
            'ledger_id' => 'required|exists:ledgers,id',
            'amount' => 'required|numeric|min:0.01',
            'entry_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $ledger = Ledger::query()->findOrFail($request->ledger_id);

        if ($request->transaction_type === 'income' && $ledger->type !== 'income') {
            return response()->json([
                'success' => false,
                'message' => 'Income entries must be posted to an income ledger.',
            ], 422);
        }

        if ($request->transaction_type === 'expense' && $ledger->type !== 'expense') {
            return response()->json([
                'success' => false,
                'message' => 'Expense entries must be posted to an expense ledger.',
            ], 422);
        }

        DB::transaction(function () use ($request) {
            $this->accountingService->recordManualTransaction([
                'transaction_type' => $request->transaction_type,
                'ledger_id' => $request->ledger_id,
                'amount' => $request->amount,
                'entry_date' => Carbon::parse($request->entry_date),
                'reference' => $request->reference,
                'description' => $request->description,
            ]);
        });

        $this->auditLogService->log(
            'accounting',
            'manual_transaction_created',
            ucfirst($request->transaction_type) . ' transaction recorded.',
            null,
            [],
            [
                'transaction_type' => $request->transaction_type,
                'ledger_id' => (int) $request->ledger_id,
                'amount' => (float) $request->amount,
                'entry_date' => $request->entry_date,
                'reference' => $request->reference,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => ucfirst($request->transaction_type) . ' entry recorded successfully.',
        ]);
    }

    protected function resolveRange(string $period, ?string $startDate, ?string $endDate): array
    {
        if ($period === 'custom' && $startDate && $endDate) {
            return [
                'start' => Carbon::parse($startDate)->startOfDay(),
                'end' => Carbon::parse($endDate)->endOfDay(),
            ];
        }

        $now = now();

        return match ($period) {
            'daily' => ['start' => $now->copy()->startOfDay(), 'end' => $now->copy()->endOfDay()],
            'weekly' => ['start' => $now->copy()->startOfWeek(), 'end' => $now->copy()->endOfWeek()],
            'monthly' => ['start' => $now->copy()->startOfMonth(), 'end' => $now->copy()->endOfMonth()],
            default => [
                'start' => Carbon::parse($startDate ?: $now->copy()->startOfMonth())->startOfDay(),
                'end' => Carbon::parse($endDate ?: $now->copy()->endOfMonth())->endOfDay(),
            ],
        };
    }

    protected function buildSalesReport(Carbon $start, Carbon $end, string $period): array
    {
        $sales = Sale::query()
            ->with('items')
            ->whereBetween('sold_at', [$start, $end])
            ->orderBy('sold_at')
            ->get();

        $groupedSales = $sales->groupBy(fn (Sale $sale) => $this->groupLabel($sale->sold_at, $period));
        $topProducts = $sales
            ->flatMap(fn (Sale $sale) => $sale->items)
            ->groupBy('product_name')
            ->map(function (Collection $items, string $productName) {
                return [
                    'name' => $productName,
                    'quantity' => (int) $items->sum('quantity'),
                    'sales' => round((float) $items->sum('line_total'), 2),
                ];
            })
            ->sortByDesc('sales')
            ->values()
            ->take(10)
            ->values();

        return [
            'title' => 'Sales Report',
            'type' => 'sales',
            'summary' => [
                ['label' => 'Net Sales', 'value' => round((float) $sales->sum('total'), 2)],
                ['label' => 'Invoices', 'value' => (int) $sales->count(), 'format' => 'number'],
                ['label' => 'Collected', 'value' => round((float) $sales->sum('paid_amount'), 2)],
                ['label' => 'Outstanding', 'value' => round((float) $sales->sum('due_amount'), 2)],
            ],
            'chart' => [
                'label' => 'Sales Trend',
                'labels' => $groupedSales->keys()->values(),
                'datasets' => [
                    [
                        'label' => 'Sales',
                        'data' => $groupedSales->map(fn (Collection $items) => round((float) $items->sum('total'), 2))
                            ->values(),
                    ],
                ],
            ],
            'table' => [
                'title' => 'Top Selling Products',
                'columns' => ['Product', 'Units Sold', 'Sales'],
                'rows' => $topProducts->map(fn ($item) => [$item['name'], $item['quantity'], $item['sales']])->all(),
            ],
        ];
    }

    protected function buildInventoryReport(): array
    {
        $products = Product::query()
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.low_stock_threshold',
                'products.inventory_locked',
                DB::raw('COALESCE(stocks.quantity, products.stock) as current_stock')
            )
            ->orderBy('products.name')
            ->get()
            ->map(function ($product) {
                $cost = $this->latestUnitCost((int) $product->id);

                return [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'current_stock' => (int) $product->current_stock,
                    'unit_cost' => $cost,
                    'inventory_value' => round((int) $product->current_stock * $cost, 2),
                    'threshold' => (int) $product->low_stock_threshold,
                    'locked' => (bool) $product->inventory_locked,
                ];
            });

        $lowStock = $products->where(fn ($product) => $product['current_stock'] <= $product['threshold']);

        return [
            'title' => 'Inventory Report',
            'type' => 'inventory',
            'summary' => [
                ['label' => 'Stock Units', 'value' => (int) $products->sum('current_stock'), 'format' => 'number'],
                ['label' => 'Inventory Value', 'value' => round((float) $products->sum('inventory_value'), 2)],
                ['label' => 'Low Stock Items', 'value' => (int) $lowStock->count(), 'format' => 'number'],
                ['label' => 'Locked Products', 'value' => (int) $products->where('locked', true)->count(), 'format' => 'number'],
            ],
            'chart' => [
                'label' => 'Inventory Value by Product',
                'labels' => $products->sortByDesc('inventory_value')->take(12)->pluck('name')->values(),
                'datasets' => [
                    [
                        'label' => 'Inventory Value',
                        'data' => $products->sortByDesc('inventory_value')->take(12)->pluck('inventory_value')->values(),
                    ],
                ],
            ],
            'table' => [
                'title' => 'Inventory Position',
                'columns' => ['Product', 'SKU', 'Stock', 'Unit Cost', 'Value', 'Threshold'],
                'rows' => $products->sortByDesc('inventory_value')->take(20)->map(function ($product) {
                    return [
                        $product['name'],
                        $product['sku'],
                        $product['current_stock'],
                        $product['unit_cost'],
                        $product['inventory_value'],
                        $product['threshold'],
                    ];
                })->all(),
            ],
        ];
    }

    protected function buildProfitLossReport(Carbon $start, Carbon $end): array
    {
        $incomeRows = $this->ledgerBalancesByType('income', $start, $end);
        $expenseRows = $this->ledgerBalancesByType('expense', $start, $end);

        $revenue = round((float) $incomeRows->sum('balance'), 2);
        $cogs = round((float) $expenseRows->where('code', 'cost_of_goods_sold')->sum('balance'), 2);
        $operatingExpenses = round((float) $expenseRows->where('code', '!=', 'cost_of_goods_sold')->sum('balance'), 2);
        $grossProfit = round($revenue - $cogs, 2);
        $netProfit = round($grossProfit - $operatingExpenses, 2);

        return [
            'title' => 'Profit & Loss',
            'type' => 'profit_loss',
            'summary' => [
                ['label' => 'Revenue', 'value' => $revenue],
                ['label' => 'COGS', 'value' => $cogs],
                ['label' => 'Gross Profit', 'value' => $grossProfit],
                ['label' => 'Net Profit', 'value' => $netProfit],
            ],
            'chart' => [
                'label' => 'P&L Breakdown',
                'labels' => ['Revenue', 'COGS', 'Operating Expenses', 'Net Profit'],
                'datasets' => [
                    [
                        'label' => 'Amount',
                        'data' => [$revenue, $cogs, $operatingExpenses, $netProfit],
                    ],
                ],
            ],
            'table' => [
                'title' => 'Income and Expense Ledgers',
                'columns' => ['Ledger', 'Type', 'Amount'],
                'rows' => $incomeRows->map(fn ($row) => [$row['name'], 'Income', $row['balance']])
                    ->concat($expenseRows->map(fn ($row) => [$row['name'], 'Expense', $row['balance']]))
                    ->values()
                    ->all(),
            ],
        ];
    }

    protected function buildCashFlowReport(Carbon $start, Carbon $end, string $period): array
    {
        $cashLedger = Ledger::query()->where('code', 'cash_on_hand')->first();
        $entries = $cashLedger
            ? $cashLedger->entries()->whereBetween('entry_date', [$start, $end])->orderBy('entry_date')->get()
            : collect();

        $grouped = $entries->groupBy(fn (LedgerEntry $entry) => $this->groupLabel($entry->entry_date, $period));
        $inflow = round((float) $entries->where('direction', 'debit')->sum('amount'), 2);
        $outflow = round((float) $entries->where('direction', 'credit')->sum('amount'), 2);

        return [
            'title' => 'Cash Flow',
            'type' => 'cash_flow',
            'summary' => [
                ['label' => 'Cash Inflow', 'value' => $inflow],
                ['label' => 'Cash Outflow', 'value' => $outflow],
                ['label' => 'Net Cash Flow', 'value' => round($inflow - $outflow, 2)],
                ['label' => 'Transactions', 'value' => (int) $entries->count(), 'format' => 'number'],
            ],
            'chart' => [
                'label' => 'Cash Movement',
                'labels' => $grouped->keys()->values(),
                'datasets' => [
                    [
                        'label' => 'Inflow',
                        'data' => $grouped->map(fn (Collection $items) => round((float) $items->where('direction', 'debit')->sum('amount'), 2))
                            ->values(),
                    ],
                    [
                        'label' => 'Outflow',
                        'data' => $grouped->map(fn (Collection $items) => round((float) $items->where('direction', 'credit')->sum('amount'), 2))
                            ->values(),
                    ],
                ],
            ],
            'table' => [
                'title' => 'Recent Cash Entries',
                'columns' => ['Date', 'Reference', 'Direction', 'Amount', 'Description'],
                'rows' => $entries->sortByDesc('entry_date')->take(20)->map(function (LedgerEntry $entry) {
                    return [
                        optional($entry->entry_date)->format('Y-m-d H:i'),
                        $entry->reference ?: '-',
                        ucfirst($entry->direction),
                        (float) $entry->amount,
                        $entry->description ?: '-',
                    ];
                })->values()->all(),
            ],
        ];
    }

    protected function buildCustomReport(Carbon $start, Carbon $end, string $period): array
    {
        $sales = $this->buildSalesReport($start, $end, $period);
        $inventory = $this->buildInventoryReport();
        $profitLoss = $this->buildProfitLossReport($start, $end);
        $cashFlow = $this->buildCashFlowReport($start, $end, $period);

        return [
            'title' => 'Custom Business Report',
            'type' => 'custom',
            'summary' => [
                ['label' => 'Net Sales', 'value' => $sales['summary'][0]['value']],
                ['label' => 'Inventory Value', 'value' => $inventory['summary'][1]['value']],
                ['label' => 'Net Profit', 'value' => $profitLoss['summary'][3]['value']],
                ['label' => 'Net Cash Flow', 'value' => $cashFlow['summary'][2]['value']],
            ],
            'chart' => [
                'label' => 'Business Snapshot',
                'labels' => ['Sales', 'Inventory', 'Profit', 'Cash Flow'],
                'datasets' => [
                    [
                        'label' => 'Amount',
                        'data' => [
                            $sales['summary'][0]['value'],
                            $inventory['summary'][1]['value'],
                            $profitLoss['summary'][3]['value'],
                            $cashFlow['summary'][2]['value'],
                        ],
                    ],
                ],
            ],
            'table' => [
                'title' => 'Custom Overview',
                'columns' => ['Section', 'Metric', 'Amount'],
                'rows' => [
                    ['Sales', 'Net Sales', $sales['summary'][0]['value']],
                    ['Inventory', 'Inventory Value', $inventory['summary'][1]['value']],
                    ['Profit & Loss', 'Net Profit', $profitLoss['summary'][3]['value']],
                    ['Cash Flow', 'Net Cash Flow', $cashFlow['summary'][2]['value']],
                ],
            ],
            'sections' => [
                $sales,
                $inventory,
                $profitLoss,
                $cashFlow,
            ],
        ];
    }

    protected function buildAccountingSnapshot(): array
    {
        $ledgers = Ledger::query()->where('is_active', true)->orderBy('type')->orderBy('name')->get();

        $rows = $ledgers->map(function (Ledger $ledger) {
            return [
                'id' => $ledger->id,
                'name' => $ledger->name,
                'code' => $ledger->code,
                'type' => $ledger->type,
                'balance' => $this->ledgerBalance($ledger),
            ];
        });

        return [
            'balance_sheet' => [
                'assets' => round((float) $rows->where('type', 'asset')->sum('balance'), 2),
                'liabilities' => round((float) $rows->where('type', 'liability')->sum('balance'), 2),
                'equity' => round((float) $rows->where('type', 'equity')->sum('balance'), 2),
            ],
            'profit_and_loss' => [
                'income' => round((float) $rows->where('type', 'income')->sum('balance'), 2),
                'expenses' => round((float) $rows->where('type', 'expense')->sum('balance'), 2),
            ],
            'ledgers' => $rows->values(),
        ];
    }

    protected function ledgerBalancesByType(string $type, ?Carbon $start = null, ?Carbon $end = null): Collection
    {
        return Ledger::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Ledger $ledger) use ($start, $end) {
                return [
                    'name' => $ledger->name,
                    'code' => $ledger->code,
                    'balance' => $this->ledgerBalance($ledger, $start, $end),
                ];
            })
            ->filter(fn ($row) => $row['balance'] > 0)
            ->values();
    }

    protected function ledgerBalance(Ledger $ledger, ?Carbon $start = null, ?Carbon $end = null): float
    {
        $query = $ledger->entries();

        if ($start && $end) {
            $query->whereBetween('entry_date', [$start, $end]);
        }

        $entries = $query->get();
        $debits = (float) $entries->where('direction', 'debit')->sum('amount');
        $credits = (float) $entries->where('direction', 'credit')->sum('amount');

        if (in_array($ledger->type, ['asset', 'expense'], true)) {
            return round($debits - $credits, 2);
        }

        return round($credits - $debits, 2);
    }

    protected function latestUnitCost(int $productId): float
    {
        $latest = PurchaseOrderItem::query()
            ->select('purchase_order_items.unit_cost')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->where('purchase_order_items.product_id', $productId)
            ->where('purchase_orders.status', 'received')
            ->latest('purchase_orders.received_at')
            ->first();

        return round((float) ($latest?->unit_cost ?? 0), 2);
    }

    protected function groupLabel(Carbon|string|null $value, string $period): string
    {
        $date = $value instanceof Carbon ? $value : Carbon::parse($value);

        return match ($period) {
            'daily' => $date->format('H:00'),
            'weekly' => $date->format('D'),
            'monthly' => $date->format('d M'),
            default => $date->format('d M'),
        };
    }

    protected function buildReportPayload(Request $request): array
    {
        $validated = $request->validate([
            'report_type' => ['required', Rule::in(['sales', 'inventory', 'profit_loss', 'cash_flow', 'custom'])],
            'period' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'custom'])],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $range = $this->resolveRange($validated['period'], $validated['start_date'] ?? null, $validated['end_date'] ?? null);
        $filters = [
            'report_type' => $validated['report_type'],
            'period' => $validated['period'],
            'start_date' => $range['start']->toDateString(),
            'end_date' => $range['end']->toDateString(),
        ];

        $report = match ($validated['report_type']) {
            'sales' => $this->buildSalesReport($range['start'], $range['end'], $validated['period']),
            'inventory' => $this->buildInventoryReport(),
            'profit_loss' => $this->buildProfitLossReport($range['start'], $range['end']),
            'cash_flow' => $this->buildCashFlowReport($range['start'], $range['end'], $validated['period']),
            'custom' => $this->buildCustomReport($range['start'], $range['end'], $validated['period']),
        };

        return [
            'filters' => $filters,
            'report' => $report,
        ];
    }
}

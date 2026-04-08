<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PurchaseOrderItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SalesController extends Controller
{
    public function __construct(protected AccountingService $accountingService)
    {
    }

    public function index()
    {
        $products = Product::query()
            ->with(['category', 'stockRecord'])
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        $promotions = Promotion::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('name')
            ->get();

        $sales = $this->salesQuery()->limit(20)->get();

        return view('admin.sales.index', compact('products', 'customers', 'promotions', 'sales'));
    }

    public function history(Request $request)
    {
        $query = $this->salesQuery();

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($builder) use ($search) {
                $builder->where('invoice_number', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
            });
        }

        return response()->json(
            $query->limit(20)->get()->map(fn($sale) => $this->transformSale($sale))
        );
    }

    public function storeCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'loyalty_points' => 'nullable|integer|min:0',
        ]);

        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'loyalty_points' => $request->loyalty_points ?? 0,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully.',
            'customer' => $customer,
        ]);
    }

    public function storePromotion(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:promotions,code',
            'type' => ['required', Rule::in(['fixed', 'percentage'])],
            'value' => 'required|numeric|min:0.01',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $promotion = Promotion::create([
            'name' => $request->name,
            'code' => $request->code,
            'type' => $request->type,
            'value' => $request->value,
            'minimum_order_amount' => $request->minimum_order_amount ?? 0,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Promotion created successfully.',
            'promotion' => $promotion,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'promotion_id' => 'nullable|exists:promotions,id',
            'manual_discount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'cart' => 'required|array|min:1',
            'cart.*.product_id' => 'required|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
            'payments' => 'nullable|array',
            'payments.*.method' => ['required_with:payments', Rule::in(['cash', 'card', 'mobile'])],
            'payments.*.amount' => 'required_with:payments|numeric|min:0.01',
            'payments.*.reference' => 'nullable|string|max:255',
            'payments.*.note' => 'nullable|string|max:255',
        ]);

        $sale = DB::transaction(function () use ($request) {
            $cartLines = collect($request->cart);
            $taxRate = (float) ($request->tax_rate ?? 0);
            $manualDiscount = (float) ($request->manual_discount ?? 0);
            $payments = collect($request->payments ?? [])->filter(fn($payment) => (float) ($payment['amount'] ?? 0) > 0)->values();

            $products = Product::query()
                ->with('stockRecord')
                ->whereIn('id', $cartLines->pluck('product_id')->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lineItems = [];
            $subtotal = 0;

            foreach ($cartLines as $line) {
                $product = $products->get((int) $line['product_id']);

                if (!$product || !$product->status) {
                    abort(422, 'One or more selected products are unavailable.');
                }

                $quantity = (int) $line['quantity'];
                $availableStock = optional($product->stockRecord)->quantity;
                $availableStock = is_null($availableStock) ? (int) $product->stock : (int) $availableStock;

                if ($product->inventory_locked && $availableStock < $quantity) {
                    abort(422, 'Insufficient stock for ' . $product->name . '.');
                }

                $lineSubtotal = round($quantity * (float) $product->price, 2);
                $subtotal += $lineSubtotal;

                $lineItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => (float) $product->price,
                    'unit_cost' => $this->resolveUnitCost($product->id),
                    'line_subtotal' => $lineSubtotal,
                ];
            }

            $promotion = null;
            $promotionDiscount = 0;

            if ($request->filled('promotion_id')) {
                $promotion = Promotion::lockForUpdate()->findOrFail($request->promotion_id);
                $isActive = $promotion->is_active
                    && (!$promotion->starts_at || $promotion->starts_at->lte(now()))
                    && (!$promotion->ends_at || $promotion->ends_at->gte(now()))
                    && $subtotal >= (float) $promotion->minimum_order_amount;

                if ($isActive) {
                    $promotionDiscount = $promotion->type === 'percentage'
                        ? round($subtotal * ((float) $promotion->value / 100), 2)
                        : min(round((float) $promotion->value, 2), $subtotal);
                }
            }

            $discountTotal = min(round($manualDiscount + $promotionDiscount, 2), $subtotal);
            $taxableAmount = max($subtotal - $discountTotal, 0);
            $taxTotal = round($taxableAmount * ($taxRate / 100), 2);
            $grandTotal = round($taxableAmount + $taxTotal, 2);
            $paidAmount = min(round($payments->sum(fn($payment) => (float) $payment['amount']), 2), $grandTotal);
            $dueAmount = round(max($grandTotal - $paidAmount, 0), 2);
            $status = $paidAmount <= 0 ? 'unpaid' : ($dueAmount > 0 ? 'partial' : 'paid');

            $sale = Sale::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'customer_id' => $request->customer_id,
                'promotion_id' => $promotion?->id,
                'created_by' => auth()->id(),
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => $status,
                'notes' => $request->notes,
                'sold_at' => now(),
                'meta' => [
                    'manual_discount' => round($manualDiscount, 2),
                    'promotion_discount' => round($promotionDiscount, 2),
                    'tax_rate' => $taxRate,
                    'customer_label' => $request->customer_id ? null : 'Walk-in Customer',
                ],
            ]);

            $runningDiscount = $discountTotal;

            foreach ($lineItems as $index => $lineItem) {
                $isLastLine = $index === array_key_last($lineItems);
                $allocatedDiscount = $subtotal > 0
                    ? round(($lineItem['line_subtotal'] / $subtotal) * $discountTotal, 2)
                    : 0;

                if ($isLastLine) {
                    $allocatedDiscount = round(max($runningDiscount, 0), 2);
                }

                $runningDiscount -= $allocatedDiscount;
                $lineTaxable = max($lineItem['line_subtotal'] - $allocatedDiscount, 0);
                $lineTax = round($lineTaxable * ($taxRate / 100), 2);
                $lineTotal = round($lineTaxable + $lineTax, 2);
                $lineCostTotal = round($lineItem['unit_cost'] * $lineItem['quantity'], 2);

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $lineItem['product']->id,
                    'product_name' => $lineItem['product']->name,
                    'sku' => $lineItem['product']->sku,
                    'unit_price' => $lineItem['unit_price'],
                    'unit_cost' => $lineItem['unit_cost'],
                    'quantity' => $lineItem['quantity'],
                    'line_subtotal' => $lineItem['line_subtotal'],
                    'line_cost_total' => $lineCostTotal,
                    'line_discount' => $allocatedDiscount,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineTotal,
                ]);

                $stock = Stock::query()
                    ->where('product_id', $lineItem['product']->id)
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    $stock = Stock::create([
                        'product_id' => $lineItem['product']->id,
                        'quantity' => $lineItem['product']->stock,
                    ]);
                }

                $stock->decrement('quantity', $lineItem['quantity']);
                $lineItem['product']->decrement('stock', $lineItem['quantity']);

                StockMovement::create([
                    'product_id' => $lineItem['product']->id,
                    'type' => 'out',
                    'quantity' => $lineItem['quantity'],
                    'reference' => $sale->invoice_number,
                    'note' => 'Sold through POS terminal',
                    'created_by' => auth()->id(),
                ]);
            }

            foreach ($payments as $payment) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'method' => $payment['method'],
                    'amount' => round((float) $payment['amount'], 2),
                    'reference' => $payment['reference'] ?? null,
                    'note' => $payment['note'] ?? null,
                    'paid_at' => now(),
                ]);
            }

            if ($sale->customer_id) {
                $customer = Customer::whereKey($sale->customer_id)->lockForUpdate()->first();

                if ($customer) {
                    $earnedPoints = (int) floor($grandTotal / 100);

                    $customer->update([
                        'total_spent' => round((float) $customer->total_spent + $grandTotal, 2),
                        'loyalty_points' => (int) $customer->loyalty_points + $earnedPoints,
                        'last_purchase_at' => $sale->sold_at,
                    ]);
                }
            }

            $sale->load(['customer', 'promotion', 'items', 'payments']);
            $this->accountingService->recordSale($sale);

            return $sale;
        });

        return response()->json([
            'success' => true,
            'message' => 'Sale completed successfully.',
            'sale' => $this->transformSale($sale),
            'receipt_url' => route('sales.receipt', $sale),
        ]);
    }

    public function show(Sale $sale)
    {
        $sale->load(['customer', 'promotion', 'user', 'items', 'payments']);

        return view('admin.sales.receipt', compact('sale'));
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['customer', 'promotion', 'user', 'items', 'payments']);

        return view('admin.sales.receipt', [
            'sale' => $sale,
            'printMode' => true,
        ]);
    }

    protected function salesQuery()
    {
        return Sale::query()
            ->with(['customer', 'payments'])
            ->latest('sold_at');
    }

    protected function nextInvoiceNumber(): string
    {
        return 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    protected function resolveUnitCost(int $productId): float
    {
        $latestPurchaseItem = PurchaseOrderItem::query()
            ->select('purchase_order_items.unit_cost')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->where('purchase_order_items.product_id', $productId)
            ->where('purchase_orders.status', 'received')
            ->latest('purchase_orders.received_at')
            ->first();

        return round((float) ($latestPurchaseItem?->unit_cost ?? 0), 2);
    }

    protected function transformSale(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'customer_name' => $sale->customer?->name ?? 'Walk-in Customer',
            'status' => $sale->status,
            'total' => (float) $sale->total,
            'paid_amount' => (float) $sale->paid_amount,
            'due_amount' => (float) $sale->due_amount,
            'sold_at' => optional($sale->sold_at)->format('Y-m-d H:i:s'),
            'receipt_url' => route('sales.receipt', $sale),
            'view_url' => route('sales.show', $sale),
        ];
    }
}

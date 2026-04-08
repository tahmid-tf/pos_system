<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function __construct(protected AccountingService $accountingService)
    {
    }

    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['supplier', 'items.product', 'payments'])
            ->latest()
            ->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('status', true)->orderBy('name')->get();
        $recentPayments = SupplierPayment::with(['supplier', 'purchaseOrder'])
            ->latest('paid_at')
            ->limit(20)
            ->get();

        if (request()->ajax()) {
            return response()->json(
                $purchaseOrders->map(fn ($purchaseOrder) => $this->transformPurchaseOrder($purchaseOrder))
            );
        }

        return view('admin.purchase-orders.index', compact('purchaseOrders', 'suppliers', 'products', 'recentPayments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'expected_at' => 'nullable|date',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $purchaseOrder = DB::transaction(function () use ($request) {
            $po = PurchaseOrder::create([
                'po_number' => $this->generatePoNumber(),
                'supplier_id' => $request->supplier_id,
                'status' => 'pending',
                'ordered_at' => now(),
                'expected_at' => $request->expected_at ?: null,
                'note' => $request->note,
                'created_by' => auth()->id(),
            ]);

            $totalAmount = 0;

            foreach ($request->items as $item) {
                $lineTotal = (float) $item['quantity'] * (float) $item['unit_cost'];
                $totalAmount += $lineTotal;

                $po->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                    'unit_cost' => (float) $item['unit_cost'],
                    'line_total' => $lineTotal,
                ]);
            }

            $po->update([
                'total_amount' => $totalAmount,
                'due_amount' => $totalAmount,
            ]);

            return $po;
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully.',
                'purchase_order' => $this->transformPurchaseOrder(
                    $purchaseOrder->load(['supplier', 'items.product', 'payments'])
                ),
            ]);
        }

        return back()->with('success', 'Purchase order created successfully.');
    }

    public function pay(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => ['required', Rule::in(['cash', 'bank', 'mobile', 'cheque'])],
            'reference' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:500',
            'paid_at' => 'nullable|date',
        ]);

        if ($purchaseOrder->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cancelled purchase orders cannot receive payments.',
            ], 422);
        }

        $payment = DB::transaction(function () use ($request, $purchaseOrder) {
            $purchaseOrder = PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            $remainingDue = round((float) $purchaseOrder->due_amount, 2);
            $amount = round((float) $request->amount, 2);

            if ($amount > $remainingDue) {
                abort(422, 'Payment amount cannot exceed the outstanding balance.');
            }

            $payment = SupplierPayment::create([
                'supplier_id' => $purchaseOrder->supplier_id,
                'purchase_order_id' => $purchaseOrder->id,
                'method' => $request->method,
                'amount' => $amount,
                'reference' => $request->reference,
                'note' => $request->note,
                'paid_at' => $request->paid_at ?: now(),
                'created_by' => auth()->id(),
            ]);

            $updatedPaidAmount = round((float) $purchaseOrder->paid_amount + $amount, 2);
            $purchaseOrder->update([
                'paid_amount' => $updatedPaidAmount,
                'due_amount' => round(max((float) $purchaseOrder->total_amount - $updatedPaidAmount, 0), 2),
            ]);

            $payment->load('purchaseOrder');
            $this->accountingService->recordSupplierPayment($payment);

            return $payment;
        });

        return response()->json([
            'success' => true,
            'message' => 'Supplier payment recorded successfully.',
            'payment' => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
                'paid_at' => optional($payment->paid_at)->format('Y-m-d H:i:s'),
            ],
            'purchase_order' => $this->transformPurchaseOrder(
                $purchaseOrder->fresh()->load(['supplier', 'items.product', 'payments'])
            ),
        ]);
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending') {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending purchase orders can be received.',
                ], 422);
            }

            return back()->with('error', 'Only pending purchase orders can be received.');
        }

        DB::transaction(function () use ($purchaseOrder) {
            $purchaseOrder->load('items');

            foreach ($purchaseOrder->items as $item) {
                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                $stock = Stock::where('product_id', $item->product_id)->lockForUpdate()->first();

                if (!$stock) {
                    $stock = Stock::create([
                        'product_id' => $item->product_id,
                        'quantity' => 0,
                    ]);
                    $stock->refresh();
                }

                $stock->increment('quantity', $item->quantity);
                $product->increment('stock', $item->quantity);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'reference' => $purchaseOrder->po_number,
                    'note' => 'Stock received from purchase order',
                    'created_by' => auth()->id(),
                ]);
            }

            $purchaseOrder->update([
                'status' => 'received',
                'received_at' => now(),
            ]);

            $this->accountingService->recordPurchaseReceipt($purchaseOrder->fresh(['items']));
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase order received and stock updated.',
            ]);
        }

        return back()->with('success', 'Purchase order received and stock updated.');
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending') {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending purchase orders can be cancelled.',
                ], 422);
            }

            return back()->with('error', 'Only pending purchase orders can be cancelled.');
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase order cancelled.',
            ]);
        }

        return back()->with('success', 'Purchase order cancelled.');
    }

    private function generatePoNumber(): string
    {
        return 'PO-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(4));
    }

    private function transformPurchaseOrder(PurchaseOrder $purchaseOrder): array
    {
        return [
            'id' => $purchaseOrder->id,
            'po_number' => $purchaseOrder->po_number,
            'status' => $purchaseOrder->status,
            'supplier' => $purchaseOrder->supplier ? [
                'id' => $purchaseOrder->supplier->id,
                'name' => $purchaseOrder->supplier->name,
            ] : null,
            'total_amount' => (float) $purchaseOrder->total_amount,
            'paid_amount' => (float) $purchaseOrder->paid_amount,
            'due_amount' => (float) $purchaseOrder->due_amount,
            'ordered_at' => optional($purchaseOrder->ordered_at)->format('Y-m-d H:i:s'),
            'expected_at' => optional($purchaseOrder->expected_at)->format('Y-m-d H:i:s'),
            'received_at' => optional($purchaseOrder->received_at)->format('Y-m-d H:i:s'),
            'note' => $purchaseOrder->note,
            'items' => $purchaseOrder->items->map(function ($item) {
                return [
                    'product_name' => $item->product?->name,
                    'quantity' => (int) $item->quantity,
                    'unit_cost' => (float) $item->unit_cost,
                    'line_total' => (float) $item->line_total,
                ];
            })->values(),
            'payments' => $purchaseOrder->payments->map(function ($payment) {
                return [
                    'amount' => (float) $payment->amount,
                    'method' => $payment->method,
                    'reference' => $payment->reference,
                    'paid_at' => optional($payment->paid_at)->format('Y-m-d H:i:s'),
                ];
            })->values(),
        ];
    }
}

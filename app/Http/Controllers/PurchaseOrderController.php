<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['supplier', 'items.product'])
            ->latest()
            ->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('status', true)->orderBy('name')->get();

        if (request()->ajax()) {
            return response()->json($purchaseOrders);
        }

        return view('admin.purchase-orders.index', compact('purchaseOrders', 'suppliers', 'products'));
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

            $po->update(['total_amount' => $totalAmount]);

            return $po;
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully.',
                'purchase_order_id' => $purchaseOrder->id,
            ]);
        }

        return back()->with('success', 'Purchase order created successfully.');
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
}

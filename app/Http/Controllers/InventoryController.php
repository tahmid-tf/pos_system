<?php
namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Product;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected AuditLogService $auditLogService
    ) {
    }

    public function stockLevels()
    {
        $products = Product::query()
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.stock',
                'products.low_stock_threshold',
                'products.inventory_locked',
                DB::raw('COALESCE(stocks.quantity, products.stock) as current_stock')
            )
            ->orderBy('products.name')
            ->get();

        $summary = [
            'total_products' => $products->count(),
            'total_units' => (int) $products->sum('current_stock'),
            'low_stock_items' => $products->filter(fn($p) => $p->current_stock <= $p->low_stock_threshold)->count(),
            'locked_items' => $products->where('inventory_locked', true)->count(),
        ];

        if (request()->ajax()) {
            return response()->json([
                'products' => $products,
                'summary' => $summary,
            ]);
        }

        return view('admin.inventory.stock-levels', compact('products', 'summary'));
    }

    public function movements(Request $request)
    {
        $query = StockMovement::with(['product', 'user'])->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $movements = $query->paginate(20)->withQueryString();
        $products = Product::orderBy('name')->get(['id', 'name']);

        if ($request->ajax()) {
            return response()->json([
                'data' => $movements->items(),
                'pagination' => [
                    'current_page' => $movements->currentPage(),
                    'last_page' => $movements->lastPage(),
                    'per_page' => $movements->perPage(),
                    'total' => $movements->total(),
                    'from' => $movements->firstItem(),
                    'to' => $movements->lastItem(),
                ],
            ]);
        }

        return view('admin.inventory.movements', compact('movements', 'products'));
    }

    public function alerts()
    {
        $products = Product::query()
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.low_stock_threshold',
                DB::raw('COALESCE(stocks.quantity, products.stock) as current_stock')
            )
            ->whereRaw('COALESCE(stocks.quantity, products.stock) <= products.low_stock_threshold')
            ->orderByRaw('COALESCE(stocks.quantity, products.stock) ASC')
            ->get();

        if (request()->ajax()) {
            return response()->json($products);
        }

        return view('admin.inventory.alerts', compact('products'));
    }

    public function toggleLock(Request $request, Product $product)
    {
        $oldValues = ['inventory_locked' => (bool) $product->inventory_locked];
        $product->update([
            'inventory_locked' => !$product->inventory_locked,
        ]);

        $this->auditLogService->log(
            'inventory',
            'lock_toggled',
            'Inventory lock updated for "' . $product->name . '".',
            $product,
            $oldValues,
            ['inventory_locked' => (bool) $product->inventory_locked]
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Inventory lock updated successfully.',
            ]);
        }

        return back()->with('success', 'Inventory lock updated successfully.');
    }

    public function updateThreshold(Request $request, Product $product)
    {
        $request->validate([
            'low_stock_threshold' => 'required|integer|min:0',
        ]);

        $oldValues = ['low_stock_threshold' => (int) $product->low_stock_threshold];
        $product->update([
            'low_stock_threshold' => $request->low_stock_threshold,
        ]);

        $currentStock = (int) ($product->stockRecord?->quantity ?? $product->stock);

        if ($currentStock <= (int) $product->low_stock_threshold) {
            $this->notificationService->createLowStockAlert($product, $currentStock);
        } else {
            $this->notificationService->resolveLowStockAlert($product);
        }

        $this->auditLogService->log(
            'inventory',
            'threshold_updated',
            'Low stock threshold updated for "' . $product->name . '".',
            $product,
            $oldValues,
            ['low_stock_threshold' => (int) $product->low_stock_threshold]
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Low stock threshold updated successfully.',
            ]);
        }

        return back()->with('success', 'Low stock threshold updated successfully.');
    }

    public function addStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $product = Product::whereKey($request->product_id)->lockForUpdate()->firstOrFail();
                $stock = Stock::where('product_id', $request->product_id)->lockForUpdate()->first();
                $previousQuantity = (int) ($stock?->quantity ?? $product->stock);

                if (!$stock) {
                    $stock = Stock::create([
                        'product_id' => $request->product_id,
                        'quantity' => 0,
                    ]);
                    $stock->refresh();
                }

                $stock->increment('quantity', $request->quantity);
                $product->increment('stock', $request->quantity);

                StockMovement::create([
                    'product_id' => $request->product_id,
                    'type'       => 'in',
                    'quantity'   => $request->quantity,
                    'reference'  => $request->reference,
                    'note'       => $request->note,
                    'created_by' => auth()->id(),
                ]);

                $stock->refresh();

                if ((int) $stock->quantity <= (int) $product->low_stock_threshold) {
                    $this->notificationService->createLowStockAlert($product->fresh(), (int) $stock->quantity);
                } else {
                    $this->notificationService->resolveLowStockAlert($product->fresh());
                }

                $this->auditLogService->log(
                    'inventory',
                    'stock_added',
                    'Stock added for "' . $product->name . '".',
                    $product,
                    ['stock' => $previousQuantity],
                    ['stock' => (int) $stock->quantity],
                    [
                        'quantity_added' => (int) $request->quantity,
                        'reference' => $request->reference,
                        'note' => $request->note,
                    ]
                );
            });

            return response()->json([
                'success' => true,
                'message' => 'Stock added successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function deductStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $product = Product::whereKey($request->product_id)->lockForUpdate()->firstOrFail();
                $stock = Stock::where('product_id', $request->product_id)->lockForUpdate()->first();
                $previousQuantity = (int) ($stock?->quantity ?? $product->stock);

                if (!$stock) {
                    $stock = Stock::create([
                        'product_id' => $request->product_id,
                        'quantity' => $product->stock,
                    ]);
                    $stock->refresh();
                }

                if ($product->inventory_locked && $stock->quantity < $request->quantity) {
                    throw new \RuntimeException('Insufficient stock quantity. Product is locked to prevent overselling.');
                }

                $stock->decrement('quantity', $request->quantity);
                $product->decrement('stock', $request->quantity);

                StockMovement::create([
                    'product_id' => $request->product_id,
                    'type'       => 'out',
                    'quantity'   => $request->quantity,
                    'reference'  => $request->reference,
                    'note'       => $request->note,
                    'created_by' => auth()->id(),
                ]);

                $stock->refresh();
                $product->refresh();

                if ((int) $stock->quantity <= (int) $product->low_stock_threshold) {
                    $this->notificationService->createLowStockAlert($product, (int) $stock->quantity);
                } else {
                    $this->notificationService->resolveLowStockAlert($product);
                }

                $this->auditLogService->log(
                    'inventory',
                    'stock_deducted',
                    'Stock deducted for "' . $product->name . '".',
                    $product,
                    ['stock' => $previousQuantity],
                    ['stock' => (int) $stock->quantity],
                    [
                        'quantity_deducted' => (int) $request->quantity,
                        'reference' => $request->reference,
                        'note' => $request->note,
                    ]
                );
            });

            return response()->json([
                'success' => true,
                'message' => 'Stock deducted successfully',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

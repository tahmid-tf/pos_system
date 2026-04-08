<?php
namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function addStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            // Find or create stock row
            $stock = Stock::firstOrCreate(
                ['product_id' => $request->product_id],
                ['quantity' => 0]
            );

            // Update stock
            $stock->increment('quantity', $request->quantity);
            Product::whereKey($request->product_id)->increment('stock', $request->quantity);

            // Log movement
            StockMovement::create([
                'product_id' => $request->product_id,
                'type'       => 'in',
                'quantity'   => $request->quantity,
                'reference'  => $request->reference,
                'note'       => $request->note,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock added successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

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

        DB::beginTransaction();

        try {
            $stock = Stock::firstOrCreate(
                ['product_id' => $request->product_id],
                ['quantity' => 0]
            );

            if ($stock->quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock quantity',
                ], 422);
            }

            $stock->decrement('quantity', $request->quantity);
            Product::whereKey($request->product_id)->decrement('stock', $request->quantity);

            StockMovement::create([
                'product_id' => $request->product_id,
                'type'       => 'out',
                'quantity'   => $request->quantity,
                'reference'  => $request->reference,
                'note'       => $request->note,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock deducted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

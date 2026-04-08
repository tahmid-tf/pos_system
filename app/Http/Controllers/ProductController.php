<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $products = Product::with('category')->latest()->get();

            return response()->json($products);
        }

        $categories = Category::all();
        return view('admin.products.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required',
            'sku'         => 'required|unique:products,sku',
            'category_id' => 'required',
            'price'       => 'required|numeric',
            'stock'       => 'required|integer|min:0',
        ]);

        $data = $request->all();
        $openingStock = (int) $request->stock;

        // Image Upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        DB::transaction(function () use ($data, $openingStock) {
            $product = Product::create($data);

            Stock::create([
                'product_id' => $product->id,
                'quantity' => $openingStock,
            ]);

            if ($openingStock > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $openingStock,
                    'reference' => 'OPENING-STOCK',
                    'note' => 'Opening stock during product creation',
                    'created_by' => auth()->id(),
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Product Created']);
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name'        => 'required',
            'sku'         => 'required|unique:products,sku,' . $id,
            'category_id' => 'required',
            'price'       => 'required|numeric',
            'stock'       => 'required|integer|min:0',
        ]);

        $data = $request->all();
        $newStock = (int) $request->stock;
        $currentStock = (int) $product->stock;

        // Image Update
        if ($request->hasFile('image')) {

            // delete old image
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $data['image'] = $request->file('image')->store('products', 'public');
        }

        DB::transaction(function () use ($product, $data, $newStock, $currentStock) {
            $product->update($data);

            $stock = Stock::firstOrCreate(
                ['product_id' => $product->id],
                ['quantity' => 0]
            );

            if ($newStock !== $currentStock) {
                $difference = abs($newStock - $currentStock);
                $movementType = $newStock > $currentStock ? 'in' : 'out';

                $stock->update(['quantity' => $newStock]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => $movementType,
                    'quantity' => $difference,
                    'reference' => 'PRODUCT-EDIT',
                    'note' => 'Stock adjusted from product update',
                    'created_by' => auth()->id(),
                ]);
            } else {
                $stock->update(['quantity' => $newStock]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Product Updated']);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // delete image
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json(['success' => true, 'message' => 'Product Deleted']);
    }
}

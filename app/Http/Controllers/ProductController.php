<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
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
            'stock'       => 'required|integer',
        ]);

        $data = $request->all();

        // Image Upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($data);

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
            'stock'       => 'required|integer',
        ]);

        $data = $request->all();

        // Image Update
        if ($request->hasFile('image')) {

            // delete old image
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

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

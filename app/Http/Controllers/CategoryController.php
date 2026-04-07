<?php
namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return Category::latest()->get();
        }

        return view('admin.categories.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:categories,name',
        ]);

        Category::create([
            'name' => $request->name,
        ]);

        return response()->json(['success' => true]);
    }

    public function edit($id)
    {
        return Category::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:categories,name,' . $id,
        ]);

        Category::findOrFail($id)->update([
            'name' => $request->name,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        Category::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}

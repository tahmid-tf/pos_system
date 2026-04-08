<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->withSum('purchaseOrders as total_purchased', 'total_amount')
            ->withSum('purchaseOrders as total_due', 'due_amount')
            ->withSum('payments as total_paid', 'amount')
            ->withCount('purchaseOrders')
            ->latest()
            ->get();

        if ($request->ajax()) {
            return response()->json($suppliers->map(fn ($supplier) => $this->transformSupplier($supplier)));
        }

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $supplier = Supplier::create($request->only([
            'name',
            'contact_person',
            'email',
            'phone',
            'address',
        ]));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier created successfully.',
                'supplier' => $this->transformSupplier($supplier->loadCount('purchaseOrders')),
            ]);
        }

        return back()->with('success', 'Supplier created successfully.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $supplier->update([
            'name' => $request->name,
            'contact_person' => $request->contact_person,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'is_active' => (bool) $request->is_active,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier updated successfully.',
                'supplier' => $this->transformSupplier(
                    $supplier->fresh()->loadCount('purchaseOrders')
                ),
            ]);
        }

        return back()->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        if ($supplier->purchaseOrders()->exists() || $supplier->payments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Suppliers with purchase or payment history cannot be deleted.',
            ], 422);
        }

        $supplier->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully.',
            ]);
        }

        return back()->with('success', 'Supplier deleted successfully.');
    }

    protected function transformSupplier(Supplier $supplier): array
    {
        return [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'contact_person' => $supplier->contact_person,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'address' => $supplier->address,
            'is_active' => (bool) $supplier->is_active,
            'purchase_orders_count' => (int) ($supplier->purchase_orders_count ?? 0),
            'total_purchased' => (float) ($supplier->total_purchased ?? 0),
            'total_paid' => (float) ($supplier->total_paid ?? 0),
            'total_due' => (float) ($supplier->total_due ?? 0),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::query()
            ->withCount('sales')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->status === 'active');
            })
            ->when($request->filled('loyalty'), function ($query) use ($request) {
                if ($request->loyalty === 'with_points') {
                    $query->where('loyalty_points', '>', 0);
                }

                if ($request->loyalty === 'without_points') {
                    $query->where('loyalty_points', '=', 0);
                }
            })
            ->latest()
            ->get();

        if ($request->ajax()) {
            return response()->json(
                $customers->map(fn ($customer) => $this->transformCustomer($customer))
            );
        }

        return view('admin.customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'loyalty_points' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'loyalty_points' => $validated['loyalty_points'] ?? 0,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully.',
            'customer' => $this->transformCustomer($customer->loadCount('sales')),
        ]);
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'sales' => function ($query) {
                $query->with(['items', 'payments'])->latest('sold_at')->limit(10);
            },
        ])->loadCount('sales');

        return response()->json([
            'customer' => $this->transformCustomer($customer),
            'sales' => $customer->sales->map(function ($sale) {
                return [
                    'invoice_number' => $sale->invoice_number,
                    'status' => $sale->status,
                    'total' => (float) $sale->total,
                    'paid_amount' => (float) $sale->paid_amount,
                    'due_amount' => (float) $sale->due_amount,
                    'sold_at' => optional($sale->sold_at)->format('Y-m-d H:i:s'),
                    'items_count' => $sale->items->count(),
                    'payment_methods' => $sale->payments->pluck('method')->unique()->values(),
                    'receipt_url' => route('sales.receipt', $sale),
                ];
            }),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'loyalty_points' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $customer->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'loyalty_points' => $validated['loyalty_points'] ?? $customer->loyalty_points,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully.',
            'customer' => $this->transformCustomer($customer->fresh()->loadCount('sales')),
        ]);
    }

    public function destroy(Customer $customer)
    {
        if ($customer->sales()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Customers with purchase history cannot be deleted.',
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully.',
        ]);
    }

    protected function transformCustomer(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'is_active' => (bool) $customer->is_active,
            'loyalty_points' => (int) $customer->loyalty_points,
            'total_spent' => (float) $customer->total_spent,
            'sales_count' => (int) ($customer->sales_count ?? $customer->sales()->count()),
            'last_purchase_at' => optional($customer->last_purchase_at)->format('Y-m-d H:i:s'),
            'created_at' => optional($customer->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}

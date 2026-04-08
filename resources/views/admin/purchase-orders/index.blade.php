@extends('layouts.admin')

@section('content')
    <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-10">
        <div class="container-xl px-4">
            <div class="page-header-content pt-4">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto mt-4">
                        <h1 class="page-header-title">
                            <div class="page-header-icon">
                                <i data-feather="file-text"></i>
                            </div>
                            Purchase Orders
                        </h1>
                        <div class="page-header-subtitle">
                            Create purchase orders, receive stock, and keep inventory history clean
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container-xl px-4 mt-n10">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card mb-4">
            <div class="card-header">Create Purchase Order</div>
            <div class="card-body">
                <form action="{{ route('purchaseOrders.store') }}" method="POST" id="purchaseOrderForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-control" required>
                                <option value="">Select supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expected Date</label>
                            <input type="datetime-local" name="expected_at" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Note</label>
                            <input type="text" name="note" class="form-control" placeholder="Optional note">
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Items</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addPoItemRow">+ Add Item</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="poItemsTable">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Unit Cost</th>
                                        <th width="80">Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <button class="btn btn-primary" type="submit">Create PO</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Purchase Order List</div>
            <div class="card-body table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Ordered</th>
                            <th>Items</th>
                            <th width="220">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchaseOrders as $po)
                            <tr>
                                <td>{{ $po->po_number }}</td>
                                <td>{{ $po->supplier?->name }}</td>
                                <td>
                                    @php
                                        $statusClass = match ($po->status) {
                                            'received' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-warning text-dark',
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ ucfirst($po->status) }}</span>
                                </td>
                                <td>{{ number_format($po->total_amount, 2) }}</td>
                                <td>{{ $po->ordered_at?->format('d M Y, h:i A') }}</td>
                                <td>{{ $po->items->count() }}</td>
                                <td>
                                    @if ($po->status === 'pending')
                                        <form action="{{ route('purchaseOrders.receive', $po->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" type="submit">Receive</button>
                                        </form>
                                        <form action="{{ route('purchaseOrders.cancel', $po->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Cancel</button>
                                        </form>
                                    @else
                                        <span class="text-muted">No action</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No purchase orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $purchaseOrders->links() }}
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const products = @json($products->map(function ($product) {
                return ['id' => $product->id, 'name' => $product->name];
            }));
            const tableBody = document.querySelector('#poItemsTable tbody');
            const addItemBtn = document.getElementById('addPoItemRow');

            function productOptions() {
                return products.map((product) => `<option value="${product.id}">${product.name}</option>`).join('');
            }

            function addRow() {
                const rowIndex = tableBody.children.length;
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <select name="items[${rowIndex}][product_id]" class="form-control" required>
                            <option value="">Select product</option>
                            ${productOptions()}
                        </select>
                    </td>
                    <td><input type="number" min="1" name="items[${rowIndex}][quantity]" class="form-control" required></td>
                    <td><input type="number" min="0" step="0.01" name="items[${rowIndex}][unit_cost]" class="form-control" required></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger removeRow">Remove</button></td>
                `;
                tableBody.appendChild(row);
            }

            addItemBtn.addEventListener('click', addRow);

            tableBody.addEventListener('click', function(e) {
                if (e.target.classList.contains('removeRow')) {
                    e.target.closest('tr').remove();
                }
            });

            addRow();
        });
    </script>
@endsection

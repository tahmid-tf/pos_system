@extends('layouts.admin')

@section('content')
    <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-10">
        <div class="container-xl px-4">
            <div class="page-header-content pt-4">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto mt-4">
                        <h1 class="page-header-title">
                            <div class="page-header-icon">
                                <i data-feather="archive"></i>
                            </div>
                            Inventory Stock Levels
                        </h1>
                        <div class="page-header-subtitle">
                            Monitor stock, low-stock threshold, and inventory lock state
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

        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-primary h-100">
                    <div class="card-body">
                        <div class="small text-muted">Products</div>
                        <div class="fs-4 fw-bold">{{ $summary['total_products'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-success h-100">
                    <div class="card-body">
                        <div class="small text-muted">Units In Stock</div>
                        <div class="fs-4 fw-bold">{{ $summary['total_units'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-warning h-100">
                    <div class="card-body">
                        <div class="small text-muted">Low Stock Items</div>
                        <div class="fs-4 fw-bold">{{ $summary['low_stock_items'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-info h-100">
                    <div class="card-body">
                        <div class="small text-muted">Locked Items</div>
                        <div class="fs-4 fw-bold">{{ $summary['locked_items'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Stock Overview</div>
            <div class="card-body table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Current Stock</th>
                            <th>Low Stock Threshold</th>
                            <th>Alert</th>
                            <th>Inventory Lock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->current_stock }}</td>
                                <td>
                                    <form action="{{ route('inventory.threshold', $product->id) }}" method="POST"
                                        class="d-flex gap-2">
                                        @csrf
                                        <input type="number" name="low_stock_threshold" min="0"
                                            value="{{ $product->low_stock_threshold }}" class="form-control form-control-sm"
                                            style="max-width:120px;">
                                        <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                    </form>
                                </td>
                                <td>
                                    @if ($product->current_stock <= $product->low_stock_threshold)
                                        <span class="badge bg-warning text-dark">Low</span>
                                    @else
                                        <span class="badge bg-success">Healthy</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('inventory.toggleLock', $product->id) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-sm {{ $product->inventory_locked ? 'btn-danger' : 'btn-outline-danger' }}"
                                            type="submit">
                                            {{ $product->inventory_locked ? 'Locked' : 'Unlocked' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

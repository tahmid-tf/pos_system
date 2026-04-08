@extends('layouts.admin')

@section('content')
    <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-10">
        <div class="container-xl px-4">
            <div class="page-header-content pt-4">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto mt-4">
                        <h1 class="page-header-title">
                            <div class="page-header-icon">
                                <i data-feather="repeat"></i>
                            </div>
                            Stock Movements
                        </h1>
                        <div class="page-header-subtitle">
                            Complete IN/OUT movement history for inventory tracking
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container-xl px-4 mt-n10">
        <div class="card mb-4">
            <div class="card-header">Filters</div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-control">
                            <option value="">All</option>
                            <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>IN</option>
                            <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>OUT</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-control">
                            <option value="">All</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" {{ (string) request('product_id') === (string) $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <a href="{{ route('inventory.movements') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Movement Logs</div>
            <div class="card-body table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Reference</th>
                            <th>Note</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr>
                                <td>{{ $movement->created_at?->format('d M Y, h:i A') }}</td>
                                <td>{{ $movement->product?->name }}</td>
                                <td>
                                    <span class="badge {{ $movement->type === 'in' ? 'bg-success' : 'bg-danger' }}">
                                        {{ strtoupper($movement->type) }}
                                    </span>
                                </td>
                                <td>{{ $movement->quantity }}</td>
                                <td>{{ $movement->reference ?: '-' }}</td>
                                <td>{{ $movement->note ?: '-' }}</td>
                                <td>{{ $movement->user?->name ?: 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No stock movement found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $movements->links() }}
            </div>
        </div>
    </div>
@endsection

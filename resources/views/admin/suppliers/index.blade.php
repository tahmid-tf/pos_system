@extends('layouts.admin')

@section('content')
    <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-10">
        <div class="container-xl px-4">
            <div class="page-header-content pt-4">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto mt-4">
                        <h1 class="page-header-title">
                            <div class="page-header-icon">
                                <i data-feather="truck"></i>
                            </div>
                            Suppliers
                        </h1>
                        <div class="page-header-subtitle">
                            Supplier integration for purchase and replenishment flow
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
            <div class="card-header">Add Supplier</div>
            <div class="card-body">
                <form action="{{ route('suppliers.store') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Save Supplier</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Supplier List</div>
            <div class="card-body table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th width="320">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $supplier)
                            <tr>
                                <td>{{ $supplier->name }}</td>
                                <td>{{ $supplier->contact_person ?: '-' }}</td>
                                <td>{{ $supplier->email ?: '-' }}</td>
                                <td>{{ $supplier->phone ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ $supplier->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <form action="{{ route('suppliers.update', $supplier->id) }}" method="POST"
                                        class="d-inline-flex gap-2 align-items-center">
                                        @csrf
                                        <input type="hidden" name="name" value="{{ $supplier->name }}">
                                        <input type="hidden" name="contact_person" value="{{ $supplier->contact_person }}">
                                        <input type="hidden" name="email" value="{{ $supplier->email }}">
                                        <input type="hidden" name="phone" value="{{ $supplier->phone }}">
                                        <input type="hidden" name="address" value="{{ $supplier->address }}">
                                        <input type="hidden" name="is_active" value="{{ $supplier->is_active ? 0 : 1 }}">
                                        <button class="btn btn-sm btn-outline-primary" type="submit">
                                            {{ $supplier->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit"
                                            onclick="return confirm('Delete this supplier?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No suppliers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $suppliers->links() }}
            </div>
        </div>
    </div>
@endsection

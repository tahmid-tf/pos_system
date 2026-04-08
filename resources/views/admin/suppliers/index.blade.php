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
        <div class="card mb-4">
            <div class="card-header">Add Supplier</div>
            <div class="card-body">
                <form id="supplierForm" class="row g-3">
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
            <div class="card-body" id="supplierTableWrapper">
                <table class="table table-bordered align-middle" id="supplierTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th width="220">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const supplierForm = document.getElementById('supplierForm');
            const supplierTableWrapper = document.getElementById('supplierTableWrapper');
            const csrfToken = '{{ csrf_token() }}';
            let dataTableInstance = null;
            let currentSuppliers = [];

            const routes = {
                index: '{{ route('suppliers.index') }}',
                store: '{{ route('suppliers.store') }}',
                update: '{{ url('/suppliers/update') }}',
                destroy: '{{ url('/suppliers/delete') }}',
            };

            function getTableMarkup(suppliers) {
                return `
                    <table class="table table-bordered align-middle" id="supplierTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th width="220">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${suppliers.map((supplier) => `
                                <tr>
                                    <td>${supplier.name ?? ''}</td>
                                    <td>${supplier.contact_person ?? '-'}</td>
                                    <td>${supplier.email ?? '-'}</td>
                                    <td>${supplier.phone ?? '-'}</td>
                                    <td>
                                        <span class="badge ${supplier.is_active ? 'bg-success' : 'bg-secondary'}">
                                            ${supplier.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-datatable btn-icon btn-transparent-dark me-2 toggleSupplierBtn"
                                            data-id="${supplier.id}"
                                            type="button"
                                            title="${supplier.is_active ? 'Deactivate' : 'Activate'}">
                                            <i data-feather="${supplier.is_active ? 'toggle-right' : 'toggle-left'}"></i>
                                        </button>
                                        <button class="btn btn-datatable btn-icon btn-transparent-dark deleteSupplierBtn"
                                            data-id="${supplier.id}"
                                            type="button"
                                            title="Delete">
                                            <i data-feather="trash-2"></i>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }

            function renderTable(suppliers) {
                if (dataTableInstance) {
                    dataTableInstance.destroy();
                    dataTableInstance = null;
                }

                currentSuppliers = suppliers;
                supplierTableWrapper.innerHTML = getTableMarkup(suppliers);
                dataTableInstance = new simpleDatatables.DataTable(document.getElementById('supplierTable'));
                feather.replace();
            }

            async function loadSuppliers() {
                const response = await fetch(routes.index, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load suppliers');
                }

                const suppliers = await response.json();
                renderTable(suppliers);
            }

            supplierForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                try {
                    const response = await fetch(routes.store, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: new FormData(supplierForm)
                    });

                    if (!response.ok) {
                        throw new Error('Failed to save supplier');
                    }

                    const data = await response.json();
                    supplierForm.reset();
                    await loadSuppliers();
                    Swal.fire('Success', data.message, 'success');
                } catch (error) {
                    Swal.fire('Error', 'Failed to save supplier', 'error');
                }
            });

            supplierTableWrapper.addEventListener('click', async function(e) {
                const toggleButton = e.target.closest('.toggleSupplierBtn');
                const deleteButton = e.target.closest('.deleteSupplierBtn');

                if (toggleButton) {
                    const supplier = currentSuppliers.find((item) => String(item.id) === String(toggleButton.dataset.id));

                    if (!supplier) {
                        Swal.fire('Error', 'Supplier data not found', 'error');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('name', supplier.name ?? '');
                    formData.append('contact_person', supplier.contact_person ?? '');
                    formData.append('email', supplier.email ?? '');
                    formData.append('phone', supplier.phone ?? '');
                    formData.append('address', supplier.address ?? '');
                    formData.append('is_active', supplier.is_active ? '0' : '1');

                    try {
                        const response = await fetch(`${routes.update}/${toggleButton.dataset.id}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        if (!response.ok) {
                            throw new Error('Failed to update supplier');
                        }

                        const data = await response.json();
                        await loadSuppliers();
                        Swal.fire('Success', data.message, 'success');
                    } catch (error) {
                        Swal.fire('Error', 'Failed to update supplier', 'error');
                    }
                }

                if (deleteButton) {
                    const result = await Swal.fire({
                        title: 'Delete supplier?',
                        icon: 'warning',
                        showCancelButton: true
                    });

                    if (!result.isConfirmed) {
                        return;
                    }

                    try {
                        const response = await fetch(`${routes.destroy}/${deleteButton.dataset.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Failed to delete supplier');
                        }

                        const data = await response.json();
                        await loadSuppliers();
                        Swal.fire('Success', data.message, 'success');
                    } catch (error) {
                        Swal.fire('Error', 'Failed to delete supplier', 'error');
                    }
                }
            });

            loadSuppliers().catch(() => {
                Swal.fire('Error', 'Failed to load suppliers', 'error');
            });
        });
    </script>
@endsection

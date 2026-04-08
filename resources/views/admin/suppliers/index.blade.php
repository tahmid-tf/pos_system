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
                            Maintain supplier records and monitor procurement balances in one place.
                        </div>
                    </div>
                    <div class="col-12 col-xl-auto mt-4">
                        <button class="btn btn-primary" type="button" id="addSupplierBtn">
                            + Add Supplier
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container-xl px-4 mt-n10">
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-primary h-100">
                    <div class="card-body">
                        <div class="small text-muted">Suppliers</div>
                        <div class="h3 mb-0" id="supplierCountLabel">0</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-success h-100">
                    <div class="card-body">
                        <div class="small text-muted">Total Purchased</div>
                        <div class="h3 mb-0" id="supplierPurchaseLabel">BDT 0.00</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-info h-100">
                    <div class="card-body">
                        <div class="small text-muted">Total Paid</div>
                        <div class="h3 mb-0" id="supplierPaidLabel">BDT 0.00</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-warning h-100">
                    <div class="card-body">
                        <div class="small text-muted">Outstanding Due</div>
                        <div class="h3 mb-0" id="supplierDueLabel">BDT 0.00</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Supplier List</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" data-mobile-table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Purchase Orders</th>
                                <th>Purchased</th>
                                <th>Paid</th>
                                <th>Due</th>
                                <th width="220">Action</th>
                            </tr>
                        </thead>
                        <tbody id="supplierTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="supplierModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" id="supplierForm">
                @csrf
                <input type="hidden" id="supplier_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="supplierModalTitle">Add Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="supplier_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" id="supplier_contact_person" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="supplier_phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="supplier_email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="supplier_address" rows="3" class="form-control"></textarea>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select name="is_active" id="supplier_is_active" class="form-control">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            const routes = {
                index: '{{ route('suppliers.index') }}',
                store: '{{ route('suppliers.store') }}',
                updateBase: '{{ url('/suppliers/update') }}',
                deleteBase: '{{ url('/suppliers/delete') }}'
            };
            const csrfToken = '{{ csrf_token() }}';
            const supplierModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('supplierModal'));
            let suppliers = [];

            function formatMoney(amount) {
                return 'BDT ' + Number(amount || 0).toFixed(2);
            }

            function resetForm() {
                $('#supplierForm')[0].reset();
                $('#supplier_id').val('');
                $('#supplier_is_active').val('1');
                $('#supplierModalTitle').text('Add Supplier');
            }

            function updateStats() {
                const purchased = suppliers.reduce(function(total, supplier) {
                    return total + Number(supplier.total_purchased || 0);
                }, 0);
                const paid = suppliers.reduce(function(total, supplier) {
                    return total + Number(supplier.total_paid || 0);
                }, 0);
                const due = suppliers.reduce(function(total, supplier) {
                    return total + Number(supplier.total_due || 0);
                }, 0);

                $('#supplierCountLabel').text(suppliers.length);
                $('#supplierPurchaseLabel').text(formatMoney(purchased));
                $('#supplierPaidLabel').text(formatMoney(paid));
                $('#supplierDueLabel').text(formatMoney(due));
            }

            function renderSuppliers() {
                let html = '';

                $.each(suppliers, function(_, supplier) {
                    html += `
                        <tr>
                            <td>
                                <div class="fw-semibold">${supplier.name}</div>
                                <div class="small text-muted">${supplier.address || 'No address added'}</div>
                            </td>
                            <td>
                                <div>${supplier.contact_person || '-'}</div>
                                <div class="small text-muted">${supplier.phone || '-'}${supplier.email ? ' | ' + supplier.email : ''}</div>
                            </td>
                            <td>
                                <span class="badge ${supplier.is_active ? 'bg-success' : 'bg-secondary'}">
                                    ${supplier.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </td>
                            <td>${supplier.purchase_orders_count}</td>
                            <td>${formatMoney(supplier.total_purchased)}</td>
                            <td>${formatMoney(supplier.total_paid)}</td>
                            <td>${formatMoney(supplier.total_due)}</td>
                            <td>
                                <button class="btn btn-datatable btn-icon btn-transparent-dark me-2 editBtn" type="button" data-id="${supplier.id}" title="Edit">
                                    <i data-feather="edit"></i>
                                </button>
                                <button class="btn btn-datatable btn-icon btn-transparent-dark me-2 toggleBtn" type="button" data-id="${supplier.id}" title="Toggle Status">
                                    <i data-feather="${supplier.is_active ? 'toggle-right' : 'toggle-left'}"></i>
                                </button>
                                <button class="btn btn-datatable btn-icon btn-transparent-dark deleteBtn" type="button" data-id="${supplier.id}" title="Delete">
                                    <i data-feather="trash-2"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                $('#supplierTableBody').html(html || '<tr><td colspan="8" class="text-center text-muted py-4">No suppliers found</td></tr>');
                updateStats();
                window.adminTableUtils?.enhanceTables(document.body);
                feather.replace();
            }

            function loadSuppliers() {
                $.get(routes.index).done(function(response) {
                    suppliers = response;
                    renderSuppliers();
                }).fail(function() {
                    Swal.fire('Error', 'Failed to load suppliers.', 'error');
                });
            }

            function fillForm(supplier) {
                $('#supplier_id').val(supplier.id);
                $('#supplier_name').val(supplier.name);
                $('#supplier_contact_person').val(supplier.contact_person);
                $('#supplier_phone').val(supplier.phone);
                $('#supplier_email').val(supplier.email);
                $('#supplier_address').val(supplier.address);
                $('#supplier_is_active').val(supplier.is_active ? '1' : '0');
                $('#supplierModalTitle').text('Edit Supplier');
            }

            $('#addSupplierBtn').on('click', function() {
                resetForm();
                supplierModal.show();
            });

            $('#supplierForm').on('submit', function(e) {
                e.preventDefault();

                const supplierId = $('#supplier_id').val();
                const url = supplierId ? `${routes.updateBase}/${supplierId}` : routes.store;

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                }).done(function(response) {
                    supplierModal.hide();
                    resetForm();
                    loadSuppliers();
                    Swal.fire('Success', response.message, 'success');
                }).fail(function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to save supplier.', 'error');
                });
            });

            $('#supplierTableBody').on('click', '.editBtn', function() {
                const supplierId = $(this).data('id');
                const supplier = suppliers.find(function(item) {
                    return String(item.id) === String(supplierId);
                });

                if (!supplier) {
                    Swal.fire('Error', 'Supplier not found.', 'error');
                    return;
                }

                fillForm(supplier);
                supplierModal.show();
            });

            $('#supplierTableBody').on('click', '.toggleBtn', function() {
                const supplierId = $(this).data('id');
                const supplier = suppliers.find(function(item) {
                    return String(item.id) === String(supplierId);
                });

                if (!supplier) {
                    Swal.fire('Error', 'Supplier not found.', 'error');
                    return;
                }

                $.ajax({
                    url: `${routes.updateBase}/${supplierId}`,
                    method: 'POST',
                    data: {
                        name: supplier.name,
                        contact_person: supplier.contact_person,
                        email: supplier.email,
                        phone: supplier.phone,
                        address: supplier.address,
                        is_active: supplier.is_active ? 0 : 1
                    },
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                }).done(function(response) {
                    loadSuppliers();
                    Swal.fire('Success', response.message, 'success');
                }).fail(function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to update supplier.', 'error');
                });
            });

            $('#supplierTableBody').on('click', '.deleteBtn', function() {
                const supplierId = $(this).data('id');

                Swal.fire({
                    title: 'Delete supplier?',
                    text: 'Suppliers with purchase or payment history cannot be deleted.',
                    icon: 'warning',
                    showCancelButton: true
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: `${routes.deleteBase}/${supplierId}`,
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    }).done(function(response) {
                        loadSuppliers();
                        Swal.fire('Success', response.message, 'success');
                    }).fail(function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to delete supplier.', 'error');
                    });
                });
            });

            loadSuppliers();
        });
    </script>
@endsection

@extends('layouts.admin')

@section('content')
    <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-10">
        <div class="container-xl px-4">
            <div class="page-header-content pt-4">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto mt-4">
                        <h1 class="page-header-title">
                            <div class="page-header-icon">
                                <i data-feather="users"></i>
                            </div>
                            Customer Management
                        </h1>
                        <div class="page-header-subtitle">
                            Manage customer records, review purchase history, and keep loyalty points updated.
                        </div>
                    </div>
                    <div class="col-12 col-xl-auto mt-4">
                        <button class="btn btn-primary" type="button" id="addCustomerBtn">
                            + Add Customer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container-xl px-4 mt-n10">
        <div class="card mb-4">
            <div class="card-header">Search & Filters</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" id="customerSearch"
                            placeholder="Search by name, phone, or email">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-control" id="customerStatusFilter">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Loyalty</label>
                        <select class="form-control" id="customerLoyaltyFilter">
                            <option value="">All</option>
                            <option value="with_points">With Points</option>
                            <option value="without_points">Without Points</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-primary h-100">
                    <div class="card-body">
                        <div class="small text-muted">Total Customers</div>
                        <div class="h3 mb-0" id="customerCountLabel">0</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-success h-100">
                    <div class="card-body">
                        <div class="small text-muted">Active Customers</div>
                        <div class="h3 mb-0" id="activeCustomerCountLabel">0</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-warning h-100">
                    <div class="card-body">
                        <div class="small text-muted">Points Issued</div>
                        <div class="h3 mb-0" id="loyaltyPointsLabel">0</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-info h-100">
                    <div class="card-body">
                        <div class="small text-muted">Customer Revenue</div>
                        <div class="h3 mb-0" id="customerRevenueLabel">BDT 0.00</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Customer List</div>
            <div class="card-body" id="customerTableWrapper">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Purchases</th>
                            <th>Total Spent</th>
                            <th>Loyalty Points</th>
                            <th>Last Purchase</th>
                            <th width="220">Action</th>
                        </tr>
                    </thead>
                    <tbody id="customerTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="customerModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" id="customerForm">
                @csrf
                <input type="hidden" id="customer_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerModalTitle">Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" id="customer_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" id="customer_phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="customer_email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address" id="customer_address">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Opening Loyalty Points</label>
                            <input type="number" min="0" class="form-control" name="loyalty_points" id="customer_loyalty_points" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="is_active" id="customer_is_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Save Customer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="historyModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Purchase History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4" id="historySummary"></div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Paid</th>
                                    <th>Due</th>
                                    <th>Items</th>
                                    <th>Payment Modes</th>
                                    <th>Sold At</th>
                                    <th width="90">Receipt</th>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            const routes = {
                index: '{{ route('customers.index') }}',
                store: '{{ route('customers.store') }}',
                showBase: '{{ url('/customers') }}',
                updateBase: '{{ url('/customers/update') }}',
                deleteBase: '{{ url('/customers/delete') }}'
            };
            const csrfToken = '{{ csrf_token() }}';
            const customerModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('customerModal'));
            const historyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('historyModal'));
            let customers = [];

            function formatMoney(amount) {
                return 'BDT ' + Number(amount || 0).toFixed(2);
            }

            function formatDate(value) {
                if (!value) {
                    return '-';
                }

                return new Date(value.replace(' ', 'T')).toLocaleString();
            }

            function getFilters() {
                return {
                    search: $('#customerSearch').val(),
                    status: $('#customerStatusFilter').val(),
                    loyalty: $('#customerLoyaltyFilter').val()
                };
            }

            function resetCustomerForm() {
                $('#customerForm')[0].reset();
                $('#customer_id').val('');
                $('#customer_loyalty_points').val(0);
                $('#customer_is_active').val('1');
                $('#customerModalTitle').text('Add Customer');
            }

            function updateStats() {
                const activeCount = customers.filter(function(customer) {
                    return customer.is_active;
                }).length;
                const totalPoints = customers.reduce(function(total, customer) {
                    return total + Number(customer.loyalty_points || 0);
                }, 0);
                const totalRevenue = customers.reduce(function(total, customer) {
                    return total + Number(customer.total_spent || 0);
                }, 0);

                $('#customerCountLabel').text(customers.length);
                $('#activeCustomerCountLabel').text(activeCount);
                $('#loyaltyPointsLabel').text(totalPoints);
                $('#customerRevenueLabel').text(formatMoney(totalRevenue));
            }

            function renderCustomers() {
                let html = '';

                $.each(customers, function(_, customer) {
                    html += `
                        <tr>
                            <td>
                                <div class="fw-semibold">${customer.name}</div>
                                <div class="small text-muted">${customer.address || 'No address added'}</div>
                            </td>
                            <td>
                                <div>${customer.phone || '-'}</div>
                                <div class="small text-muted">${customer.email || '-'}</div>
                            </td>
                            <td>
                                <span class="badge ${customer.is_active ? 'bg-success' : 'bg-secondary'}">
                                    ${customer.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </td>
                            <td>${customer.sales_count}</td>
                            <td>${formatMoney(customer.total_spent)}</td>
                            <td>${customer.loyalty_points}</td>
                            <td>${formatDate(customer.last_purchase_at)}</td>
                            <td>
                                <button class="btn btn-datatable btn-icon btn-transparent-dark me-2 historyBtn" type="button" data-id="${customer.id}" title="Purchase History">
                                    <i data-feather="clock"></i>
                                </button>
                                <button class="btn btn-datatable btn-icon btn-transparent-dark me-2 editBtn" type="button" data-id="${customer.id}" title="Edit">
                                    <i data-feather="edit"></i>
                                </button>
                                <button class="btn btn-datatable btn-icon btn-transparent-dark deleteBtn" type="button" data-id="${customer.id}" title="Delete">
                                    <i data-feather="trash-2"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                $('#customerTableBody').html(html || '<tr><td colspan="8" class="text-center text-muted py-4">No customers found</td></tr>');
                updateStats();
                feather.replace();
            }

            function loadCustomers() {
                $.get(routes.index, getFilters()).done(function(response) {
                    customers = response;
                    renderCustomers();
                }).fail(function() {
                    Swal.fire('Error', 'Failed to load customers.', 'error');
                });
            }

            function renderHistory(customer, sales) {
                $('#historySummary').html(`
                    <div class="col-md-3 mb-3">
                        <div class="card border-start-lg border-start-primary h-100">
                            <div class="card-body">
                                <div class="small text-muted">Customer</div>
                                <div class="fw-bold">${customer.name}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-start-lg border-start-success h-100">
                            <div class="card-body">
                                <div class="small text-muted">Purchases</div>
                                <div class="fw-bold">${customer.sales_count}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-start-lg border-start-info h-100">
                            <div class="card-body">
                                <div class="small text-muted">Total Spent</div>
                                <div class="fw-bold">${formatMoney(customer.total_spent)}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-start-lg border-start-warning h-100">
                            <div class="card-body">
                                <div class="small text-muted">Loyalty Points</div>
                                <div class="fw-bold">${customer.loyalty_points}</div>
                            </div>
                        </div>
                    </div>
                `);

                let rows = '';

                $.each(sales, function(_, sale) {
                    rows += `
                        <tr>
                            <td>${sale.invoice_number}</td>
                            <td><span class="badge bg-${sale.status === 'paid' ? 'success' : (sale.status === 'partial' ? 'warning text-dark' : 'secondary')}">${sale.status}</span></td>
                            <td>${formatMoney(sale.total)}</td>
                            <td>${formatMoney(sale.paid_amount)}</td>
                            <td>${formatMoney(sale.due_amount)}</td>
                            <td>${sale.items_count}</td>
                            <td>${sale.payment_methods.join(', ') || '-'}</td>
                            <td>${formatDate(sale.sold_at)}</td>
                            <td>
                                <a href="${sale.receipt_url}" target="_blank" class="btn btn-datatable btn-icon btn-transparent-dark" title="Receipt">
                                    <i data-feather="printer"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });

                $('#historyTableBody').html(rows || '<tr><td colspan="9" class="text-center text-muted py-4">No purchase history found</td></tr>');
                feather.replace();
            }

            $('#addCustomerBtn').on('click', function() {
                resetCustomerForm();
                customerModal.show();
            });

            $('#customerSearch').on('input', loadCustomers);
            $('#customerStatusFilter, #customerLoyaltyFilter').on('change', loadCustomers);

            $('#customerForm').on('submit', function(e) {
                e.preventDefault();

                const customerId = $('#customer_id').val();
                const url = customerId ? `${routes.updateBase}/${customerId}` : routes.store;

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                }).done(function(response) {
                    customerModal.hide();
                    resetCustomerForm();
                    loadCustomers();
                    Swal.fire('Success', response.message, 'success');
                }).fail(function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to save customer.', 'error');
                });
            });

            $('#customerTableBody').on('click', '.editBtn', function() {
                const customer = customers.find(function(item) {
                    return String(item.id) === String($(this).data('id'));
                }.bind(this));

                if (!customer) {
                    Swal.fire('Error', 'Customer not found.', 'error');
                    return;
                }

                $('#customer_id').val(customer.id);
                $('#customer_name').val(customer.name);
                $('#customer_phone').val(customer.phone);
                $('#customer_email').val(customer.email);
                $('#customer_address').val(customer.address);
                $('#customer_loyalty_points').val(customer.loyalty_points);
                $('#customer_is_active').val(customer.is_active ? '1' : '0');
                $('#customerModalTitle').text('Edit Customer');
                customerModal.show();
            });

            $('#customerTableBody').on('click', '.historyBtn', function() {
                const customerId = $(this).data('id');

                $.get(`${routes.showBase}/${customerId}`).done(function(response) {
                    renderHistory(response.customer, response.sales);
                    historyModal.show();
                }).fail(function() {
                    Swal.fire('Error', 'Failed to load purchase history.', 'error');
                });
            });

            $('#customerTableBody').on('click', '.deleteBtn', function() {
                const customerId = $(this).data('id');

                Swal.fire({
                    title: 'Delete customer?',
                    text: 'Customers with purchase history cannot be deleted.',
                    icon: 'warning',
                    showCancelButton: true
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: `${routes.deleteBase}/${customerId}`,
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    }).done(function(response) {
                        loadCustomers();
                        Swal.fire('Success', response.message, 'success');
                    }).fail(function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to delete customer.', 'error');
                    });
                });
            });

            loadCustomers();
        });
    </script>
@endsection

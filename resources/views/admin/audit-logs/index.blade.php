@extends('layouts.admin')

@section('content')
    <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-10">
        <div class="container-xl px-4">
            <div class="page-header-content pt-4">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto mt-4">
                        <h1 class="page-header-title">
                            <div class="page-header-icon">
                                <i data-feather="shield"></i>
                            </div>
                            Audit Logs
                        </h1>
                        <div class="page-header-subtitle">
                            Review user activity and changes across products, inventory, orders, and accounting transactions.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container-xl px-4 mt-n10">
        <div class="card mb-4">
            <div class="card-header">Filter Logs</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Module</label>
                        <select class="form-control" id="auditModuleFilter">
                            <option value="">All Modules</option>
                            <option value="products">Products</option>
                            <option value="inventory">Inventory</option>
                            <option value="sales">Sales</option>
                            <option value="purchase_orders">Purchase Orders</option>
                            <option value="accounting">Accounting</option>
                            <option value="customers">Customers</option>
                            <option value="promotions">Promotions</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Action</label>
                        <input type="text" class="form-control" id="auditActionFilter" placeholder="created, updated, payment_recorded">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" id="auditSearchFilter" placeholder="Search description, module, or user">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="button" id="auditFilterBtn">Apply Filters</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Activity Timeline</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" data-mobile-table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Changes</th>
                                <th>Meta</th>
                            </tr>
                        </thead>
                        <tbody id="auditLogTableBody"></tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="small text-muted" id="auditPaginationLabel">Showing 0 of 0</div>
                    <div class="btn-group">
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="auditPrevBtn">Previous</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="auditNextBtn">Next</button>
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
                list: '{{ route('auditLogs.list') }}'
            };
            let pagination = {
                current_page: 1,
                last_page: 1
            };

            function jsonBlock(data) {
                const keys = Object.keys(data || {});

                if (!keys.length) {
                    return '<span class="text-muted">-</span>';
                }

                return `<pre class="small mb-0">${JSON.stringify(data, null, 2)}</pre>`;
            }

            function renderRows(rows) {
                let html = '';

                $.each(rows, function(_, row) {
                    html += `
                        <tr>
                            <td>${row.created_at || '-'}</td>
                            <td>
                                <div class="fw-semibold">${row.user_name}</div>
                                <div class="small text-muted">${row.user_email || '-'}</div>
                            </td>
                            <td><span class="badge bg-light text-dark">${row.module}</span></td>
                            <td><span class="badge bg-primary">${row.action}</span></td>
                            <td>${row.description}</td>
                            <td>
                                <div class="small text-muted mb-1">Old</div>
                                ${jsonBlock(row.old_values)}
                                <div class="small text-muted mt-2 mb-1">New</div>
                                ${jsonBlock(row.new_values)}
                            </td>
                            <td>${jsonBlock(row.meta)}</td>
                        </tr>
                    `;
                });

                $('#auditLogTableBody').html(html || '<tr><td colspan="7" class="text-center text-muted py-5">No audit logs found for the selected filters.</td></tr>');
                window.adminTableUtils?.enhanceTables(document.body);
            }

            function updatePagination(meta) {
                pagination = meta;
                $('#auditPaginationLabel').text(`Showing ${meta.from || 0} to ${meta.to || 0} of ${meta.total || 0}`);
                $('#auditPrevBtn').prop('disabled', meta.current_page <= 1);
                $('#auditNextBtn').prop('disabled', meta.current_page >= meta.last_page);
            }

            function filters(page) {
                return {
                    page: page || pagination.current_page || 1,
                    module: $('#auditModuleFilter').val(),
                    action: $('#auditActionFilter').val(),
                    search: $('#auditSearchFilter').val()
                };
            }

            function loadLogs(page) {
                $.get(routes.list, filters(page)).done(function(response) {
                    renderRows(response.data || []);
                    updatePagination(response.pagination || {
                        current_page: 1,
                        last_page: 1
                    });
                }).fail(function() {
                    Swal.fire('Error', 'Failed to load audit logs.', 'error');
                });
            }

            $('#auditFilterBtn').on('click', function() {
                loadLogs(1);
            });

            $('#auditSearchFilter').on('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    loadLogs(1);
                }
            });

            $('#auditPrevBtn').on('click', function() {
                if (pagination.current_page > 1) {
                    loadLogs(pagination.current_page - 1);
                }
            });

            $('#auditNextBtn').on('click', function() {
                if (pagination.current_page < pagination.last_page) {
                    loadLogs(pagination.current_page + 1);
                }
            });

            loadLogs(1);
        });
    </script>
@endsection

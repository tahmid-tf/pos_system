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
                <form id="movementFilterForm" class="row g-3">
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
                        <button type="button" id="resetMovementFilters" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Movement Logs</span>
                <span class="small text-muted" id="movementPaginationSummary"></span>
            </div>
            <div class="card-body" id="movementTableWrapper">
                <table class="table table-bordered align-middle mb-0">
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
                    <tbody></tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-primary btn-sm" id="movementPrevBtn">Previous</button>
                <span class="small text-muted" id="movementPageLabel"></span>
                <button type="button" class="btn btn-outline-primary btn-sm" id="movementNextBtn">Next</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const movementFilterForm = document.getElementById('movementFilterForm');
            const resetMovementFilters = document.getElementById('resetMovementFilters');
            const movementTableWrapper = document.getElementById('movementTableWrapper');
            const movementPrevBtn = document.getElementById('movementPrevBtn');
            const movementNextBtn = document.getElementById('movementNextBtn');
            const movementPageLabel = document.getElementById('movementPageLabel');
            const movementPaginationSummary = document.getElementById('movementPaginationSummary');

            const routes = {
                index: '{{ route('inventory.movements') }}',
            };

            let currentPage = 1;
            let lastPage = 1;

            function getQueryString(page = 1) {
                const formData = new FormData(movementFilterForm);
                const params = new URLSearchParams();

                if (formData.get('type')) {
                    params.append('type', formData.get('type'));
                }

                if (formData.get('product_id')) {
                    params.append('product_id', formData.get('product_id'));
                }

                params.append('page', page);

                return params.toString();
            }

            function formatDate(dateString) {
                if (!dateString) {
                    return '-';
                }

                return new Date(dateString).toLocaleString();
            }

            function getTableMarkup(movements) {
                return `
                    <table class="table table-bordered align-middle mb-0">
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
                            ${movements.length ? movements.map((movement) => `
                                <tr>
                                    <td>${formatDate(movement.created_at)}</td>
                                    <td>${movement.product?.name ?? '-'}</td>
                                    <td>
                                        <span class="badge ${movement.type === 'in' ? 'bg-success' : 'bg-danger'}">
                                            ${(movement.type ?? '').toUpperCase()}
                                        </span>
                                    </td>
                                    <td>${movement.quantity ?? 0}</td>
                                    <td>${movement.reference ?? '-'}</td>
                                    <td>${movement.note ?? '-'}</td>
                                    <td>${movement.user?.name ?? 'System'}</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td colspan="7" class="text-center">No stock movement found.</td>
                                </tr>
                            `}
                        </tbody>
                    </table>
                `;
            }

            function updatePagination(pagination) {
                currentPage = pagination.current_page || 1;
                lastPage = pagination.last_page || 1;

                movementPrevBtn.disabled = currentPage <= 1;
                movementNextBtn.disabled = currentPage >= lastPage;
                movementPageLabel.textContent = `Page ${currentPage} of ${lastPage}`;

                if (pagination.total) {
                    movementPaginationSummary.textContent =
                        `Showing ${pagination.from ?? 0}-${pagination.to ?? 0} of ${pagination.total}`;
                } else {
                    movementPaginationSummary.textContent = 'No movement records';
                }
            }

            async function loadMovements(page = 1) {
                const response = await fetch(`${routes.index}?${getQueryString(page)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load stock movements');
                }

                const payload = await response.json();
                movementTableWrapper.innerHTML = getTableMarkup(payload.data || []);
                updatePagination(payload.pagination || {});

                const url = new URL(window.location.href);
                url.search = getQueryString(currentPage);
                window.history.replaceState({}, '', url);
            }

            movementFilterForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                try {
                    await loadMovements(1);
                } catch (error) {
                    Swal.fire('Error', 'Failed to apply filters', 'error');
                }
            });

            resetMovementFilters.addEventListener('click', async function() {
                movementFilterForm.reset();

                try {
                    await loadMovements(1);
                } catch (error) {
                    Swal.fire('Error', 'Failed to reset filters', 'error');
                }
            });

            movementPrevBtn.addEventListener('click', async function() {
                if (currentPage <= 1) {
                    return;
                }

                try {
                    await loadMovements(currentPage - 1);
                } catch (error) {
                    Swal.fire('Error', 'Failed to load previous page', 'error');
                }
            });

            movementNextBtn.addEventListener('click', async function() {
                if (currentPage >= lastPage) {
                    return;
                }

                try {
                    await loadMovements(currentPage + 1);
                } catch (error) {
                    Swal.fire('Error', 'Failed to load next page', 'error');
                }
            });

            document.addEventListener('inventory:refresh', function() {
                loadMovements(currentPage).catch(() => {
                    Swal.fire('Error', 'Failed to refresh movement logs', 'error');
                });
            });

            loadMovements({{ request('page', 1) }}).catch(() => {
                Swal.fire('Error', 'Failed to load stock movements', 'error');
            });
        });
    </script>
@endsection

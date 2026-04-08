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

    <div class="container-xl px-4 mt-4">
        <div class="row gx-4" id="inventorySummary">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-primary h-100">
                    <div class="card-body">
                        <div class="small text-muted">Products</div>
                        <div class="fs-4 fw-bold" data-key="total_products">{{ $summary['total_products'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-success h-100">
                    <div class="card-body">
                        <div class="small text-muted">Units In Stock</div>
                        <div class="fs-4 fw-bold" data-key="total_units">{{ $summary['total_units'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-warning h-100">
                    <div class="card-body">
                        <div class="small text-muted">Low Stock Items</div>
                        <div class="fs-4 fw-bold" data-key="low_stock_items">{{ $summary['low_stock_items'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-info h-100">
                    <div class="card-body">
                        <div class="small text-muted">Locked Items</div>
                        <div class="fs-4 fw-bold" data-key="locked_items">{{ $summary['locked_items'] }}</div>
                    </div>
                </div>
            </div>

        </div>

        <div class="card mb-4">
            <div class="card-header">Stock Overview</div>
            <div class="card-body" id="stockLevelsTableWrapper">
                <table class="table table-bordered align-middle mb-0" id="stockLevelsTable">
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
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const summaryWrapper = document.getElementById('inventorySummary');
            const stockLevelsTableWrapper = document.getElementById('stockLevelsTableWrapper');
            const csrfToken = '{{ csrf_token() }}';

            const routes = {
                index: '{{ route('inventory.stockLevels') }}',
                toggleLock: '{{ url('/inventory/products') }}',
                threshold: '{{ url('/inventory/products') }}',
            };

            function renderSummary(summary) {
                summaryWrapper.querySelector('[data-key="total_products"]').textContent = summary.total_products;
                summaryWrapper.querySelector('[data-key="total_units"]').textContent = summary.total_units;
                summaryWrapper.querySelector('[data-key="low_stock_items"]').textContent = summary.low_stock_items;
                summaryWrapper.querySelector('[data-key="locked_items"]').textContent = summary.locked_items;
            }

            function getTableMarkup(products) {
                return `
                    <table class="table table-bordered align-middle mb-0" id="stockLevelsTable">
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
                            ${products.map((product) => `
                                                <tr>
                                                    <td>${product.name}</td>
                                                    <td>${product.sku}</td>
                                                    <td>${product.current_stock}</td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            <input type="number"
                                                                class="form-control form-control-sm thresholdInput"
                                                                value="${product.low_stock_threshold}"
                                                                min="0"
                                                                data-id="${product.id}"
                                                                style="max-width:120px;">
                                                            <button class="btn btn-sm btn-primary saveThresholdBtn"
                                                                data-id="${product.id}"
                                                                type="button">Save</button>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        ${Number(product.current_stock) <= Number(product.low_stock_threshold)
                                                            ? '<span class="badge bg-warning text-dark">Low</span>'
                                                            : '<span class="badge bg-success">Healthy</span>'}
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm ${product.inventory_locked ? 'btn-danger' : 'btn-outline-danger'} toggleLockBtn"
                                                            data-id="${product.id}" type="button">
                                                            ${product.inventory_locked ? 'Locked' : 'Unlocked'}
                                                        </button>
                                                    </td>
                                                </tr>
                                            `).join('')}
                        </tbody>
                    </table>
                `;
            }

            function renderTable(products) {
                stockLevelsTableWrapper.innerHTML = getTableMarkup(products);
            }

            async function loadStockLevels() {
                const response = await fetch(routes.index, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load stock levels');
                }

                const data = await response.json();
                renderSummary(data.summary);
                renderTable(data.products);
            }

            stockLevelsTableWrapper.addEventListener('click', async function(e) {
                const toggleLockButton = e.target.closest('.toggleLockBtn');
                const saveThresholdButton = e.target.closest('.saveThresholdBtn');

                if (toggleLockButton) {
                    try {
                        const response = await fetch(
                            `${routes.toggleLock}/${toggleLockButton.dataset.id}/toggle-lock`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            });

                        if (!response.ok) {
                            throw new Error('Failed to update inventory lock');
                        }

                        const data = await response.json();
                        await loadStockLevels();
                        Swal.fire('Success', data.message, 'success');
                    } catch (error) {
                        Swal.fire('Error', 'Failed to update inventory lock', 'error');
                    }
                }

                if (saveThresholdButton) {
                    const input = stockLevelsTableWrapper.querySelector(
                        `.thresholdInput[data-id="${saveThresholdButton.dataset.id}"]`);
                    const formData = new FormData();
                    formData.append('low_stock_threshold', input.value);

                    try {
                        const response = await fetch(
                            `${routes.threshold}/${saveThresholdButton.dataset.id}/threshold`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: formData
                            });

                        if (!response.ok) {
                            throw new Error('Failed to update threshold');
                        }

                        const data = await response.json();
                        await loadStockLevels();
                        Swal.fire('Success', data.message, 'success');
                    } catch (error) {
                        Swal.fire('Error', 'Failed to update threshold', 'error');
                    }
                }
            });

            document.addEventListener('inventory:refresh', function() {
                loadStockLevels().catch(() => {
                    Swal.fire('Error', 'Failed to refresh stock levels', 'error');
                });
            });

            loadStockLevels().catch(() => {
                Swal.fire('Error', 'Failed to load stock levels', 'error');
            });
        });
    </script>
@endsection

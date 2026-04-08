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
        <div class="card mb-4">
            <div class="card-header">Create Purchase Order</div>
            <div class="card-body">
                <form id="purchaseOrderForm">
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
            <div class="card-body" id="purchaseOrderTableWrapper">
                <table class="table table-bordered align-middle" id="purchaseOrderTable">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Ordered</th>
                            <th>Items</th>
                            <th width="180">Action</th>
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
            const products = @json($products->map(function ($product) {
                return ['id' => $product->id, 'name' => $product->name];
            }));
            const purchaseOrderForm = document.getElementById('purchaseOrderForm');
            const purchaseOrderTableWrapper = document.getElementById('purchaseOrderTableWrapper');
            const tableBody = document.querySelector('#poItemsTable tbody');
            const addItemBtn = document.getElementById('addPoItemRow');
            const csrfToken = '{{ csrf_token() }}';
            let dataTableInstance = null;

            const routes = {
                index: '{{ route('purchaseOrders.index') }}',
                store: '{{ route('purchaseOrders.store') }}',
                receive: '{{ url('/purchase-orders/receive') }}',
                cancel: '{{ url('/purchase-orders/cancel') }}',
            };

            function productOptions() {
                return products.map((product) => `<option value="${product.id}">${product.name}</option>`).join('');
            }

            function refreshItemNames() {
                Array.from(tableBody.querySelectorAll('tr')).forEach((row, index) => {
                    const productInput = row.querySelector('.po-product');
                    const quantityInput = row.querySelector('.po-quantity');
                    const unitCostInput = row.querySelector('.po-unit-cost');

                    productInput.name = `items[${index}][product_id]`;
                    quantityInput.name = `items[${index}][quantity]`;
                    unitCostInput.name = `items[${index}][unit_cost]`;
                });
            }

            function addRow() {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <select class="form-control po-product" required>
                            <option value="">Select product</option>
                            ${productOptions()}
                        </select>
                    </td>
                    <td><input type="number" min="1" class="form-control po-quantity" required></td>
                    <td><input type="number" min="0" step="0.01" class="form-control po-unit-cost" required></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger removeRow">Remove</button></td>
                `;
                tableBody.appendChild(row);
                refreshItemNames();
            }

            function getStatusBadge(status) {
                if (status === 'received') {
                    return '<span class="badge bg-success">Received</span>';
                }

                if (status === 'cancelled') {
                    return '<span class="badge bg-danger">Cancelled</span>';
                }

                return '<span class="badge bg-warning text-dark">Pending</span>';
            }

            function getActionButtons(po) {
                if (po.status !== 'pending') {
                    return '<span class="text-muted">No action</span>';
                }

                return `
                    <button class="btn btn-datatable btn-icon btn-transparent-dark me-2 receivePoBtn"
                        data-id="${po.id}" type="button" title="Receive">
                        <i data-feather="check-circle"></i>
                    </button>
                    <button class="btn btn-datatable btn-icon btn-transparent-dark cancelPoBtn"
                        data-id="${po.id}" type="button" title="Cancel">
                        <i data-feather="x-circle"></i>
                    </button>
                `;
            }

            function getTableMarkup(purchaseOrders) {
                return `
                    <table class="table table-bordered align-middle" id="purchaseOrderTable">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Ordered</th>
                                <th>Items</th>
                                <th width="180">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${purchaseOrders.map((po) => `
                                <tr>
                                    <td>${po.po_number}</td>
                                    <td>${po.supplier?.name ?? '-'}</td>
                                    <td>${getStatusBadge(po.status)}</td>
                                    <td>${Number(po.total_amount).toFixed(2)}</td>
                                    <td>${new Date(po.ordered_at).toLocaleString()}</td>
                                    <td>${po.items?.length ?? 0}</td>
                                    <td>${getActionButtons(po)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }

            function renderTable(purchaseOrders) {
                if (dataTableInstance) {
                    dataTableInstance.destroy();
                    dataTableInstance = null;
                }

                purchaseOrderTableWrapper.innerHTML = getTableMarkup(purchaseOrders);
                dataTableInstance = new simpleDatatables.DataTable(document.getElementById('purchaseOrderTable'));
                feather.replace();
            }

            async function loadPurchaseOrders() {
                const response = await fetch(routes.index, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load purchase orders');
                }

                const purchaseOrders = await response.json();
                renderTable(purchaseOrders);
            }

            addItemBtn.addEventListener('click', addRow);

            tableBody.addEventListener('click', function(e) {
                if (e.target.classList.contains('removeRow')) {
                    e.target.closest('tr').remove();
                    refreshItemNames();
                }
            });

            purchaseOrderForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                try {
                    const response = await fetch(routes.store, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: new FormData(purchaseOrderForm)
                    });

                    if (!response.ok) {
                        throw new Error('Failed to create purchase order');
                    }

                    const data = await response.json();
                    purchaseOrderForm.reset();
                    tableBody.innerHTML = '';
                    addRow();
                    await loadPurchaseOrders();
                    Swal.fire('Success', data.message, 'success');
                } catch (error) {
                    Swal.fire('Error', 'Failed to create purchase order', 'error');
                }
            });

            purchaseOrderTableWrapper.addEventListener('click', async function(e) {
                const receiveButton = e.target.closest('.receivePoBtn');
                const cancelButton = e.target.closest('.cancelPoBtn');

                if (receiveButton) {
                    try {
                        const response = await fetch(`${routes.receive}/${receiveButton.dataset.id}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Failed to receive purchase order');
                        }

                        const data = await response.json();
                        await loadPurchaseOrders();
                        document.dispatchEvent(new CustomEvent('inventory:refresh'));
                        Swal.fire('Success', data.message, 'success');
                    } catch (error) {
                        Swal.fire('Error', 'Failed to receive purchase order', 'error');
                    }
                }

                if (cancelButton) {
                    const result = await Swal.fire({
                        title: 'Cancel purchase order?',
                        icon: 'warning',
                        showCancelButton: true
                    });

                    if (!result.isConfirmed) {
                        return;
                    }

                    try {
                        const response = await fetch(`${routes.cancel}/${cancelButton.dataset.id}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Failed to cancel purchase order');
                        }

                        const data = await response.json();
                        await loadPurchaseOrders();
                        Swal.fire('Success', data.message, 'success');
                    } catch (error) {
                        Swal.fire('Error', 'Failed to cancel purchase order', 'error');
                    }
                }
            });

            addRow();
            loadPurchaseOrders().catch(() => {
                Swal.fire('Error', 'Failed to load purchase orders', 'error');
            });
        });
    </script>
@endsection

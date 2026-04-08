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
                            Create purchase orders, receive stock, and track supplier payments asynchronously.
                        </div>
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
                        <div class="small text-muted">Purchase Orders</div>
                        <div class="h3 mb-0" id="purchaseOrderCountLabel">0</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-success h-100">
                    <div class="card-body">
                        <div class="small text-muted">Order Value</div>
                        <div class="h3 mb-0" id="purchaseOrderTotalLabel">BDT 0.00</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-info h-100">
                    <div class="card-body">
                        <div class="small text-muted">Paid to Suppliers</div>
                        <div class="h3 mb-0" id="purchaseOrderPaidLabel">BDT 0.00</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-warning h-100">
                    <div class="card-body">
                        <div class="small text-muted">Outstanding Due</div>
                        <div class="h3 mb-0" id="purchaseOrderDueLabel">BDT 0.00</div>
                    </div>
                </div>
            </div>
        </div>

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
                            <table class="table table-bordered" id="poItemsTable" data-mobile-table>
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
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" data-mobile-table>
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Due</th>
                                <th>Ordered</th>
                                <th>Items</th>
                                <th width="220">Action</th>
                            </tr>
                        </thead>
                        <tbody id="purchaseOrderTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Recent Supplier Payments</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" data-mobile-table>
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>PO Number</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Reference</th>
                                <th>Paid At</th>
                            </tr>
                        </thead>
                        <tbody id="paymentHistoryTableBody">
                            @forelse ($recentPayments as $payment)
                                <tr>
                                    <td>{{ $payment->supplier?->name ?? '-' }}</td>
                                    <td>{{ $payment->purchaseOrder?->po_number ?? '-' }}</td>
                                    <td>{{ ucfirst($payment->method) }}</td>
                                    <td>BDT {{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ $payment->reference ?? '-' }}</td>
                                    <td>{{ optional($payment->paid_at)->format('d M Y h:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No supplier payments recorded yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" id="paymentForm">
                @csrf
                <input type="hidden" id="payment_purchase_order_id">
                <div class="modal-header">
                    <h5 class="modal-title">Record Supplier Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3" id="paymentModalSummary"></div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" min="0.01" step="0.01" name="amount" id="payment_amount" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Method</label>
                            <select name="method" id="payment_method" class="form-control">
                                <option value="cash">Cash</option>
                                <option value="bank">Bank</option>
                                <option value="mobile">Mobile</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Paid At</label>
                            <input type="datetime-local" name="paid_at" id="payment_paid_at" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference</label>
                        <input type="text" name="reference" id="payment_reference" class="form-control">
                    </div>
                    <div>
                        <label class="form-label">Note</label>
                        <textarea name="note" id="payment_note" rows="3" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            const products = @json($products->map(function ($product) {
                return ['id' => $product->id, 'name' => $product->name];
            }));
            const routes = {
                index: '{{ route('purchaseOrders.index') }}',
                store: '{{ route('purchaseOrders.store') }}',
                payBase: '{{ url('/purchase-orders/pay') }}',
                receiveBase: '{{ url('/purchase-orders/receive') }}',
                cancelBase: '{{ url('/purchase-orders/cancel') }}'
            };
            const csrfToken = '{{ csrf_token() }}';
            const paymentModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentModal'));
            let purchaseOrders = [];

            function formatMoney(amount) {
                return 'BDT ' + Number(amount || 0).toFixed(2);
            }

            function formatDate(value) {
                if (!value) {
                    return '-';
                }

                return new Date(value.replace(' ', 'T')).toLocaleString();
            }

            function productOptions() {
                return products.map(function(product) {
                    return `<option value="${product.id}">${product.name}</option>`;
                }).join('');
            }

            function addRow() {
                const index = $('#poItemsTable tbody tr').length;
                $('#poItemsTable tbody').append(`
                    <tr>
                        <td>
                            <select class="form-control" name="items[${index}][product_id]" required>
                                <option value="">Select product</option>
                                ${productOptions()}
                            </select>
                        </td>
                        <td><input type="number" min="1" class="form-control" name="items[${index}][quantity]" required></td>
                        <td><input type="number" min="0" step="0.01" class="form-control" name="items[${index}][unit_cost]" required></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger removeRow">Remove</button></td>
                    </tr>
                `);
            }

            function refreshRowIndexes() {
                $('#poItemsTable tbody tr').each(function(index) {
                    $(this).find('select, input').each(function() {
                        const name = $(this).attr('name');

                        if (name) {
                            $(this).attr('name', name.replace(/items\[\d+\]/, `items[${index}]`));
                        }
                    });
                });
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

            function updateStats() {
                const totals = purchaseOrders.reduce(function(accumulator, purchaseOrder) {
                    accumulator.total += Number(purchaseOrder.total_amount || 0);
                    accumulator.paid += Number(purchaseOrder.paid_amount || 0);
                    accumulator.due += Number(purchaseOrder.due_amount || 0);
                    return accumulator;
                }, {
                    total: 0,
                    paid: 0,
                    due: 0
                });

                $('#purchaseOrderCountLabel').text(purchaseOrders.length);
                $('#purchaseOrderTotalLabel').text(formatMoney(totals.total));
                $('#purchaseOrderPaidLabel').text(formatMoney(totals.paid));
                $('#purchaseOrderDueLabel').text(formatMoney(totals.due));
            }

            function renderPurchaseOrders() {
                let html = '';

                $.each(purchaseOrders, function(_, purchaseOrder) {
                    html += `
                        <tr>
                            <td>
                                <div class="fw-semibold">${purchaseOrder.po_number}</div>
                                <div class="small text-muted">${purchaseOrder.note || 'No note added'}</div>
                            </td>
                            <td>${purchaseOrder.supplier ? purchaseOrder.supplier.name : '-'}</td>
                            <td>${getStatusBadge(purchaseOrder.status)}</td>
                            <td>${formatMoney(purchaseOrder.total_amount)}</td>
                            <td>${formatMoney(purchaseOrder.paid_amount)}</td>
                            <td>${formatMoney(purchaseOrder.due_amount)}</td>
                            <td>${formatDate(purchaseOrder.ordered_at)}</td>
                            <td>${purchaseOrder.items.length}</td>
                            <td>
                                <button class="btn btn-datatable btn-icon btn-transparent-dark me-2 payBtn" type="button" data-id="${purchaseOrder.id}" title="Record Payment" ${purchaseOrder.status === 'cancelled' || purchaseOrder.due_amount <= 0 ? 'disabled' : ''}>
                                    <i data-feather="credit-card"></i>
                                </button>
                                <button class="btn btn-datatable btn-icon btn-transparent-dark me-2 receiveBtn" type="button" data-id="${purchaseOrder.id}" title="Receive" ${purchaseOrder.status !== 'pending' ? 'disabled' : ''}>
                                    <i data-feather="check-circle"></i>
                                </button>
                                <button class="btn btn-datatable btn-icon btn-transparent-dark cancelBtn" type="button" data-id="${purchaseOrder.id}" title="Cancel" ${purchaseOrder.status !== 'pending' ? 'disabled' : ''}>
                                    <i data-feather="x-circle"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                $('#purchaseOrderTableBody').html(html || '<tr><td colspan="9" class="text-center text-muted py-4">No purchase orders found</td></tr>');
                updateStats();
                window.adminTableUtils?.enhanceTables(document.body);
                feather.replace();
            }

            function renderPaymentHistory() {
                let rows = '';
                const payments = [];

                $.each(purchaseOrders, function(_, purchaseOrder) {
                    $.each(purchaseOrder.payments, function(_, payment) {
                        payments.push({
                            supplier_name: purchaseOrder.supplier ? purchaseOrder.supplier.name : '-',
                            po_number: purchaseOrder.po_number,
                            method: payment.method,
                            amount: payment.amount,
                            reference: payment.reference,
                            paid_at: payment.paid_at
                        });
                    });
                });

                payments.sort(function(first, second) {
                    const firstDate = first.paid_at ? new Date(first.paid_at.replace(' ', 'T')) : new Date(0);
                    const secondDate = second.paid_at ? new Date(second.paid_at.replace(' ', 'T')) : new Date(0);
                    return secondDate - firstDate;
                });

                $.each(payments.slice(0, 20), function(_, payment) {
                    rows += `
                        <tr>
                            <td>${payment.supplier_name}</td>
                            <td>${payment.po_number}</td>
                            <td>${payment.method}</td>
                            <td>${formatMoney(payment.amount)}</td>
                            <td>${payment.reference || '-'}</td>
                            <td>${formatDate(payment.paid_at)}</td>
                        </tr>
                    `;
                });

                $('#paymentHistoryTableBody').html(rows || '<tr><td colspan="6" class="text-center text-muted py-4">No supplier payments recorded yet</td></tr>');
                window.adminTableUtils?.enhanceTables(document.body);
            }

            function loadPurchaseOrders() {
                $.get(routes.index).done(function(response) {
                    purchaseOrders = response;
                    renderPurchaseOrders();
                    renderPaymentHistory();
                }).fail(function() {
                    Swal.fire('Error', 'Failed to load purchase orders.', 'error');
                });
            }

            function resetPaymentForm() {
                $('#paymentForm')[0].reset();
                $('#payment_purchase_order_id').val('');
                $('#paymentModalSummary').html('');
            }

            $('#addPoItemRow').on('click', addRow);

            $('#poItemsTable tbody').on('click', '.removeRow', function() {
                $(this).closest('tr').remove();
                refreshRowIndexes();
            });

            $('#purchaseOrderForm').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: routes.store,
                    method: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                }).done(function(response) {
                    $('#purchaseOrderForm')[0].reset();
                    $('#poItemsTable tbody').empty();
                    addRow();
                    loadPurchaseOrders();
                    Swal.fire('Success', response.message, 'success');
                }).fail(function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to create purchase order.', 'error');
                });
            });

            $('#purchaseOrderTableBody').on('click', '.payBtn', function() {
                const purchaseOrderId = $(this).data('id');
                const purchaseOrder = purchaseOrders.find(function(item) {
                    return String(item.id) === String(purchaseOrderId);
                });

                if (!purchaseOrder) {
                    Swal.fire('Error', 'Purchase order not found.', 'error');
                    return;
                }

                resetPaymentForm();
                $('#payment_purchase_order_id').val(purchaseOrder.id);
                $('#payment_amount').attr('max', purchaseOrder.due_amount).val(purchaseOrder.due_amount);
                $('#payment_paid_at').val(new Date().toISOString().slice(0, 16));
                $('#paymentModalSummary').html(`
                    <div><strong>PO:</strong> ${purchaseOrder.po_number}</div>
                    <div><strong>Supplier:</strong> ${purchaseOrder.supplier ? purchaseOrder.supplier.name : '-'}</div>
                    <div><strong>Outstanding Due:</strong> ${formatMoney(purchaseOrder.due_amount)}</div>
                `);
                paymentModal.show();
            });

            $('#paymentForm').on('submit', function(e) {
                e.preventDefault();

                const purchaseOrderId = $('#payment_purchase_order_id').val();

                $.ajax({
                    url: `${routes.payBase}/${purchaseOrderId}`,
                    method: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                }).done(function(response) {
                    paymentModal.hide();
                    resetPaymentForm();
                    loadPurchaseOrders();
                    Swal.fire('Success', response.message, 'success');
                }).fail(function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to record supplier payment.', 'error');
                });
            });

            $('#purchaseOrderTableBody').on('click', '.receiveBtn', function() {
                const purchaseOrderId = $(this).data('id');

                $.ajax({
                    url: `${routes.receiveBase}/${purchaseOrderId}`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                }).done(function(response) {
                    loadPurchaseOrders();
                    $(document).trigger('inventory:refresh');
                    Swal.fire('Success', response.message, 'success');
                }).fail(function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to receive purchase order.', 'error');
                });
            });

            $('#purchaseOrderTableBody').on('click', '.cancelBtn', function() {
                const purchaseOrderId = $(this).data('id');

                Swal.fire({
                    title: 'Cancel purchase order?',
                    icon: 'warning',
                    showCancelButton: true
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: `${routes.cancelBase}/${purchaseOrderId}`,
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    }).done(function(response) {
                        loadPurchaseOrders();
                        Swal.fire('Success', response.message, 'success');
                    }).fail(function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to cancel purchase order.', 'error');
                    });
                });
            });

            addRow();
            loadPurchaseOrders();
        });
    </script>
@endsection

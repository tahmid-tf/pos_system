@extends('layouts.admin')

@section('content')
    <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-10">
        <div class="container-xl px-4">
            <div class="page-header-content pt-4">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto mt-4">
                        <h1 class="page-header-title">
                            <div class="page-header-icon"><i data-feather="shopping-cart"></i></div>
                            Sales / POS Terminal
                        </h1>
                        <div class="page-header-subtitle">
                            Build carts, accept mixed payments, update stock in real time, and print invoices.
                        </div>
                    </div>
                    <div class="col-12 col-xl-auto mt-4">
                        <div class="badge bg-white text-primary p-3">
                            Terminal Date: {{ now()->format('d M Y h:i A') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container-xl px-4 mt-n10">
        <div class="row">
            <div class="col-xl-8 mb-4">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Products</span>
                        <div class="input-group" style="max-width: 320px;">
                            <span class="input-group-text"><i data-feather="search"></i></span>
                            <input type="text" class="form-control" id="productSearch" placeholder="Search product or SKU">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3" id="productGrid">
                            @foreach ($products as $product)
                                @php
                                    $availableStock = optional($product->stockRecord)->quantity;
                                    $availableStock = $availableStock ?? $product->stock;
                                @endphp
                                <div class="col-md-6 col-xl-4 product-card-item"
                                    data-name="{{ strtolower($product->name) }}"
                                    data-sku="{{ strtolower($product->sku) }}">
                                    <div class="card border h-100 shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <div class="fw-bold">{{ $product->name }}</div>
                                                    <div class="small text-muted">{{ $product->sku }}</div>
                                                    <div class="small text-muted">{{ $product->category?->name ?? 'Uncategorized' }}</div>
                                                </div>
                                                <span
                                                    class="badge {{ $availableStock <= $product->low_stock_threshold ? 'bg-warning text-dark' : 'bg-success' }}">
                                                    Stock: <span class="product-stock-label">{{ $availableStock }}</span>
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="small text-muted">Selling price</div>
                                                    <div class="h5 mb-0">৳{{ number_format($product->price, 2) }}</div>
                                                </div>
                                                <button
                                                    class="btn btn-primary add-to-cart-btn"
                                                    type="button"
                                                    data-id="{{ $product->id }}"
                                                    data-name="{{ $product->name }}"
                                                    data-sku="{{ $product->sku }}"
                                                    data-price="{{ $product->price }}"
                                                    data-stock="{{ $availableStock }}"
                                                    data-locked="{{ $product->inventory_locked ? 1 : 0 }}">
                                                    Add
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Transaction History</span>
                        <div class="input-group" style="max-width: 320px;">
                            <span class="input-group-text"><i data-feather="clock"></i></span>
                            <input type="text" class="form-control" id="historySearch" placeholder="Search invoice or customer">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th>Paid</th>
                                        <th>Due</th>
                                        <th>Date</th>
                                        <th width="130">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="historyTableBody">
                                    @foreach ($sales as $sale)
                                        <tr>
                                            <td>{{ $sale->invoice_number }}</td>
                                            <td>{{ $sale->customer?->name ?? 'Walk-in Customer' }}</td>
                                            <td><span class="badge bg-{{ $sale->status === 'paid' ? 'success' : ($sale->status === 'partial' ? 'warning text-dark' : 'secondary') }}">{{ ucfirst($sale->status) }}</span></td>
                                            <td>৳{{ number_format($sale->total, 2) }}</td>
                                            <td>৳{{ number_format($sale->paid_amount, 2) }}</td>
                                            <td>৳{{ number_format($sale->due_amount, 2) }}</td>
                                            <td>{{ optional($sale->sold_at)->format('d M Y h:i A') }}</td>
                                            <td>
                                                <a href="{{ route('sales.show', $sale) }}"
                                                    class="btn btn-datatable btn-icon btn-transparent-dark me-2"
                                                    title="View Invoice">
                                                    <i data-feather="eye"></i>
                                                </a>
                                                <a href="{{ route('sales.receipt', $sale) }}" target="_blank"
                                                    class="btn btn-datatable btn-icon btn-transparent-dark"
                                                    title="Print Receipt">
                                                    <i data-feather="printer"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 mb-4">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Cart</span>
                        <button class="btn btn-outline-danger btn-sm" type="button" id="clearCartBtn">Clear</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive mb-3">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th width="90">Qty</th>
                                        <th width="90">Price</th>
                                        <th width="110">Total</th>
                                        <th width="40"></th>
                                    </tr>
                                </thead>
                                <tbody id="cartTableBody">
                                    <tr class="cart-empty-row">
                                        <td colspan="5" class="text-center text-muted py-4">No products in cart</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            <div class="input-group">
                                <select class="form-control" id="customerSelect">
                                    <option value="">Walk-in Customer</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal"
                                    data-bs-target="#customerModal">New</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Promotion</label>
                            <div class="input-group">
                                <select class="form-control" id="promotionSelect">
                                    <option value="">No Promotion</option>
                                    @foreach ($promotions as $promotion)
                                        <option value="{{ $promotion->id }}"
                                            data-type="{{ $promotion->type }}"
                                            data-value="{{ $promotion->value }}"
                                            data-minimum="{{ $promotion->minimum_order_amount }}">
                                            {{ $promotion->name }}
                                            {{ $promotion->code ? '(' . $promotion->code . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal"
                                    data-bs-target="#promotionModal">New</button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Manual Discount</label>
                                <input type="number" class="form-control" id="manualDiscount" min="0" step="0.01" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tax Rate (%)</label>
                                <input type="number" class="form-control" id="taxRate" min="0" step="0.01" value="5">
                            </div>
                        </div>

                        <div class="border rounded p-3 bg-light mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <strong id="subtotalLabel">৳0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Promotion Discount</span>
                                <strong id="promotionDiscountLabel">৳0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Manual Discount</span>
                                <strong id="manualDiscountLabel">৳0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax</span>
                                <strong id="taxLabel">৳0.00</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="h6 mb-0">Grand Total</span>
                                <strong class="h5 mb-0" id="grandTotalLabel">৳0.00</strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Payments</label>
                                <button class="btn btn-outline-primary btn-sm" type="button" id="addPaymentBtn">Add Payment</button>
                            </div>
                            <div id="paymentRows"></div>
                            <div class="small text-muted mt-2">
                                Use one row for a full payment or combine rows for partial split payments.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="saleNotes" rows="3" placeholder="Optional sale note"></textarea>
                        </div>

                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Paid</span>
                                <strong id="paidAmountLabel">৳0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Due</span>
                                <strong id="dueAmountLabel">৳0.00</strong>
                            </div>
                        </div>

                        <button class="btn btn-success w-100" type="button" id="checkoutBtn">Complete Sale</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="customerModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" id="customerForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div>
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Save Customer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="promotionModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" id="promotionForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">New Promotion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-control" name="type">
                                <option value="fixed">Fixed</option>
                                <option value="percentage">Percentage</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Value</label>
                            <input type="number" name="value" class="form-control" min="0.01" step="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Minimum Order</label>
                        <input type="number" name="minimum_order_amount" class="form-control" min="0" step="0.01" value="0">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Starts At</label>
                            <input type="datetime-local" name="starts_at" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ends At</label>
                            <input type="datetime-local" name="ends_at" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Save Promotion</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            const csrfToken = '{{ csrf_token() }}';
            const routes = {
                checkout: '{{ route('sales.store') }}',
                customerStore: '{{ route('sales.customers.store') }}',
                promotionStore: '{{ route('sales.promotions.store') }}',
                history: '{{ route('sales.history') }}'
            };

            let cart = [];
            let paymentIndex = 0;

            function formatMoney(amount) {
                return '৳' + Number(amount || 0).toFixed(2);
            }

            function getSelectedPromotion() {
                const option = $('#promotionSelect option:selected');

                if (!option.val()) {
                    return null;
                }

                return {
                    id: option.val(),
                    type: option.data('type'),
                    value: Number(option.data('value') || 0),
                    minimum: Number(option.data('minimum') || 0)
                };
            }

            function getSubtotal() {
                return cart.reduce(function(total, item) {
                    return total + (item.price * item.quantity);
                }, 0);
            }

            function getPromotionDiscount(subtotal) {
                const promotion = getSelectedPromotion();

                if (!promotion || subtotal < promotion.minimum) {
                    return 0;
                }

                if (promotion.type === 'percentage') {
                    return subtotal * (promotion.value / 100);
                }

                return Math.min(promotion.value, subtotal);
            }

            function getManualDiscount() {
                return Math.max(Number($('#manualDiscount').val() || 0), 0);
            }

            function getTaxRate() {
                return Math.max(Number($('#taxRate').val() || 0), 0);
            }

            function getPaidAmount() {
                let total = 0;

                $('.payment-amount').each(function() {
                    total += Math.max(Number($(this).val() || 0), 0);
                });

                return total;
            }

            function updateSummary() {
                const subtotal = getSubtotal();
                const promotionDiscount = getPromotionDiscount(subtotal);
                const manualDiscount = Math.min(getManualDiscount(), Math.max(subtotal - promotionDiscount, 0));
                const discountTotal = Math.min(subtotal, promotionDiscount + manualDiscount);
                const taxableAmount = Math.max(subtotal - discountTotal, 0);
                const tax = taxableAmount * (getTaxRate() / 100);
                const grandTotal = taxableAmount + tax;
                const paidAmount = Math.min(getPaidAmount(), grandTotal);
                const dueAmount = Math.max(grandTotal - paidAmount, 0);

                $('#subtotalLabel').text(formatMoney(subtotal));
                $('#promotionDiscountLabel').text(formatMoney(promotionDiscount));
                $('#manualDiscountLabel').text(formatMoney(manualDiscount));
                $('#taxLabel').text(formatMoney(tax));
                $('#grandTotalLabel').text(formatMoney(grandTotal));
                $('#paidAmountLabel').text(formatMoney(paidAmount));
                $('#dueAmountLabel').text(formatMoney(dueAmount));
            }

            function renderCart() {
                let html = '';

                if (!cart.length) {
                    html = '<tr class="cart-empty-row"><td colspan="5" class="text-center text-muted py-4">No products in cart</td></tr>';
                } else {
                    $.each(cart, function(index, item) {
                        html += `
                            <tr>
                                <td>
                                    <div class="fw-semibold">${item.name}</div>
                                    <div class="small text-muted">${item.sku}</div>
                                    <div class="small text-muted">Stock: ${item.stock}</div>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm cart-qty-input" min="1" ${item.locked ? `max="${item.stock}"` : ''} data-index="${index}" value="${item.quantity}">
                                </td>
                                <td>${formatMoney(item.price)}</td>
                                <td>${formatMoney(item.price * item.quantity)}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger remove-cart-item" type="button" data-index="${index}">
                                        <i data-feather="x"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }

                $('#cartTableBody').html(html);
                feather.replace();
                updateSummary();
            }

            function addPaymentRow(payment) {
                paymentIndex += 1;

                const row = `
                    <div class="border rounded p-2 mb-2 payment-row" data-index="${paymentIndex}">
                        <div class="row g-2">
                            <div class="col-4">
                                <select class="form-control payment-method">
                                    <option value="cash" ${payment && payment.method === 'cash' ? 'selected' : ''}>Cash</option>
                                    <option value="card" ${payment && payment.method === 'card' ? 'selected' : ''}>Card</option>
                                    <option value="mobile" ${payment && payment.method === 'mobile' ? 'selected' : ''}>Mobile</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <input type="number" class="form-control payment-amount" min="0.01" step="0.01" value="${payment ? payment.amount : ''}" placeholder="Amount">
                            </div>
                            <div class="col-3">
                                <input type="text" class="form-control payment-reference" value="${payment ? payment.reference || '' : ''}" placeholder="Ref">
                            </div>
                            <div class="col-1 d-grid">
                                <button class="btn btn-outline-danger remove-payment-row" type="button">&times;</button>
                            </div>
                        </div>
                    </div>
                `;

                $('#paymentRows').append(row);
            }

            function collectPayments() {
                const payments = [];

                $('#paymentRows .payment-row').each(function() {
                    const amount = Number($(this).find('.payment-amount').val() || 0);

                    if (amount > 0) {
                        payments.push({
                            method: $(this).find('.payment-method').val(),
                            amount: amount,
                            reference: $(this).find('.payment-reference').val(),
                            note: ''
                        });
                    }
                });

                return payments;
            }

            function updateProductStock(productId, quantitySold) {
                const button = $('.add-to-cart-btn[data-id="' + productId + '"]');
                const currentStock = Number(button.data('stock') || 0);
                const newStock = Math.max(currentStock - quantitySold, 0);
                button.data('stock', newStock);
                button.closest('.card').find('.product-stock-label').text(newStock);
            }

            function resetTerminal() {
                cart = [];
                $('#customerSelect').val('');
                $('#promotionSelect').val('');
                $('#manualDiscount').val(0);
                $('#taxRate').val(5);
                $('#saleNotes').val('');
                $('#paymentRows').empty();
                addPaymentRow({
                    method: 'cash',
                    amount: '',
                    reference: ''
                });
                renderCart();
            }

            function buildHistoryRows(sales) {
                let html = '';

                $.each(sales, function(_, sale) {
                    const badgeClass = sale.status === 'paid' ? 'success' : (sale.status === 'partial' ? 'warning text-dark' : 'secondary');

                    html += `
                        <tr>
                            <td>${sale.invoice_number}</td>
                            <td>${sale.customer_name}</td>
                            <td><span class="badge bg-${badgeClass}">${sale.status.charAt(0).toUpperCase() + sale.status.slice(1)}</span></td>
                            <td>${formatMoney(sale.total)}</td>
                            <td>${formatMoney(sale.paid_amount)}</td>
                            <td>${formatMoney(sale.due_amount)}</td>
                            <td>${sale.sold_at}</td>
                            <td>
                                <a href="${sale.view_url}" class="btn btn-datatable btn-icon btn-transparent-dark me-2" title="View Invoice">
                                    <i data-feather="eye"></i>
                                </a>
                                <a href="${sale.receipt_url}" target="_blank" class="btn btn-datatable btn-icon btn-transparent-dark" title="Print Receipt">
                                    <i data-feather="printer"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });

                $('#historyTableBody').html(html || '<tr><td colspan="8" class="text-center text-muted py-4">No transactions found</td></tr>');
                feather.replace();
            }

            function refreshHistory(search) {
                $.get(routes.history, {
                    search: search || ''
                }).done(function(sales) {
                    buildHistoryRows(sales);
                });
            }

            $(document).on('click', '.add-to-cart-btn', function() {
                const button = $(this);
                const id = Number(button.data('id'));
                const stock = Number(button.data('stock'));
                const locked = Number(button.data('locked')) === 1;
                let existing = cart.find(function(item) {
                    return item.product_id === id;
                });

                if (locked && stock <= 0) {
                    Swal.fire('Out of stock', 'This item is locked and has no stock available.', 'warning');
                    return;
                }

                if (existing) {
                    if (locked && existing.quantity >= stock) {
                        Swal.fire('Stock limit reached', 'You cannot add more than available stock.', 'warning');
                        return;
                    }

                    existing.quantity += 1;
                } else {
                    cart.push({
                        product_id: id,
                        name: button.data('name'),
                        sku: button.data('sku'),
                        price: Number(button.data('price')),
                        stock: stock,
                        locked: locked,
                        quantity: 1
                    });
                }

                renderCart();
            });

            $(document).on('input', '.cart-qty-input', function() {
                const index = Number($(this).data('index'));
                const item = cart[index];
                const qty = Math.max(Number($(this).val() || 1), 1);

                if (item.locked && qty > item.stock) {
                    $(this).val(item.stock);
                    item.quantity = item.stock;
                    Swal.fire('Stock limit reached', 'Quantity adjusted to available stock.', 'info');
                } else {
                    item.quantity = qty;
                }

                renderCart();
            });

            $(document).on('click', '.remove-cart-item', function() {
                const index = Number($(this).data('index'));
                cart.splice(index, 1);
                renderCart();
            });

            $('#clearCartBtn').on('click', function() {
                cart = [];
                renderCart();
            });

            $('#addPaymentBtn').on('click', function() {
                addPaymentRow();
            });

            $(document).on('click', '.remove-payment-row', function() {
                $(this).closest('.payment-row').remove();
                updateSummary();
            });

            $(document).on('input change', '#manualDiscount, #taxRate, #promotionSelect, .payment-amount', function() {
                updateSummary();
            });

            $('#productSearch').on('input', function() {
                const query = $(this).val().toLowerCase();

                $('.product-card-item').each(function() {
                    const card = $(this);
                    const haystack = (card.data('name') + ' ' + card.data('sku')).toLowerCase();
                    card.toggle(haystack.indexOf(query) !== -1);
                });
            });

            $('#historySearch').on('input', function() {
                refreshHistory($(this).val());
            });

            $('#customerForm').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: routes.customerStore,
                    method: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                }).done(function(response) {
                    $('#customerSelect').append(
                        `<option value="${response.customer.id}">${response.customer.name}${response.customer.phone ? ' - ' + response.customer.phone : ''}</option>`
                    );
                    $('#customerSelect').val(response.customer.id);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('customerModal')).hide();
                    $('#customerForm')[0].reset();
                    Swal.fire('Saved', response.message, 'success');
                }).fail(function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to create customer.', 'error');
                });
            });

            $('#promotionForm').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: routes.promotionStore,
                    method: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                }).done(function(response) {
                    $('#promotionSelect').append(
                        `<option value="${response.promotion.id}" data-type="${response.promotion.type}" data-value="${response.promotion.value}" data-minimum="${response.promotion.minimum_order_amount}">${response.promotion.name}${response.promotion.code ? ' (' + response.promotion.code + ')' : ''}</option>`
                    );
                    $('#promotionSelect').val(response.promotion.id);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('promotionModal')).hide();
                    $('#promotionForm')[0].reset();
                    updateSummary();
                    Swal.fire('Saved', response.message, 'success');
                }).fail(function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to create promotion.', 'error');
                });
            });

            $('#checkoutBtn').on('click', function() {
                if (!cart.length) {
                    Swal.fire('Cart empty', 'Add at least one product before checkout.', 'warning');
                    return;
                }

                const payload = {
                    customer_id: $('#customerSelect').val(),
                    promotion_id: $('#promotionSelect').val(),
                    manual_discount: Number($('#manualDiscount').val() || 0),
                    tax_rate: Number($('#taxRate').val() || 0),
                    notes: $('#saleNotes').val(),
                    cart: $.map(cart, function(item) {
                        return {
                            product_id: item.product_id,
                            quantity: item.quantity
                        };
                    }),
                    payments: collectPayments()
                };

                $.ajax({
                    url: routes.checkout,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    contentType: 'application/json',
                    data: JSON.stringify(payload)
                }).done(function(response) {
                    $.each(cart, function(_, item) {
                        updateProductStock(item.product_id, item.quantity);
                    });

                    resetTerminal();
                    refreshHistory($('#historySearch').val());

                    Swal.fire({
                        title: response.sale.invoice_number,
                        text: response.message,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Print Receipt',
                        cancelButtonText: 'Close'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            window.open(response.receipt_url, '_blank');
                        }
                    });
                }).fail(function(xhr) {
                    Swal.fire('Checkout failed', xhr.responseJSON?.message || 'Unable to complete sale.', 'error');
                });
            });

            addPaymentRow({
                method: 'cash',
                amount: '',
                reference: ''
            });
            renderCart();
            feather.replace();
        });
    </script>
@endsection

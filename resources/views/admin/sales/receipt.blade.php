@php
    $printMode = $printMode ?? false;
    $customerName = $sale->customer?->name ?? ($sale->meta['customer_label'] ?? 'Walk-in Customer');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sale->invoice_number }}</title>
    <link href="{{ asset('css/styles.css') }}" rel="stylesheet" />
    <style>
        body { background: #f4f6fb; }
        .receipt-wrapper { max-width: 900px; margin: 30px auto; }
        .receipt-card { background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .receipt-wrapper { margin: 0; max-width: 100%; }
            .receipt-card { box-shadow: none; border-radius: 0; }
        }
    </style>
</head>

<body @if ($printMode) onload="window.print()" @endif>
    <div class="receipt-wrapper px-3">
        <div class="receipt-card p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h2 class="mb-1">Sales Invoice</h2>
                    <div class="text-muted">Invoice: {{ $sale->invoice_number }}</div>
                    <div class="text-muted">Date: {{ optional($sale->sold_at)->format('d M Y h:i A') }}</div>
                </div>
                <div class="text-end">
                    <div class="fw-bold">POS System</div>
                    <div class="text-muted">Terminal Receipt</div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="fw-semibold mb-2">Customer</div>
                    <div>{{ $customerName }}</div>
                    @if ($sale->customer?->phone)
                        <div class="text-muted">{{ $sale->customer->phone }}</div>
                    @endif
                    @if ($sale->customer?->address)
                        <div class="text-muted">{{ $sale->customer->address }}</div>
                    @endif
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="fw-semibold mb-2">Handled By</div>
                    <div>{{ $sale->user?->name ?? 'System User' }}</div>
                    <div class="text-muted">Status: {{ ucfirst($sale->status) }}</div>
                    @if ($sale->promotion)
                        <div class="text-muted">Promotion: {{ $sale->promotion->name }}</div>
                    @endif
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Discount</th>
                            <th>Tax</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->sku }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>৳{{ number_format($item->unit_price, 2) }}</td>
                                <td>৳{{ number_format($item->line_discount, 2) }}</td>
                                <td>৳{{ number_format($item->tax_amount, 2) }}</td>
                                <td>৳{{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="fw-semibold mb-2">Payment Breakdown</div>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sale->payments as $payment)
                                <tr>
                                    <td>{{ ucfirst($payment->method) }}</td>
                                    <td>{{ $payment->reference ?: '-' }}</td>
                                    <td>৳{{ number_format($payment->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted">No payment recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if ($sale->notes)
                        <div class="fw-semibold mb-1">Notes</div>
                        <div class="text-muted">{{ $sale->notes }}</div>
                    @endif
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th class="ps-0">Subtotal</th>
                            <td class="text-end">৳{{ number_format($sale->subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0">Discount</th>
                            <td class="text-end">৳{{ number_format($sale->discount_total, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0">Tax</th>
                            <td class="text-end">৳{{ number_format($sale->tax_total, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0">Grand Total</th>
                            <td class="text-end fw-bold">৳{{ number_format($sale->total, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0">Paid</th>
                            <td class="text-end">৳{{ number_format($sale->paid_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0">Due</th>
                            <td class="text-end">৳{{ number_format($sale->due_amount, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 no-print">
                <a href="{{ route('sales.index') }}" class="btn btn-outline-primary">Back to POS</a>
                <button onclick="window.print()" class="btn btn-dark">Print Receipt</button>
            </div>
        </div>
    </div>
</body>

</html>

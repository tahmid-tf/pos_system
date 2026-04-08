@extends('layouts.admin')

@section('content')
    <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-10">
        <div class="container-xl px-4">
            <div class="page-header-content pt-4">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto mt-4">
                        <h1 class="page-header-title">
                            <div class="page-header-icon">
                                <i data-feather="home"></i>
                            </div>
                            Business Dashboard
                        </h1>
                        <div class="page-header-subtitle">
                            Live business performance, inventory watchlist, and operational activity in one place.
                        </div>
                    </div>
                    <div class="col-12 col-xl-auto mt-4">
                        <div class="badge bg-white text-primary p-3">
                            Snapshot Updated: {{ now()->format('d M Y h:i A') }}
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
                        <div class="small text-muted">Monthly Revenue</div>
                        <div class="h3 mb-0">BDT {{ number_format($stats['monthly_revenue'], 2) }}</div>
                        <div class="small text-success mt-2">Collected: BDT {{ number_format($stats['monthly_collected'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-success h-100">
                    <div class="card-body">
                        <div class="small text-muted">Today's Sales</div>
                        <div class="h3 mb-0">{{ number_format($stats['today_invoices']) }}</div>
                        <div class="small text-muted mt-2">Revenue: BDT {{ number_format($stats['today_revenue'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-warning h-100">
                    <div class="card-body">
                        <div class="small text-muted">Low Stock Products</div>
                        <div class="h3 mb-0">{{ number_format($stats['low_stock']) }}</div>
                        <div class="small text-muted mt-2">Units in stock: {{ number_format($stats['stock_units']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start-lg border-start-danger h-100">
                    <div class="card-body">
                        <div class="small text-muted">Pending Alerts</div>
                        <div class="h3 mb-0">{{ number_format($stats['unread_notifications']) }}</div>
                        <div class="small text-muted mt-2">Supplier due: BDT {{ number_format($stats['supplier_due'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xxl-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Sales Trend</span>
                        <span class="badge bg-primary-soft text-primary">Last 7 Days</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="height: 18rem;">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Recent Sales</span>
                                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary">Open POS</a>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    @forelse ($recentSales as $sale)
                                        <div class="list-group-item px-0">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="fw-semibold">{{ $sale->invoice_number }}</div>
                                                    <div class="small text-muted">
                                                        {{ $sale->customer?->name ?? 'Walk-in Customer' }} ·
                                                        {{ optional($sale->sold_at)->format('d M Y h:i A') }}
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fw-semibold">BDT {{ number_format($sale->total, 2) }}</div>
                                                    <span class="badge bg-{{ $sale->status === 'paid' ? 'success' : ($sale->status === 'partial' ? 'warning text-dark' : 'secondary') }}">
                                                        {{ ucfirst($sale->status) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center text-muted py-5">No recent sales found.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Low Stock Watchlist</span>
                                <a href="{{ route('inventory.alerts') }}" class="btn btn-sm btn-outline-warning">Review</a>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    @forelse ($lowStockProducts as $product)
                                        <div class="list-group-item px-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="fw-semibold">{{ $product->name }}</div>
                                                    <div class="small text-muted">{{ $product->sku }}</div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fw-semibold text-warning">{{ $product->current_stock }} units</div>
                                                    <div class="small text-muted">Threshold {{ $product->low_stock_threshold }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center text-muted py-5">Inventory levels look healthy.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">Recent Activity</div>
                    <div class="card-body">
                        <div class="timeline timeline-xs">
                            @forelse ($recentActivities as $activity)
                                <div class="timeline-item">
                                    <div class="timeline-item-marker">
                                        <div class="timeline-item-marker-text">
                                            {{ $activity->created_at->diffForHumans() }}
                                        </div>
                                        <div class="timeline-item-marker-indicator bg-primary"></div>
                                    </div>
                                    <div class="timeline-item-content">
                                        <div class="fw-semibold">{{ $activity->description }}</div>
                                        <div class="small text-muted">
                                            {{ ucfirst(str_replace('_', ' ', $activity->module)) }}
                                            @if ($activity->user)
                                                · {{ $activity->user->name }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">No activity has been logged yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-4">
                <div class="card mb-4">
                    <div class="card-header">Business Snapshot</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Products</div>
                                    <div class="h4 mb-0">{{ number_format($stats['products']) }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Customers</div>
                                    <div class="h4 mb-0">{{ number_format($stats['customers']) }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Today's Due</div>
                                    <div class="h4 mb-0">BDT {{ number_format($stats['today_due'], 2) }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted">Pending POs</div>
                                    <div class="h4 mb-0">{{ number_format($stats['pending_purchase_orders']) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">Revenue Mix</div>
                    <div class="card-body">
                        <div class="chart-pie mb-4" style="height: 16rem;">
                            <canvas id="revenueMixChart"></canvas>
                        </div>
                        <div class="small text-muted">
                            This compares monthly collected cash, outstanding customer dues, and supplier dues.
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Latest Notifications</span>
                        <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-primary">Open</a>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @forelse ($recentNotifications as $notification)
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-semibold">{{ $notification->title }}</div>
                                            <div class="small text-muted">{{ $notification->message }}</div>
                                        </div>
                                        <span class="badge {{ $notification->read_at ? 'bg-light text-dark' : 'bg-primary' }}">
                                            {{ $notification->read_at ? 'Read' : 'New' }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">No notifications available.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            const salesTrendCtx = document.getElementById('salesTrendChart');
            const revenueMixCtx = document.getElementById('revenueMixChart');

            if (salesTrendCtx) {
                new Chart(salesTrendCtx, {
                    type: 'line',
                    data: {
                        labels: @json($salesTrendLabels),
                        datasets: [{
                            label: 'Sales',
                            data: @json($salesTrendValues),
                            borderColor: '#0061f2',
                            backgroundColor: 'rgba(0, 97, 242, 0.08)',
                            pointBackgroundColor: '#0061f2',
                            pointBorderColor: '#ffffff',
                            pointRadius: 4,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        legend: {
                            display: false
                        },
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                    callback: function(value) {
                                        return 'BDT ' + Number(value).toFixed(0);
                                    }
                                },
                                gridLines: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            }],
                            xAxes: [{
                                gridLines: {
                                    display: false
                                }
                            }]
                        }
                    }
                });
            }

            if (revenueMixCtx) {
                new Chart(revenueMixCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Collected', 'Customer Due', 'Supplier Due'],
                        datasets: [{
                            data: [
                                {{ $stats['monthly_collected'] }},
                                {{ $stats['today_due'] }},
                                {{ $stats['supplier_due'] }}
                            ],
                            backgroundColor: ['#00ac69', '#f4a100', '#e81500'],
                            hoverBackgroundColor: ['#139c5e', '#d88f00', '#c91500'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        cutoutPercentage: 72,
                        legend: {
                            position: 'bottom'
                        }
                    }
                });
            }
        });
    </script>
@endsection

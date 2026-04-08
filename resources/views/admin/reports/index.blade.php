@extends('layouts.admin')

@php
    $ledgerOptions = $ledgers->map(function ($ledger) {
        return [
            'id' => $ledger->id,
            'name' => $ledger->name,
            'type' => $ledger->type,
            'code' => $ledger->code,
        ];
    })->values();
@endphp

@section('content')
    <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-10">
        <div class="container-xl px-4">
            <div class="page-header-content pt-4">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto mt-4">
                        <h1 class="page-header-title">
                            <div class="page-header-icon"><i data-feather="bar-chart-2"></i></div>
                            Reporting & Basic Accounting
                        </h1>
                        <div class="page-header-subtitle">
                            Generate async business reports, export them, and manage simple accounting from one module.
                        </div>
                    </div>
                    <div class="col-12 col-xl-auto mt-4">
                        <div class="badge bg-white text-primary p-3">
                            Financial Date: {{ now()->format('d M Y h:i A') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container-xl px-4 mt-n10">
        <div class="card mb-4">
            <div class="card-header">Generate Report</div>
            <div class="card-body">
                <form id="reportFilterForm">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Report Type</label>
                            <select class="form-control" name="report_type" id="reportType">
                                <option value="sales">Sales Report</option>
                                <option value="inventory">Inventory Report</option>
                                <option value="profit_loss">Profit &amp; Loss</option>
                                <option value="cash_flow">Cash Flow</option>
                                <option value="custom">Custom Report</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Period</label>
                            <select class="form-control" name="period" id="reportPeriod">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly" selected>Monthly</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="startDate"
                                value="{{ $defaultFilters['start_date'] }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" id="endDate"
                                value="{{ $defaultFilters['end_date'] }}">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button class="btn btn-primary" type="submit">Load Report</button>
                        </div>
                    </div>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-success" type="button" id="exportExcelBtn">Export Excel</button>
                        <button class="btn btn-outline-danger" type="button" id="exportPdfBtn">Export PDF</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row" id="reportSummaryCards"></div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span id="reportChartTitle">Report Chart</span>
                <small class="text-muted" id="reportRangeLabel"></small>
            </div>
            <div class="card-body">
                <div class="report-chart-wrapper">
                    <canvas id="reportChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header" id="reportTableTitle">Report Details</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead id="reportTableHead"></thead>
                        <tbody id="reportTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4 d-none" id="customSectionsCard">
            <div class="card-header">Custom Report Sections</div>
            <div class="card-body" id="customSectionsBody"></div>
        </div>

        <div class="row">
            <div class="col-xl-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">Create Ledger</div>
                    <div class="card-body">
                        <form id="ledgerForm">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Ledger Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ledger Code</label>
                                <input type="text" name="code" class="form-control" placeholder="office_rent" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select class="form-control" name="type">
                                    <option value="asset">Asset</option>
                                    <option value="liability">Liability</option>
                                    <option value="equity">Equity</option>
                                    <option value="income">Income</option>
                                    <option value="expense">Expense</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Save Ledger</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">Track Income &amp; Expenses</div>
                    <div class="card-body">
                        <form id="transactionForm">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Transaction Type</label>
                                <select class="form-control" name="transaction_type" id="transactionType">
                                    <option value="income">Income</option>
                                    <option value="expense">Expense</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ledger</label>
                                <select class="form-control" name="ledger_id" id="transactionLedger"></select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" name="amount" class="form-control" min="0.01" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Entry Date</label>
                                <input type="datetime-local" name="entry_date" class="form-control"
                                    value="{{ now()->format('Y-m-d\TH:i') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reference</label>
                                <input type="text" name="reference" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            <button class="btn btn-success w-100" type="submit">Record Entry</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">Balance Sheet Snapshot</div>
                    <div class="card-body">
                        <div class="border rounded p-3 bg-light mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Assets</span>
                                <strong id="assetsBalanceLabel">BDT 0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Liabilities</span>
                                <strong id="liabilitiesBalanceLabel">BDT 0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Equity</span>
                                <strong id="equityBalanceLabel">BDT 0.00</strong>
                            </div>
                        </div>
                        <div class="border rounded p-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Income</span>
                                <strong id="incomeBalanceLabel">BDT 0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Total Expenses</span>
                                <strong id="expenseBalanceLabel">BDT 0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Ledger Management</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Ledger</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody id="ledgerTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <style>
        .report-chart-wrapper {
            position: relative;
            height: 320px;
            min-height: 320px;
        }

        .report-chart-wrapper canvas {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }

        @media (max-width: 767.98px) {
            .report-chart-wrapper {
                height: 260px;
                min-height: 260px;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script>
        $(function() {
            const csrfToken = '{{ csrf_token() }}';
            const routes = {
                reportData: '{{ route('reports.data') }}',
                exportExcel: '{{ route('reports.export.excel') }}',
                accountingSnapshot: '{{ route('reports.accountingSnapshot') }}',
                ledgerStore: '{{ route('reports.ledgers.store') }}',
                transactionStore: '{{ route('reports.transactions.store') }}'
            };
            const allLedgers = @json($ledgerOptions);

            let activeReport = null;
            let reportChart = null;

            function formatMoney(value) {
                return 'BDT ' + Number(value || 0).toFixed(2);
            }

            function formatValue(item) {
                if (item.format === 'number') {
                    return Number(item.value || 0).toLocaleString();
                }

                return formatMoney(item.value);
            }

            function syncDateInputs() {
                const period = $('#reportPeriod').val();
                const today = new Date();
                let start = new Date(today);
                let end = new Date(today);

                if (period === 'daily') {
                    start = end = today;
                } else if (period === 'weekly') {
                    const day = today.getDay();
                    const diff = day === 0 ? 6 : day - 1;
                    start.setDate(today.getDate() - diff);
                    end = new Date(start);
                    end.setDate(start.getDate() + 6);
                } else if (period === 'monthly') {
                    start = new Date(today.getFullYear(), today.getMonth(), 1);
                    end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                }

                if (period !== 'custom') {
                    $('#startDate').val(start.toISOString().slice(0, 10));
                    $('#endDate').val(end.toISOString().slice(0, 10));
                }
            }

            function buildSummaryCards(summary) {
                let html = '';

                $.each(summary || [], function(index, item) {
                    const borderClasses = ['primary', 'success', 'warning', 'info'];
                    const borderClass = borderClasses[index % borderClasses.length];

                    html += `
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-start-lg border-start-${borderClass} h-100">
                                <div class="card-body">
                                    <div class="small text-muted">${item.label}</div>
                                    <div class="h3 mb-0">${formatValue(item)}</div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                $('#reportSummaryCards').html(html);
            }

            function renderTable(table) {
                $('#reportTableTitle').text(table.title || 'Report Details');

                let headHtml = '<tr>';
                $.each(table.columns || [], function(_, column) {
                    headHtml += `<th>${column}</th>`;
                });
                headHtml += '</tr>';

                let bodyHtml = '';
                $.each(table.rows || [], function(_, row) {
                    bodyHtml += '<tr>';

                    $.each(row, function(columnIndex, cell) {
                        const isAmountColumn = (table.columns[columnIndex] || '').toLowerCase().indexOf('amount') !== -1
                            || (table.columns[columnIndex] || '').toLowerCase().indexOf('sales') !== -1
                            || (table.columns[columnIndex] || '').toLowerCase().indexOf('value') !== -1;

                        bodyHtml += `<td>${typeof cell === 'number' && isAmountColumn ? formatMoney(cell) : cell}</td>`;
                    });

                    bodyHtml += '</tr>';
                });

                if (!bodyHtml) {
                    bodyHtml = `<tr><td colspan="${table.columns.length}" class="text-center text-muted py-4">No data found for this report</td></tr>`;
                }

                $('#reportTableHead').html(headHtml);
                $('#reportTableBody').html(bodyHtml);
            }

            function renderCustomSections(sections) {
                if (!sections || !sections.length) {
                    $('#customSectionsCard').addClass('d-none');
                    $('#customSectionsBody').empty();
                    return;
                }

                let html = '';

                $.each(sections, function(_, section) {
                    html += `
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">${section.title}</h6>
                                <span class="badge bg-light text-dark">${section.type.replace('_', ' ')}</span>
                            </div>
                            <div class="row g-3">
                                ${(section.summary || []).map(function(item) {
                                    return `
                                        <div class="col-md-3">
                                            <div class="small text-muted">${item.label}</div>
                                            <div class="fw-bold">${formatValue(item)}</div>
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    `;
                });

                $('#customSectionsBody').html(html);
                $('#customSectionsCard').removeClass('d-none');
            }

            function renderChart(chart) {
                $('#reportChartTitle').text(chart.label || 'Report Chart');

                const canvas = document.getElementById('reportChart');
                const context = canvas.getContext('2d');

                if (reportChart) {
                    reportChart.destroy();
                }

                const datasets = $.map(chart.datasets || [], function(dataset, index) {
                    const colors = ['#0061f2', '#00ac69', '#f4a100', '#e81500'];
                    return {
                        label: dataset.label,
                        data: dataset.data,
                        backgroundColor: colors[index % colors.length],
                        borderColor: colors[index % colors.length],
                        borderWidth: 2,
                        pointRadius: 3,
                        lineTension: 0.2,
                        fill: false
                    };
                });

                reportChart = new Chart(context, {
                    type: datasets.length > 1 ? 'line' : 'bar',
                    data: {
                        labels: chart.labels || [],
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        }
                    }
                });
            }

            function renderReport(payload) {
                activeReport = payload.report;
                buildSummaryCards(payload.report.summary || []);
                renderChart(payload.report.chart || {});
                renderTable(payload.report.table || {
                    columns: [],
                    rows: []
                });
                renderCustomSections(payload.report.sections || []);
                $('#reportRangeLabel').text(payload.filters.start_date + ' to ' + payload.filters.end_date);
            }

            function loadReport() {
                $.get(routes.reportData, $('#reportFilterForm').serialize())
                    .done(function(response) {
                        renderReport(response);
                    })
                    .fail(function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Unable to generate report.', 'error');
                    });
            }

            function refreshLedgerOptions() {
                const transactionType = $('#transactionType').val();
                const matchedType = transactionType === 'income' ? 'income' : 'expense';
                const options = $.map(allLedgers, function(ledger) {
                    if (ledger.type !== matchedType) {
                        return null;
                    }

                    return `<option value="${ledger.id}">${ledger.name}</option>`;
                });

                $('#transactionLedger').html(options.join(''));
            }

            function renderAccountingSnapshot(snapshot) {
                $('#assetsBalanceLabel').text(formatMoney(snapshot.balance_sheet.assets));
                $('#liabilitiesBalanceLabel').text(formatMoney(snapshot.balance_sheet.liabilities));
                $('#equityBalanceLabel').text(formatMoney(snapshot.balance_sheet.equity));
                $('#incomeBalanceLabel').text(formatMoney(snapshot.profit_and_loss.income));
                $('#expenseBalanceLabel').text(formatMoney(snapshot.profit_and_loss.expenses));

                let html = '';

                $.each(snapshot.ledgers || [], function(_, ledger) {
                    html += `
                        <tr>
                            <td>${ledger.name}</td>
                            <td>${ledger.code}</td>
                            <td>${ledger.type.replace('_', ' ')}</td>
                            <td>${formatMoney(ledger.balance)}</td>
                        </tr>
                    `;
                });

                $('#ledgerTableBody').html(html || '<tr><td colspan="4" class="text-center text-muted py-4">No ledgers available</td></tr>');
            }

            function loadAccountingSnapshot() {
                $.get(routes.accountingSnapshot).done(function(response) {
                    renderAccountingSnapshot(response);
                });
            }

            function exportToExcel() {
                if (!activeReport) {
                    Swal.fire('No report', 'Generate a report before exporting.', 'warning');
                    return;
                }

                const query = $('#reportFilterForm').serialize();
                window.location.href = `${routes.exportExcel}?${query}`;
            }

            function exportToPdf() {
                if (!activeReport) {
                    Swal.fire('No report', 'Generate a report before exporting.', 'warning');
                    return;
                }

                const {
                    jsPDF
                } = window.jspdf;
                const doc = new jsPDF();

                doc.setFontSize(16);
                doc.text(activeReport.title || 'Report', 14, 18);
                doc.setFontSize(10);
                doc.text($('#reportRangeLabel').text(), 14, 25);

                let y = 34;
                $.each(activeReport.summary || [], function(_, item) {
                    doc.text(`${item.label}: ${item.format === 'number' ? item.value : formatMoney(item.value)}`, 14, y);
                    y += 6;
                });

                doc.autoTable({
                    startY: y + 4,
                    head: [activeReport.table.columns || []],
                    body: activeReport.table.rows || [],
                    styles: {
                        fontSize: 9
                    }
                });

                doc.save((activeReport.type || 'report') + '-export.pdf');
            }

            $('#reportPeriod').on('change', syncDateInputs);

            $('#reportFilterForm').on('submit', function(e) {
                e.preventDefault();
                loadReport();
            });

            $('#ledgerForm').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: routes.ledgerStore,
                    method: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                }).done(function(response) {
                    allLedgers.push(response.ledger);
                    refreshLedgerOptions();
                    loadAccountingSnapshot();
                    $('#ledgerForm')[0].reset();
                    Swal.fire('Saved', response.message, 'success');
                }).fail(function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to create ledger.', 'error');
                });
            });

            $('#transactionType').on('change', refreshLedgerOptions);

            $('#transactionForm').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: routes.transactionStore,
                    method: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                }).done(function(response) {
                    $('#transactionForm')[0].reset();
                    $('input[name="entry_date"]').val(new Date().toISOString().slice(0, 16));
                    refreshLedgerOptions();
                    loadAccountingSnapshot();
                    loadReport();
                    Swal.fire('Saved', response.message, 'success');
                }).fail(function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to record entry.', 'error');
                });
            });

            $('#exportExcelBtn').on('click', exportToExcel);
            $('#exportPdfBtn').on('click', exportToPdf);

            syncDateInputs();
            refreshLedgerOptions();
            loadAccountingSnapshot();
            loadReport();
            feather.replace();
        });
    </script>
@endsection

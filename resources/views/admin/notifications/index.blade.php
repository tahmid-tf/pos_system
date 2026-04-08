@extends('layouts.admin')

@section('content')
    <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-10">
        <div class="container-xl px-4">
            <div class="page-header-content pt-4">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto mt-4">
                        <h1 class="page-header-title">
                            <div class="page-header-icon">
                                <i data-feather="bell"></i>
                            </div>
                            Notifications
                        </h1>
                        <div class="page-header-subtitle">
                            Monitor low stock alerts, new order activity, and payment reminders in real time.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container-xl px-4 mt-n10">
        <div class="row">
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-start-lg border-start-warning h-100">
                    <div class="card-body">
                        <div class="small text-muted">Low Stock Alerts</div>
                        <div class="h3 mb-0" id="lowStockCountLabel">0</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-start-lg border-start-info h-100">
                    <div class="card-body">
                        <div class="small text-muted">New Order Alerts</div>
                        <div class="h3 mb-0" id="newOrderCountLabel">0</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-start-lg border-start-danger h-100">
                    <div class="card-body">
                        <div class="small text-muted">Payment Reminders</div>
                        <div class="h3 mb-0" id="paymentReminderCountLabel">0</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Notification Feed</span>
                <div class="d-flex gap-2">
                    <select class="form-control" id="notificationTypeFilter" style="width: 220px;">
                        <option value="">All Types</option>
                        <option value="low_stock">Low Stock</option>
                        <option value="new_order">New Order</option>
                        <option value="payment_reminder">Payment Reminder</option>
                    </select>
                    <div class="form-check align-self-center ms-2">
                        <input class="form-check-input" type="checkbox" id="unreadOnlyFilter">
                        <label class="form-check-label" for="unreadOnlyFilter">Unread only</label>
                    </div>
                    <button class="btn btn-outline-primary" type="button" id="markAllReadPageBtn">Mark all read</button>
                </div>
            </div>
            <div class="card-body">
                <div id="notificationsList"></div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="small text-muted" id="notificationsPaginationLabel">Showing 0 of 0</div>
                    <div class="btn-group">
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="notificationsPrevBtn">Previous</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="notificationsNextBtn">Next</button>
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
                feed: '{{ route('notifications.feed') }}',
                list: '{{ route('notifications.list') }}',
                markAllRead: '{{ route('notifications.markAllRead') }}',
                markReadBase: '{{ url('/notifications') }}'
            };
            const csrfToken = '{{ csrf_token() }}';
            let pagination = {
                current_page: 1,
                last_page: 1
            };

            function iconMeta(type) {
                if (type === 'low_stock') {
                    return {
                        icon: 'alert-triangle',
                        className: 'bg-warning'
                    };
                }

                if (type === 'payment_reminder') {
                    return {
                        icon: 'credit-card',
                        className: 'bg-danger'
                    };
                }

                return {
                    icon: 'shopping-cart',
                    className: 'bg-info'
                };
            }

            function updateCounters(feed) {
                $('#lowStockCountLabel').text(feed.counts?.low_stock || 0);
                $('#newOrderCountLabel').text(feed.counts?.new_order || 0);
                $('#paymentReminderCountLabel').text(feed.counts?.payment_reminder || 0);
            }

            function renderNotifications(items) {
                let html = '';

                $.each(items, function(_, item) {
                    const meta = iconMeta(item.type);

                    html += `
                        <div class="card border mb-3 ${item.is_read ? '' : 'border-primary'}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div class="d-flex">
                                        <div class="icon-stack ${meta.className} text-white me-3">
                                            <i data-feather="${meta.icon}"></i>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h6 class="mb-0">${item.title}</h6>
                                                ${item.is_read ? '<span class="badge bg-light text-dark">Read</span>' : '<span class="badge bg-primary">Unread</span>'}
                                            </div>
                                            <div class="small text-muted mb-2">${item.created_at || '-'}</div>
                                            <div>${item.message}</div>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm mark-notification-read-btn" type="button" data-id="${item.id}" ${item.is_read ? 'disabled' : ''}>
                                        Mark read
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });

                $('#notificationsList').html(html || '<div class="text-center text-muted py-5">No notifications found for the selected filters.</div>');
                feather.replace();
            }

            function updatePagination(meta) {
                pagination = meta;
                $('#notificationsPaginationLabel').text(`Showing ${meta.from || 0} to ${meta.to || 0} of ${meta.total || 0}`);
                $('#notificationsPrevBtn').prop('disabled', meta.current_page <= 1);
                $('#notificationsNextBtn').prop('disabled', meta.current_page >= meta.last_page);
            }

            function currentFilters(page) {
                return {
                    page: page || pagination.current_page || 1,
                    type: $('#notificationTypeFilter').val(),
                    unread_only: $('#unreadOnlyFilter').is(':checked') ? 1 : 0
                };
            }

            function loadFeed() {
                $.get(routes.feed).done(updateCounters);
            }

            function loadNotifications(page) {
                $.get(routes.list, currentFilters(page)).done(function(response) {
                    renderNotifications(response.data || []);
                    updatePagination(response.pagination || {
                        current_page: 1,
                        last_page: 1
                    });
                }).fail(function() {
                    Swal.fire('Error', 'Failed to load notifications.', 'error');
                });
            }

            $('#notificationTypeFilter, #unreadOnlyFilter').on('change', function() {
                loadNotifications(1);
            });

            $('#notificationsPrevBtn').on('click', function() {
                if (pagination.current_page > 1) {
                    loadNotifications(pagination.current_page - 1);
                }
            });

            $('#notificationsNextBtn').on('click', function() {
                if (pagination.current_page < pagination.last_page) {
                    loadNotifications(pagination.current_page + 1);
                }
            });

            $('#markAllReadPageBtn').on('click', function() {
                $.ajax({
                    url: routes.markAllRead,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                }).done(function(response) {
                    loadFeed();
                    loadNotifications(1);
                    Swal.fire('Success', response.message, 'success');
                }).fail(function() {
                    Swal.fire('Error', 'Failed to update notifications.', 'error');
                });
            });

            $(document).on('click', '.mark-notification-read-btn', function() {
                const notificationId = $(this).data('id');

                $.ajax({
                    url: `${routes.markReadBase}/${notificationId}/read`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                }).done(function() {
                    loadFeed();
                    loadNotifications();
                }).fail(function() {
                    Swal.fire('Error', 'Failed to update notification.', 'error');
                });
            });

            loadFeed();
            loadNotifications(1);
        });
    </script>
@endsection

@php
    $linkClass = function (...$patterns) {
        return request()->routeIs(...$patterns) ? 'nav-link active' : 'nav-link';
    };
@endphp

<div class="sidenav-menu-heading">Core</div>
<a class="{{ $linkClass('admin.dashboard') }}" href="{{ route('admin.dashboard') }}">
    <div class="nav-link-icon"><i data-feather="package"></i></div>
    Dashboard
</a>

<div class="sidenav-menu-heading">Catalog</div>
<a class="{{ $linkClass('products.*') }}" href="{{ route('products.index') }}">
    <div class="nav-link-icon"><i data-feather="package"></i></div>
    Products
</a>
<a class="{{ $linkClass('categories.*') }}" href="{{ route('categories.index') }}">
    <div class="nav-link-icon"><i data-feather="grid"></i></div>
    Categories
</a>

<div class="sidenav-menu-heading">Inventory</div>
<a class="{{ $linkClass('inventory.stockLevels') }}" href="{{ route('inventory.stockLevels') }}">
    <div class="nav-link-icon"><i data-feather="archive"></i></div>
    Stock Levels
</a>
<a class="{{ $linkClass('inventory.movements') }}" href="{{ route('inventory.movements') }}">
    <div class="nav-link-icon"><i data-feather="repeat"></i></div>
    Stock Movements
</a>
<a class="{{ $linkClass('inventory.alerts') }}" href="{{ route('inventory.alerts') }}">
    <div class="nav-link-icon"><i data-feather="alert-triangle"></i></div>
    Low Stock Alerts
</a>

<div class="sidenav-menu-heading">Sales</div>
<a class="{{ $linkClass('sales.*') }}" href="{{ route('sales.index') }}">
    <div class="nav-link-icon"><i data-feather="shopping-cart"></i></div>
    POS Terminal
</a>
<a class="{{ $linkClass('customers.*') }}" href="{{ route('customers.index') }}">
    <div class="nav-link-icon"><i data-feather="users"></i></div>
    Customers
</a>

<div class="sidenav-menu-heading">Procurement</div>
<a class="{{ $linkClass('suppliers.*') }}" href="{{ route('suppliers.index') }}">
    <div class="nav-link-icon"><i data-feather="truck"></i></div>
    Suppliers
</a>
<a class="{{ $linkClass('purchaseOrders.*') }}" href="{{ route('purchaseOrders.index') }}">
    <div class="nav-link-icon"><i data-feather="file-text"></i></div>
    Purchase Orders
</a>

<div class="sidenav-menu-heading">Insights</div>
<a class="{{ $linkClass('reports.*') }}" href="{{ route('reports.index') }}">
    <div class="nav-link-icon"><i data-feather="bar-chart-2"></i></div>
    Reports & Accounting
</a>
<a class="{{ $linkClass('notifications.*') }}" href="{{ route('notifications.index') }}">
    <div class="nav-link-icon"><i data-feather="bell"></i></div>
    Notifications
</a>
<a class="{{ $linkClass('auditLogs.*') }}" href="{{ route('auditLogs.index') }}">
    <div class="nav-link-icon"><i data-feather="shield"></i></div>
    Audit Logs
</a>

<div class="sidenav-menu-heading">Catalog</div>
<a class="nav-link" href="{{ route('products.index') }}">
    <div class="nav-link-icon"><i data-feather="package"></i></div>
    Products
</a>
<a class="nav-link" href="{{ route('categories.index') }}">
    <div class="nav-link-icon"><i data-feather="grid"></i></div>
    Categories
</a>

<div class="sidenav-menu-heading">Inventory</div>
<a class="nav-link" href="{{ route('inventory.stockLevels') }}">
    <div class="nav-link-icon"><i data-feather="archive"></i></div>
    Stock Levels
</a>
<a class="nav-link" href="{{ route('inventory.movements') }}">
    <div class="nav-link-icon"><i data-feather="repeat"></i></div>
    Stock Movements
</a>
<a class="nav-link" href="{{ route('inventory.alerts') }}">
    <div class="nav-link-icon"><i data-feather="alert-triangle"></i></div>
    Low Stock Alerts
</a>

<div class="sidenav-menu-heading">Sales</div>
<a class="nav-link" href="{{ route('sales.index') }}">
    <div class="nav-link-icon"><i data-feather="shopping-cart"></i></div>
    POS Terminal
</a>
<a class="nav-link" href="{{ route('customers.index') }}">
    <div class="nav-link-icon"><i data-feather="users"></i></div>
    Customers
</a>

<div class="sidenav-menu-heading">Procurement</div>
<a class="nav-link" href="{{ route('suppliers.index') }}">
    <div class="nav-link-icon"><i data-feather="truck"></i></div>
    Suppliers
</a>
<a class="nav-link" href="{{ route('purchaseOrders.index') }}">
    <div class="nav-link-icon"><i data-feather="file-text"></i></div>
    Purchase Orders
</a>

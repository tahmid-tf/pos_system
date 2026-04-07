  <!-- Sidenav Menu Heading (Core)-->
  <div class="sidenav-menu-heading">Core</div>
  <!-- Sidenav Accordion (Dashboard)-->
  <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseDashboards"
      aria-expanded="false" aria-controls="collapseDashboards">
      <div class="nav-link-icon"><i data-feather="activity"></i></div>
      Dashboards
      <div class="sidenav-collapse-arrow">
          <i class="fas fa-angle-down"></i>
      </div>
  </a>
  <div class="collapse" id="collapseDashboards" data-bs-parent="#accordionSidenav">
      <nav class="sidenav-menu-nested nav accordion" id="accordionSidenavPages">
          <a class="nav-link" href="{{ route('products.index') }}">Products</a>
          <a class="nav-link" href="{{ route('categories.index') }}">Categories</a>

      </nav>
  </div>


  <!-- Sidenav Heading (Addons)-->
  <div class="sidenav-menu-heading">Products</div>
  <!-- Sidenav Link (Charts)-->
  <a class="nav-link" href="{{ route('products.index') }}">
      <div class="nav-link-icon"><i data-feather="bar-chart"></i></div>
      Products
  </a>
  <!-- Sidenav Link (Tables)-->
  <a class="nav-link" href="{{ route('categories.index') }}">
      <div class="nav-link-icon"><i data-feather="filter"></i></div>
      Categories
  </a>

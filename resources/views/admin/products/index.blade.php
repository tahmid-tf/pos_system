@extends('layouts.admin')

@section('content')
    <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-10">
        <div class="container-xl px-4">
            <div class="page-header-content pt-4">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto mt-4">
                        <h1 class="page-header-title">
                            <div class="page-header-icon">
                                <i data-feather="activity"></i>
                            </div>
                            Dashboard
                        </h1>
                        <div class="page-header-subtitle">
                            Example dashboard overview and content summary
                        </div>
                    </div>

                    <div class="col-12 col-xl-auto mt-4">
                        <div class="input-group input-group-joined border-0" style="width: 16.5rem">
                            <span class="input-group-text"><i class="text-primary" data-feather="calendar"></i></span>
                            <input class="form-control ps-0 pointer" id="litepickerRangePlugin"
                                placeholder="Select date range..." />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container-xl px-4 mt-n10">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Product Management</span>
                <button class="btn btn-primary" id="addProductBtn">
                    + Add Product
                </button>
            </div>
            <div class="card-body" id="productTableWrapper">
                <table class="table table-striped" id="productTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th width="180">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="productModal">
        <div class="modal-dialog modal-lg">
            <form id="productForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="product_id">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body row">
                        <div class="col-md-6">
                            <label>Name</label>
                            <input type="text" name="name" id="name" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label>SKU</label>
                            <input type="text" name="sku" id="sku" class="form-control">
                        </div>

                        <div class="col-md-6 mt-2">
                            <label>Category</label>
                            <select name="category_id" id="category_id" class="form-control">
                                <option value="">Select</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mt-2">
                            <label>Price</label>
                            <input type="number" name="price" id="price" class="form-control">
                        </div>

                        <div class="col-md-6 mt-2">
                            <label>Cost Price</label>
                            <input type="number" name="cost_price" id="cost_price" class="form-control">
                        </div>

                        <div class="col-md-6 mt-2">
                            <label>Stock</label>
                            <input type="number" name="stock" id="stock" class="form-control">
                        </div>

                        <div class="col-md-6 mt-2">
                            <label>Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-6 mt-2">
                            <label>Image</label>
                            <input type="file" name="image" id="image" class="form-control">
                            <img id="preview" src="" width="80" class="mt-2 d-none">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="stockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stockModalTitle">Add Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="stock_product_id">
                    <input type="hidden" id="stock_action" value="add">

                    <input type="number" id="quantity" class="form-control mb-2" placeholder="Quantity">

                    <input type="text" id="reference" class="form-control mb-2" placeholder="Reference (optional)">

                    <textarea id="note" class="form-control" placeholder="Note"></textarea>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" id="saveStockBtn" type="button">Save</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('click', function(e) {
            const addStockButton = e.target.closest('.add-stock-btn');
            const deductStockButton = e.target.closest('.deduct-stock-btn');

            if (addStockButton) {
                let id = addStockButton.dataset.id;
                document.getElementById('stock_product_id').value = id;
                document.getElementById('stock_action').value = 'add';
                document.getElementById('stockModalTitle').textContent = 'Add Stock';
                document.getElementById('quantity').value = '';
                document.getElementById('reference').value = '';
                document.getElementById('note').value = '';

                let modal = new bootstrap.Modal(document.getElementById('stockModal'));
                modal.show();
            }

            if (deductStockButton) {
                let id = deductStockButton.dataset.id;
                document.getElementById('stock_product_id').value = id;
                document.getElementById('stock_action').value = 'deduct';
                document.getElementById('stockModalTitle').textContent = 'Deduct Stock';
                document.getElementById('quantity').value = '';
                document.getElementById('reference').value = '';
                document.getElementById('note').value = '';

                let modal = new bootstrap.Modal(document.getElementById('stockModal'));
                modal.show();
            }

            if (e.target.id === 'saveStockBtn') {
                const action = document.getElementById('stock_action').value;
                const route = action === 'deduct' ?
                    "{{ route('inventory.deductStock') }}" :
                    "{{ route('inventory.addStock') }}";

                fetch(route, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({
                            product_id: document.getElementById('stock_product_id').value,
                            quantity: document.getElementById('quantity').value,
                            reference: document.getElementById('reference').value,
                            note: document.getElementById('note').value
                        })
                    })
                    .then(res => res.json())
                    .then(async data => {
                        if (data.success) {
                            bootstrap.Modal.getInstance(document.getElementById('stockModal'))?.hide();
                            document.getElementById('quantity').value = '';
                            document.getElementById('reference').value = '';
                            document.getElementById('note').value = '';
                            document.dispatchEvent(new CustomEvent('stock:updated'));
                            Swal.fire('Success', data.message || 'Stock updated successfully', 'success');
                        } else {
                            Swal.fire('Error', data.message || 'Failed to add stock', 'error');
                        }
                    });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const productTableWrapper = document.getElementById('productTableWrapper');
            const productForm = document.getElementById('productForm');
            const productModalElement = document.getElementById('productModal');
            const productModal = new bootstrap.Modal(productModalElement);
            const preview = document.getElementById('preview');
            const productId = document.getElementById('product_id');
            const csrfToken = '{{ csrf_token() }}';
            let dataTableInstance = null;

            const routes = {
                index: '{{ route('products.index') }}',
                store: '{{ route('products.store') }}',
                edit: '{{ url('/products/edit') }}',
                update: '{{ url('/products/update') }}',
                destroy: '{{ url('/products/delete') }}'
            };

            function getTableMarkup(products) {
                return `
                    <table class="table table-striped" id="productTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th width="180">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${products.map((product) => `
                                    <tr>
                                        <td>${product.id}</td>
                                        <td>${product.image ? `<img src="/storage/${product.image}" width="50" class="img-fluid rounded">` : 'No Image'}</td>
                                        <td>${product.name ?? ''}</td>
                                        <td>${product.sku ?? ''}</td>
                                        <td>${product.category?.name ?? ''}</td>
                                        <td>${product.price ?? ''}</td>
                                        <td>${product.stock ?? ''}</td>
                                        <td>${product.status ? 'Active' : 'Inactive'}</td>
                                        <td>
                                            <button class="btn btn-datatable btn-icon btn-transparent-dark me-2 editBtn" data-id="${product.id}" type="button">
                                                <i data-feather="edit"></i>
                                            </button>
                                            <button class="btn btn-datatable btn-icon btn-transparent-dark deleteBtn" data-id="${product.id}" type="button">
                                                <i data-feather="trash-2"></i>
                                            </button>
                                            <button class="btn btn-datatable btn-icon btn-transparent-dark me-2 add-stock-btn" data-id="${product.id}" type="button" title="Add Stock">
                                                <i data-feather="plus-circle"></i>
                                            </button>
                                            <button class="btn btn-datatable btn-icon btn-transparent-dark deduct-stock-btn" data-id="${product.id}" type="button" title="Deduct Stock">
                                                <i data-feather="minus-circle"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                        </tbody>
                    </table>
                `;
            }

            function renderTable(products) {
                if (dataTableInstance) {
                    dataTableInstance.destroy();
                    dataTableInstance = null;
                }

                productTableWrapper.innerHTML = getTableMarkup(products);

                const productTable = document.getElementById('productTable');
                dataTableInstance = new simpleDatatables.DataTable(productTable);
                feather.replace();
            }

            async function loadProducts() {
                const response = await fetch(routes.index, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load products');
                }

                const products = await response.json();
                renderTable(products);
            }

            function resetForm() {
                productForm.reset();
                productId.value = '';
                preview.src = '';
                preview.classList.add('d-none');
            }

            document.getElementById('addProductBtn').addEventListener('click', function() {
                resetForm();
                productModal.show();
            });

            productForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const id = productId.value;
                const url = id ? `${routes.update}/${id}` : routes.store;
                const formData = new FormData(productForm);

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error('Failed to save product');
                    }

                    productModal.hide();
                    await loadProducts();
                    Swal.fire({
                        title: 'Success',
                        text: 'Saved successfully',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500 // auto-close after 1.5 seconds
                    });

                } catch (error) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Something went wrong',
                        icon: 'error',
                        showConfirmButton: false,
                        timer: 2000 // closes after 2 seconds
                    });

                }
            });

            productTableWrapper.addEventListener('click', async function(e) {
                const editButton = e.target.closest('.editBtn');
                const deleteButton = e.target.closest('.deleteBtn');

                if (editButton) {
                    const id = editButton.dataset.id;

                    try {
                        const response = await fetch(`${routes.edit}/${id}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Failed to load product');
                        }

                        const data = await response.json();

                        productId.value = data.id;
                        document.getElementById('name').value = data.name ?? '';
                        document.getElementById('sku').value = data.sku ?? '';
                        document.getElementById('category_id').value = data.category_id ?? '';
                        document.getElementById('price').value = data.price ?? '';
                        document.getElementById('cost_price').value = data.cost_price ?? '';
                        document.getElementById('stock').value = data.stock ?? '';
                        document.getElementById('status').value = data.status ?? 1;

                        if (data.image) {
                            preview.src = `/storage/${data.image}`;
                            preview.classList.remove('d-none');
                        } else {
                            preview.src = '';
                            preview.classList.add('d-none');
                        }

                        productModal.show();
                    } catch (error) {
                        Swal.fire('Error', 'Failed to load product', 'error');
                    }
                }

                if (deleteButton) {
                    const id = deleteButton.dataset.id;

                    const result = await Swal.fire({
                        title: 'Delete?',
                        icon: 'warning',
                        showCancelButton: true
                    });

                    if (!result.isConfirmed) {
                        return;
                    }

                    try {
                        const response = await fetch(`${routes.destroy}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Failed to delete product');
                        }

                        await loadProducts();
                        Swal.fire('Deleted!', '', 'success');
                    } catch (error) {
                        Swal.fire('Error', 'Failed to delete product', 'error');
                    }
                }
            });

            loadProducts().catch(() => {
                Swal.fire('Error', 'Failed to load products', 'error');
            });

            document.addEventListener('stock:updated', function() {
                loadProducts().catch(() => {
                    Swal.fire('Error', 'Failed to refresh products', 'error');
                });
            });
        });
    </script>
@endsection

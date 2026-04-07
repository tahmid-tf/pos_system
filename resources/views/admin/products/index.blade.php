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
            <div class="card-body">
                <table class="table table-bordered" id="productTable">
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
                            <th width="120">Action</th>
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
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
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
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {

            let table = $('#productTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: "{{ route('products.index') }}",
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'image',
                        render: function(data) {
                            return data ?
                                `<img src="/storage/${data}" width="50">` :
                                'No Image';
                        }
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'sku'
                    },
                    {
                        data: 'category.name'
                    },
                    {
                        data: 'price'
                    },
                    {
                        data: 'stock'
                    },
                    {
                        data: 'status',
                        render: data => data ? 'Active' : 'Inactive'
                    },
                    {
                        data: 'id',
                        render: function(id) {
                            return `
                        <button class="btn btn-sm btn-warning editBtn" data-id="${id}">Edit</button>
                        <button class="btn btn-sm btn-danger deleteBtn" data-id="${id}">Delete</button>
                    `;
                        }
                    }
                ]
            });

            $('#addProductBtn').click(function() {
                $('#productForm')[0].reset();
                $('#product_id').val('');
                $('#preview').addClass('d-none');
                $('#productModal').modal('show');
            });

            $('#productForm').submit(function(e) {
                e.preventDefault();

                let id = $('#product_id').val();
                let url = id ?
                    `/products/update/${id}` :
                    `/products/store`;

                let formData = new FormData(this);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function() {
                        $('#productModal').modal('hide');
                        table.ajax.reload();
                        Swal.fire('Success', 'Saved successfully', 'success');
                    }
                });
            });

            $(document).on('click', '.editBtn', function() {
                let id = $(this).data('id');

                $.get(`/products/edit/${id}`, function(data) {
                    $('#product_id').val(data.id);
                    $('#name').val(data.name);
                    $('#sku').val(data.sku);
                    $('#category_id').val(data.category_id);
                    $('#price').val(data.price);
                    $('#cost_price').val(data.cost_price);
                    $('#stock').val(data.stock);
                    $('#status').val(data.status);

                    if (data.image) {
                        $('#preview')
                            .attr('src', '/storage/' + data.image)
                            .removeClass('d-none');
                    }

                    $('#productModal').modal('show');
                });
            });

            $(document).on('click', '.deleteBtn', function() {
                let id = $(this).data('id');

                Swal.fire({
                    title: 'Delete?',
                    icon: 'warning',
                    showCancelButton: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/products/delete/${id}`,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function() {
                                table.ajax.reload();
                                Swal.fire('Deleted!', '', 'success');
                            }
                        });
                    }
                });
            });

        });
    </script>
@endsection

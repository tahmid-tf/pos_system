@extends('layouts.admin')

@section('content')
    <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-10">
        <div class="container-xl px-4">
            <div class="page-header-content pt-4">
                <div class="row align-items-center justify-content-between">
                    <div class="col-auto mt-4">
                        <h1 class="page-header-title">
                            <div class="page-header-icon">
                                <i data-feather="grid"></i>
                            </div>
                            Categories
                        </h1>
                        <div class="page-header-subtitle">
                            Manage your product categories from one clean, searchable list
                        </div>
                    </div>

                    <div class="col-12 col-xl-auto mt-4">
                        <button class="btn btn-primary" id="addCategoryBtn">
                            + Add Category
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container-xl px-4 mt-n10">
        <div class="card mb-4">
            <div class="card-header">Category Management</div>
            <div class="card-body" id="categoryTableWrapper">
                <table class="table table-striped" id="categoryTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th width="120">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="categoryModal">
        <div class="modal-dialog">
            <form id="categoryForm">
                @csrf
                <input type="hidden" id="category_id">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <label>Name</label>
                        <input type="text" id="name" name="name" class="form-control">
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
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
            const form = document.getElementById('categoryForm');
            const wrapper = document.getElementById('categoryTableWrapper');
            const categoryId = document.getElementById('category_id');
            const categoryName = document.getElementById('name');
            const csrf = '{{ csrf_token() }}';
            let dataTableInstance = null;

            const routes = {
                index: '{{ route('categories.index') }}',
                store: '{{ route('categories.store') }}',
                edit: '{{ url('/categories/edit') }}',
                update: '{{ url('/categories/update') }}',
                delete: '{{ url('/categories/delete') }}'
            };

            function getTableMarkup(categories) {
                return `
                    <table class="table table-striped" id="categoryTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th width="120">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${categories.map((category) => `
                                <tr>
                                    <td>${category.id}</td>
                                    <td>${category.name}</td>
                                    <td>
                                        <button class="btn btn-datatable btn-icon btn-transparent-dark me-2 edit" data-id="${category.id}" type="button">
                                            <i data-feather="edit"></i>
                                        </button>
                                        <button class="btn btn-datatable btn-icon btn-transparent-dark delete" data-id="${category.id}" type="button">
                                            <i data-feather="trash-2"></i>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }

            function render(categories) {
                if (dataTableInstance) {
                    dataTableInstance.destroy();
                    dataTableInstance = null;
                }

                wrapper.innerHTML = getTableMarkup(categories);

                const categoryTable = document.getElementById('categoryTable');
                dataTableInstance = new simpleDatatables.DataTable(categoryTable);
                feather.replace();
            }

            async function load() {
                const res = await fetch(routes.index, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!res.ok) {
                    throw new Error('Failed to load categories');
                }

                const data = await res.json();
                render(data);
            }

            document.getElementById('addCategoryBtn').addEventListener('click', function() {
                form.reset();
                categoryId.value = '';
                modal.show();
            });

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const id = categoryId.value;
                const url = id ? `${routes.update}/${id}` : routes.store;

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: new FormData(form)
                    });

                    if (!res.ok) {
                        throw new Error('Failed to save category');
                    }

                    modal.hide();
                    await load();
                    Swal.fire('Success', 'Saved successfully', 'success');
                } catch (error) {
                    Swal.fire('Error', 'Failed to save category', 'error');
                }
            });

            wrapper.addEventListener('click', async function(e) {
                const edit = e.target.closest('.edit');
                const del = e.target.closest('.delete');

                if (edit) {
                    try {
                        const res = await fetch(`${routes.edit}/${edit.dataset.id}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!res.ok) {
                            throw new Error('Failed to load category');
                        }

                        const data = await res.json();
                        categoryId.value = data.id;
                        categoryName.value = data.name;
                        modal.show();
                    } catch (error) {
                        Swal.fire('Error', 'Failed to load category', 'error');
                    }
                }

                if (del) {
                    const result = await Swal.fire({
                        title: 'Delete?',
                        icon: 'warning',
                        showCancelButton: true
                    });

                    if (!result.isConfirmed) {
                        return;
                    }

                    try {
                        const res = await fetch(`${routes.delete}/${del.dataset.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!res.ok) {
                            throw new Error('Failed to delete category');
                        }

                        await load();
                        Swal.fire('Deleted!', '', 'success');
                    } catch (error) {
                        Swal.fire('Error', 'Failed to delete category', 'error');
                    }
                }
            });

            load().catch(() => {
                Swal.fire('Error', 'Failed to load categories', 'error');
            });
        });
    </script>
@endsection

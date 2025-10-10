@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="{{$isExists ? 'Update Employee Records' : 'Add New Employee'}}" subtitle="employee personal data sheet and portal account" >
            <x-button-link 
                :href="route('hris.employee.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>
        <div class="row">
            <div class="col-12 col-md-3">
                @if($isExists)
                    <x-hris-menu active="children" empno="{{$employee_no}}" />
                @endif
            </div>
            <div class="col-12 {{ $isExists ? 'col-md-9' : '' }}">
                <div class="d-flex justify-content-end align-items-center bg-transparent border-0 mt-2 mb-4">
                    <button class="btn btn-primary px-5 py-3 text-uppercase fw-bold" id="openItemModal" data-action="add">Add Data</button>
                </div>
                <div class="accordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-children" aria-expanded="false" aria-controls="flush-children">
                                Children Background
                            </button>
                        </h2>
                        <div id="flush-children" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <div class="table-responsive">
                                    <table class="table table-hover w-100 pb-3" id="myTable">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Birthday</th>
                                                <th>Document</th>
                                                <th style="width: 120px">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="itemModal" tabindex="-1" data-bs-backdrop="static" aria-labelledby="itemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 text-uppercase" id="itemModalLabel"></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form" enctype="multipart/form-data" action="{{route('hris.employee.children', ['employee_no' => $employee_no])}}" method="post">
                        @if($isEdit)
                            @method('PUT')
                        @else
                            @method('POST')
                        @endif
                        @csrf
                        <div class="row">
                            <input type="hidden" name="id" id="id" value="">
                            <div class="col-12 col-md-6 mb-3">
                                <label class="mb-2" for="firstname">First Name <span class="text-danger">*</span></label>
                                <input type="text" id="firstname" name="firstname" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="mb-2" for="middlename">Middle Name</label>
                                <input type="text" id="middlename" name="middlename" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-12 mb-3">
                                <label class="mb-2" for="lastname">Last Name <span class="text-danger">*</span></label>
                                <input type="text" id="lastname" name="lastname" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-12 mb-3">
                                <label class="mb-2" for="birthdate">Birthday <span class="text-danger">*</span></label>
                                <input type="date" id="birthdate" name="birthdate" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                                <div class="col-12 col-md-12 mb-3">
                                <label class="mb-2" for="documents">Document</label>
                                <input type="file" id="documents" name="documents" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                                <div class="mt-1">
                                    <small class="text-muted text-uppercase">Note: Only accepts docs | docx | pdf | jpeg | jpg | png files only.</small>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-5 mb-4">
                            <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                                Save <i class="fa-solid fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id="galleryContainer"></div>


@endsection

@section('scripts')
<script>
    $(function() {

        const employee_no = '{{$employee_no}}';

        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                url: '{{ route('api.employee.children') }}',
                data: function (d) {
                    d.isDT = true;
                    d.employee_no = employee_no;
                }
            },  
            "columns": [
                { data: "name", name: 'name' },
                { data: "birthday", name: 'birthday' },
                { data: "documents", name: 'documents' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
            "columnDefs": [
                { targets: '_all', className: 'dt-nowrap' } 
            ]
        });    

        $('#openItemModal').on('click', function() {

            const action = $(this).data('action');

            const config = {
                title: {
                    add: 'Add Details',
                    update: 'Update Details'
                },
                method: {
                    add: 'post',
                    update: 'update'
                }
            };

            const title = config.title[action];
            const method = config.method[action];

            $('#itemModal').modal('show');
            $('.modal-title').html(title);

            $('#itemForm').attr('data-method', method);

            $('#form').trigger('reset');
            
        });

        const isEdit = @json($isEdit);
        const url = $('#form').attr('action');

        if(!isEdit) {
            post(url);
        } else {
            put(url);
        }


        $(document).on('click', '#btn-edit', function() {
            const id = $(this).data('id');
            $.ajax({
                type: "GET",
                url: "{{ route('api.employee.children') }}",
                data: {
                    'isDT': false,
                    'employee_no': employee_no,
                    'id': id,
                },
                dataType: "json",
                success: function (response) {
                    
                    Object.entries(response).forEach(([key, value]) => {
                        if (key === 'documents') {
                            return; 
                        }
                        $(`form [name="${key}"]`).val(value);
                    });

                    $('#itemModal').modal('show');
                }
            });
        });

        $(document).on('click', '.open-document', function() {

            const src = $(this).data('src'); 

            const galleryContainer = document.getElementById('galleryContainer');
            if (galleryContainer.lightGalleryInstance) {
                galleryContainer.lightGalleryInstance.destroy();
            }

            const gallery = lightGallery(galleryContainer, {
                dynamic: true,
                dynamicEl: [
                    {
                        src: src,
                        iframe: true
                    }
                ],
                plugins: [lgThumbnail, lgZoom],
                licenseKey: '0000-0000-000-0000',
                speed: 500
            });

            galleryContainer.lightGalleryInstance = gallery;

            gallery.openGallery();
        });

    });
</script>
@endsection



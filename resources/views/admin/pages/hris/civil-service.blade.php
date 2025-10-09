@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="{{$isExists ? 'Update Employee Records' : 'Add New Employee'}}" subtitle="Create new employee's personal data sheet and portal account" >
            <a href="{{route('hris.employee.index')}}" class="btn btn-primary py-3 px-4 text-uppercase fw-medium">
                Go Back
            </a>
        </x-header>
        @if($isExists)
            <x-hris-menu active="civil-service" empno="{{$employee_no}}" />
        @endif
        <div class="d-flex justify-content-end align-items-center bg-transparent border-0 mt-4">
            <button class="btn btn-outline-primary px-5 py-3 text-uppercase fw-bold" id="openItemModal" data-action="add">Add Data</button>
        </div>
        <div class="card shadow p-3 pb-5 mt-5">
            <div class="card-body">
                <div class="accordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-civil-service" aria-expanded="false" aria-controls="flush-civil-service">
                                Civil Service Eligibility
                            </button>
                        </h2>
                        <div id="flush-civil-service" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <div class="table-responsive">
                                    <table class="table table-hover w-100 pb-3" id="myTable">
                                        <thead>
                                            <tr>
                                                <th>Career Service</th>
                                                <th>Rating</th>
                                                <th>License</th>
                                                <th>Documents</th>
                                                <th row></th>
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 text-uppercase" id="itemModalLabel"></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form" enctype="multipart/form-data" action="{{route('hris.employee.civil-service', ['employee_no' => $employee_no])}}" method="post">
                        @if($isEdit)
                            @method('PUT')
                        @else
                            @method('POST')
                        @endif
                        @csrf
                        <div class="row">
                            <input type="hidden" name="id" id="id" value="">
                            <div class="col-12 col-md-12 mb-3">
                                <label class="mb-2" for="certification">
                                Career Service/ RA 1080 (Board / Bar) Under Special Laws/ CES/ CSEE
                                Barangay Eligibility / Driver's License
                            </label>
                                <input type="text" id="certification" name="certification" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-12 mb-3">
                                <label class="mb-2" for="rating">
                                    Rating
                                    (if applicable)
                                </label>
                                <input type="text" id="rating" name="rating" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="mb-2" for="date_exam">
                                Date of Examination / Conferment	    
                            </label>
                                <input type="date" id="date_exam" name="date_exam" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="mb-2" for="place_exam">Place of Exam / Conferment	</label>
                                <input type="text" id="place_exam" name="place_exam" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="mb-2" for="license_no">License No.</label>
                                <input type="text" id="license_no" name="license_no" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="mb-2" for="date_validity">Date of Validity</label>
                                <input type="date" id="date_validity" name="date_validity" class="form-control text-uppercase"
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
                url: '{{ route('api.employee.civil-service') }}',
                data: function (d) {
                    d.isDT = true;
                    d.employee_no = employee_no;
                }
            },  
            "columns": [
                { data: "certification", name: 'certification' },
                { data: "rating", name: 'rating' },
                { data: "license_no", name: 'license_no' },
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
                url: "{{ route('api.employee.civil-service') }}",
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



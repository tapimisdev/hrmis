@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="{{$isExists ? 'Update Employee Records' : 'Add New Employee'}}" subtitle="Create new employee's personal data sheet and portal account" >
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
                    <x-hris-menu active="work-experience" empno="{{$employee_no}}" />
                @endif
            </div>
            <div class="col-12 {{ $isExists ? 'col-md-9' : '' }}">
                <div class="d-flex justify-content-end align-items-center bg-transparent border-0 mt-2 mb-4">
                    <button class="btn btn-primary px-5 py-3 text-uppercase fw-bold" id="openItemModal" data-action="add">Add Data</button>
                </div>
                 <div class="accordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-work-experience" aria-expanded="false" aria-controls="flush-work-experience">
                                Work Experiences
                            </button>
                        </h2>
                        <div id="flush-work-experience" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <div class="table-responsive">
                                    <table class="table table-hover w-100 pb-3" id="myTable">
                                        <thead>
                                            <tr>
                                                <th>Position / Title</th>
                                                <th>Company / Agency</th>
                                                <th>Status of Appointment</th>
                                                <th>Document</th>
                                                <th>Action</th>
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
                    <form id="form" enctype="multipart/form-data" action="{{route('hris.employee.work-experience', ['employee_no' => $employee_no])}}" method="post">
                        @if($isEdit)
                            @method('PUT')
                        @else
                            @method('POST')
                        @endif
                        @csrf
                        <div class="row">
                            <input type="hidden" name="id" id="id" value="">
                            <div class="col-12 col-md-12 mb-3">
                                <label class="mb-2" for="position">
                                Position Title
                                (Write in full / Do not abbreviate)
                            </label>
                                <input type="text" id="position" name="position" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-12 mb-3">
                                <label class="mb-2" for="department">
                                    Department / Agency / Office / Company
                                    (Write in full / Do not abbreviate)
                                </label>
                                <input type="text" id="department" name="department" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="mb-2" for="from_year">
                                    From	    
                                </label>
                                <input type="date" id="from_year" name="from_year" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="mb-2" for="to_year">
                                    To	    
                                </label>
                                <input type="date" id="to_year" name="to_year" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="mb-2" for="monthly_salary">Monthly Salary</label>
                                <input type="number" id="monthly_salary" name="monthly_salary" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                             <div class="col-12 col-md-6 mb-3">
                                <label class="mb-2" for="salary_pay_grade">
                                    Salary / Job / Pay Grade
                                    & Step (Format "00-0") / Increment
                                </label>
                                <input type="number" id="salary_pay_grade" name="salary_pay_grade" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="mb-2" for="employment_status">Status of Appointment	</label>
                                <input type="text" id="employment_status" name="employment_status" class="form-control text-uppercase"
                                    value="">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="mb-2" for="date_validity">Gov't Service (Y / N)</label>
                                <select name="isGovernment" id="isGovernment" class="form-select">
                                    <option value=""> - CHOOSE - </option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
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
                url: '{{ route('api.employee.work-experience') }}',
                data: function (d) {
                    d.isDT = true;
                    d.employee_no = employee_no;
                }
            },  
            "columns": [
                { data: "position", name: 'position' },
                { data: "department", name: 'department' },
                { data: "employment_status", name: 'employment_status' },
                { data: "documents", name: 'documents' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
            "columnDefs": [
                {
                    targets: "_all",
                    className: "min-table-width",
                    render: function(data, type, row, meta) {
                        return data ?? "";
                    }
                }
            ],
            "scrollX": true,
            "autoWidth": false
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
                url: "{{ route('api.employee.work-experience') }}",
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



@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="{{$isExists ? 'Update Employee Records' : 'Add New Employee'}}" subtitle="Create new employee's personal data sheet and portal account" >
            <a href="{{route('hris.employee.index')}}" class="btn btn-primary py-3 px-4 text-uppercase fw-medium">
                Go Back
            </a>
        </x-header>
        @if($isExists)
            <x-hris-menu active="children" empno="{{$employee_no}}" />
        @endif
        <div class="d-flex justify-content-end align-items-center bg-transparent border-0 mt-4">
            <button class="btn btn-outline-primary px-5 py-3 text-uppercase fw-bold" id="openItemModal" data-action="add">Add Data</button>
        </div>
        <div class="card shadow p-3 pb-5 mt-5">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100 pb-3" id="myTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Birthday</th>
                                <th>Documents</th>
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

    <div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 text-uppercase" id="itemModalLabel"></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form" action="{{route('hris.employee.children', ['employee_no' => $employee_no])}}" method="post">
                        @method('POST')
                            <div class="row">
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
                                    <label class="mb-2" for="document">Document <span class="text-danger">*</span></label>
                                    <input type="file" id="document" name="document" class="form-control text-uppercase"
                                        value="">
                                    <div class="error-field"></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-5 mb-4">
                                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                                    Save <i class="fa-solid fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>



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
                    d.employee_no = employee_no;
                }
            },  
            "columns": [
                { data: "name", name: 'name' },
                { data: "birthday", name: 'birthday' },
                { data: "documents", name: 'documents' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
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
        });


    });
</script>
@endsection



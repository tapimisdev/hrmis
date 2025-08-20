@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4">
        <x-header title="Add New Employee" subtitle="" >
            <a href="{{route('hris.employee.create')}}" class="btn btn-primary py-3 px-4 text-uppercase fw-medium">
                Add Employee
            </a>
        </x-header>
        <div class="card shadow p-3">
            <div class="card-body">
                <div class="row my-3">
                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="employee_no">Employee No. <span class="text-danger">*</span></label>
                        <input type="text" id="employee_no" name="employee_no" class="form-control restricted" readonly>
                    </div>

                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="biometrics_id">Biometrics ID</label>
                        <input type="number" id="biometrics_id" name="biometrics_id" class="form-control">
                    </div>  

                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="date_hired">Date Hired</label>
                        <input type="date" id="date_hired" name="date_hired" class="form-control restricted">
                    </div> 

                    <div class="col-md-3 mb-3">
                        <label class="mb-2" for="status">Account Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select">
                            <option value=""> - CHOOSE - </option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="date_resignation">Date Resignation</label>
                        <input type="date" id="date_resignation" name="date_resignation" class="form-control restricted">
                    </div>  

                    <div class="col-12 mt-4 mb-3">
                        <h5 class="mb-0 text-uppercase fw-bold pt-4 pb-0 ps-2">Organization Details</h5>
                        <hr>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="mb-2" for="section_id">Section <span class="text-danger">*</span></label>
                        <select id="section_id" name="section_id" class="form-select">
                            <option value=""> - CHOOSE - </option>
                            <option value="1">Section 1</option>
                            <option value="2">Section 2</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="mb-2" for="branch">Central / Field Office</label>
                        <input type="text" id="branch" name="branch" class="form-control" readonly>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="mb-2" for="department">Cluster</label>
                        <input type="text" id="department" name="department" class="form-control" readonly>
                    </div>

                    <div class="col-12 mt-4 mb-3">
                        <h5 class="mb-0 text-uppercase fw-bold pt-4 pb-0 ps-2">Employment Details</h5>
                        <hr>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="mb-2" for="type">Employment Type <span class="text-danger">*</span></label>
                        <select id="type" name="type" class="form-select">
                            <option value=""> - CHOOSE - </option>
                           
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="mb-2" for="position_id">Position <span class="text-danger">*</span></label>
                        <select id="position_id" name="position_id" class="form-select">
                            <option value=""> - CHOOSE - </option>

                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="mb-2" for="step_id">Tranche Step <span class="text-danger">*</span></label>
                        <select id="step_id" name="step_id" class="form-select">
                            <option value=""> - CHOOSE - </option>
                          
                        </select>
                    </div>

                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="shift_schedule">Shift Schedule</label>
                        <select id="shift_schedule" name="shift_schedule" class="form-select">
                            <option value=""> - CHOOSE - </option>
                           
                        </select>
                    </div> 

                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="employee_schedule">Days Schedule</label>
                        <select id="employee_schedule" name="employee_schedule" class="form-select">
                            <option value=""> - CHOOSE - </option>
                            
                        </select>
                    </div> 

                    <div class="col-12 mt-4 mb-3">
                        <h5 class="mb-0 text-uppercase fw-bold pt-4 pb-0 ps-2">Salary & Payroll Details</h5>
                        <hr>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="mb-2" for="salary_method">Salary Method <span class="text-danger">*</span></label>
                        <select id="salary_method" name="salary_method" class="form-select">
                            <option value=""> - CHOOSE - </option>

                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="mb-2" for="salary">Monthly Rate <span class="text-danger">*</span></label>
                        <input type="number" id="salary" name="salary" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="mb-2" for="payroll_account_number">Payroll Account No.</label>
                        <input type="text" id="payroll_account_number" name="payroll_account_number" class="form-control">
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('hris.employee.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "name", name: 'name' },
                { data: "date_hired", name: 'date_hired' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });
    });
</script>
@endsection



@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="{{$isExists ? 'Update Employee Records' : 'Add New Employee'}}" subtitle="Create new employee's personal data sheet and portal account" >
            <a href="{{route('hris.employee.index')}}" class="btn btn-primary py-3 px-4 text-uppercase fw-medium">
                Go Back
            </a>
        </x-header>
        @if($isExists)
            <x-hris-menu active="work-experience" empno="{{$employee_no}}" />
        @endif
        <form id="form" action="{{route('hris.employee.family', ['employee_no' => $employee_no])}}" method="post">
            @method('POST')
            @csrf
            <div class="card shadow p-3">
                <div class="card-body">
                   <div class="table-responsive">
                        <table class="table table-bordered mt-3">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="text-center"></th>
                                    <th colspan="2" class="text-center">Inclusive Dates <br> (mm/dd/yyyy)</th>
                                    <th rowspan="2" class="text-center">Position Title <br> (Write in full / Do not abbreviate)</th>
                                    <th rowspan="2" class="text-center">Department / Agency / Office / Company <br> (Write in full / Do not abbreviate)</th>
                                    <th rowspan="2" class="text-center">Monthly Salary</th>
                                    <th rowspan="2" class="text-center">Salary / Job / Pay Grade (if applicable) <br> & Step (Format "00-0") / Increment</th>
                                    <th rowspan="2" class="text-center">Status of Appointment</th>
                                    <th rowspan="2" class="text-center">Gov't Service (Y / N)</th>
                                    <th rowspan="2" class="text-center">Documents</th>
                                </tr>
                                <tr>
                                    <th class="text-center">From</th>
                                    <th class="text-center">To</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <button type="button" class="btn btn-danger">
                                            <i class="fa-solid fa-circle-minus"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <input style="width: 300px" type="number" class="form-control text-uppercase text-center" placeholder="From Year">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input style="width: 300px" type="number" class="form-control text-uppercase text-center" placeholder="To Year">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input style="width: 600px" type="text" class="form-control text-uppercase text-center" placeholder="Position Title">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input style="width: 800px" type="text" class="form-control text-uppercase text-center" placeholder="Department / Agency / Office / Company">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input style="width: 300px" type="number" class="form-control text-uppercase text-center" placeholder="Monthly Salary">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input style="width: 100%" type="text" class="form-control text-uppercase text-center" placeholder="Salary / Pay Grade / Step">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input style="width: 500px" type="text" class="form-control text-uppercase text-center" placeholder="Status of Appointment">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <select style="width: 300px" class="form-select text-uppercase text-center">
                                            <option value=""> - CHOOSE - </option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div>
                                                <input type="file" style="width: 300px;" class="form-control">
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a href="javascript:void(0)" class="btn btn-primary">
                                                    <i class="fa-solid fa-download"></i>
                                                </a>
                                                <a href="javascript:void(0)" class="btn btn-danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="error-field"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 d-flex justify-content-end">
                    <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                        Save <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {

        const url = $('#form').attr('action');
        post(url);

    });
</script>
@endsection



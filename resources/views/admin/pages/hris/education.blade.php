@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="{{$isExists ? 'Update Employee Records' : 'Add New Employee'}}" subtitle="Create new employee's personal data sheet and portal account" >
            <a href="{{route('hris.employee.index')}}" class="btn btn-primary py-3 px-4 text-uppercase fw-medium">
                Go Back
            </a>
        </x-header>
        @if($isExists)
            <x-hris-menu active="education" empno="{{$employee_no}}" />
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
                                    <th rowspan="2" class="text-center">Level</th>
                                    <th rowspan="2" class="text-center">Name of School</th>
                                    <th rowspan="2" class="text-center">Basic Education / Degree / Course</th>
                                    <th colspan="2" class="text-center">Period of Attendance</th>
                                    <th rowspan="2" class="text-center">Highest Level / Units Earned <br> (if not graduated)</th>
                                    <th rowspan="2" class="text-center">Year Graduated</th>
                                    <th rowspan="2" class="text-center">Scholarship / Academic <br> Honors Received</th>
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
                                        <select style="width: 300px" class="form-select text-uppercase text-center">
                                            <option value=""> - CHOOSE - </option>
                                            <option value="elementary">Elementary</option>
                                            <option value="secondary">Secondary</option>
                                            <option value="vocational">Vocational</option>
                                            <option value="highschool">High School</option>
                                            <option value="senior_highschool">Senior High School</option>
                                            <option value="college">College</option>
                                            <option value="masters">Masters</option>
                                            <option value="doctoral">Doctoral</option>
                                        </select>
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input type="text" style="width: 600px" class="form-control text-uppercase text-center">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input type="text" style="width: 800px" class="form-control text-uppercase text-center">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input type="number" style="width: 300px" class="form-control text-uppercase text-center" placeholder="ex. 2020">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input type="number" style="width: 300px" class="form-control text-uppercase text-center" placeholder="ex. 2024">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input type="text" style="width: 300px" class="form-control text-uppercase text-center">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input type="number" style="width: 300px" class="form-control text-uppercase text-center" placeholder="ex. 2024">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input type="text" style="width: 800px" class="form-control text-uppercase text-center">
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



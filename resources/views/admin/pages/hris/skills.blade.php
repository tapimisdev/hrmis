@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="{{$isExists ? 'Update Employee Records' : 'Add New Employee'}}" subtitle="Create new employee's personal data sheet and portal account" >
            <a href="{{route('hris.employee.index')}}" class="btn btn-primary py-3 px-4 text-uppercase fw-medium">
                Go Back
            </a>
        </x-header>
        @if($isExists)
            <x-hris-menu active="skills" empno="{{$employee_no}}" />
        @endif
        <form id="form" action="{{route('hris.employee.family', ['employee_no' => $employee_no])}}" method="post">
            @method('POST')
            @csrf
            <div class="card shadow p-3">
                <div class="card-body">
                   <div class="table-responsive">
                        <table class="table table-bordered mt-3 w-100">
                            <thead>
                                <tr>
                                    <th class="text-center text-uppercase"></th>
                                    <th class="text-center text-uppercase">Special Skills and Hobbies</th>
                                    <th class="text-center text-uppercase">Non-Academic Distinctions <br> / Recognition (Write in full)</th>
                                    <th class="text-center text-uppercase">Membership in Association / Organization</th>
                                    <th class="text-center text-uppercase">Documents</th>
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
                                        <input style="width: 500px" type="text" class="form-control text-uppercase text-center">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input style="width: 600px" type="text" class="form-control text-uppercase text-center">
                                        <div class="error-field"></div>
                                    </td>
                                    <td>
                                        <input style="width: 800px" type="text" class="form-control text-uppercase text-center">
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



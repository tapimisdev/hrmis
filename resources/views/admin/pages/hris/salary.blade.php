@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="Update Salary" subtitle="Change or update employee salary" >
            <a href="{{ route('hris.employee.index') }}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
                <i class="fa-solid fa-arrow-left me-2"></i> Back
            </a>
        </x-header>

        <form id="form" action="{{ route('hris.employee.information') }}" method="post">
            @method('POST')
            @csrf
            <div class="card shadow p-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="employee_no">Choose Employees</label>
                            <select id="employeeSelect" class="form-select select2" multiple="multiple" style="width: 75%">
                                <option value=""> - CHOOSE - </option>
                                @foreach ($employees as $divisionName => $units)
                                    <optgroup label="{{ $divisionName }}">
                                        @foreach ($units as $unitName => $unitEmployees)
                                            <optgroup label="&nbsp;&nbsp;{{ $unitName }}">
                                                @foreach ($unitEmployees as $employee)
                                                    <option value="{{ $employee->employee_no }}">
                                                        {{ $employee->firstname . ' ' . $employee->lastname }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>  
                        <div class="col-12 col-md-12 mb-3">
                            <label for="division" class="mb-3">Choose Tranche</label>
                            <select id="division_id" class="form-select text-uppercase">
                                <option value=""> - CHOOSE -</option>
                                @foreach ($divisions as $division)
                                    <option value="{{$division->id}}">
                                        {{ strtoupper($division->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label for="units" class="mb-3">Choose Step</label>
                            <select id="unit_id" class="form-select text-uppercase">
                                <option value=""> - CHOOSE -</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
             <div class="mt-5 bg-transparent border-0 d-flex justify-content-end mt-3">
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                    Save <i class="fa-solid fa-arrow-right ms-2"></i>
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {

        let selectedEmployees = @json($selectedEmployee ?? []);

        $('#employeeSelect').val(selectedEmployees).trigger('change');

        const url = $('#form').attr('action');
        post(url);

        // DIVISIONS ON CHANGE, SHOW UNITS
        $('#division_id').on('change', function() {
            const id = $(this).val();
            const url = @json(route('hris.employee.information'));
            $.ajax({
                type: "GET",
                url: url,
                data: { 'division_id': id },
                dataType: "JSON",
                success: function (response) {
                    const res = response.data;
                    $('#unit_id').html('<option value=""> - CHOOSE UNIT - </option>'); 
                    res.forEach(item => {
                        $('#unit_id').append(`
                            <option value="${item.id}">${item.name.toUpperCase()}</option>
                        `);
                    });
                },
                error: function(xhr, status, error) {
                    console.error(status, error);
                }
            });
        });


    });
</script>
@endsection

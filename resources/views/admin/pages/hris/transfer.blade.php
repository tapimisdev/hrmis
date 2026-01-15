@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Transfer Employees" subtitle="Transfer employees to other units" >
            <x-button-link 
                :href="route('hris.employee.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>

        <form id="form" action="{{ route('hris.employee.transfer') }}" method="post">
            @method('POST')
            @csrf
            <div class="card shadow">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="employee_no">Choose Employees</label>
                            <select id="employees" name="employees[]" class="form-select select2" multiple="multiple" style="width: 75%">
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
                        <div class="col-12 col-md-6 mb-3">
                            <label for="division" class="mb-3">Choose Division</label>
                            <select id="division_id" name="division_id" class="form-select text-uppercase">
                                <option value=""> - CHOOSE -</option>
                                @foreach ($divisions as $division)
                                    <option value="{{$division->id}}">
                                        {{ strtoupper($division->name) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label for="units" class="mb-3">Choose Unit</label>
                            <select id="unit_id" name="unit_id" class="form-select text-uppercase">
                                <option value=""> - CHOOSE -</option>
                            </select>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="mb-2" for="employment_type_id">Employment Type <span class="text-danger">*</span></label>
                            <select id="employment_type_id" name="employment_type_id" class="form-select">
                                <option value=""> - CHOOSE - </option>
                                @foreach($employment_types as $type)
                                    <option value="{{ $type->id }}">{{ strtoupper($type->name) }}</option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="mb-2" for="position_id">Position <span class="text-danger">*</span></label>
                            <select id="position_id" name="position_id" class="form-select">
                                <option value=""> - CHOOSE - </option>
                            </select>
                            <div class="error-field"></div>
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

        $('#employees').val(selectedEmployees).trigger('change');

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

         // EMPLOYMENT TYPE ON CHANGE, SHOW POSITIONS

        $('#employment_type_id').on('change', function() {
            const id = $(this).val();
            const url = @json(route('hris.employee.information'));
            $('#salary').val(0)
            $.ajax({
                type: "GET",
                url: url,
                data: {
                    'employment_type_id': id
                },
                dataType: "json",
                success: function (response) {
                    const res = response;
                    const $position = $('#position_id')
                    $('#position_id').html('<option value=""> - CHOOSE POSITION - </option>'); 
                     res.positions.forEach(item => {
                        $position.append(`<option value="${item.id}">${item.name.toUpperCase()}</option>`);
                    });
                },
                error: function(xhr, status, error) {
                    console.error(status, error);
                }
            });
        });

        // POSITION ID ON CHANGE, SHOW POSITIONS

        $('#position_id').on('change', function() {
            const id = $(this).val();
               const url = @json(route('hris.employee.information'));
            $.ajax({
                type: "GET",
                url: url,
                data: {
                    'position_id': id
                },
                dataType: "json",
                success: function (response) {
                    const res = response.data;
                    $('#salary').val(res.salary)
                },
                error: function(xhr, status, error) {
                    console.error(status, error);
                }
            });

        });


    });
</script>
@endsection

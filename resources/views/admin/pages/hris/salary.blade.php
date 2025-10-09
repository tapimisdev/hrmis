@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="Update Salary" subtitle="Change or update employee salary" >
            <a href="{{ route('hris.employee.index') }}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
                <i class="fa-solid fa-arrow-left me-2"></i> Back
            </a>
        </x-header>

        <form id="form" action="{{ route('hris.employee.salary') }}" method="post">
            @method('POST')
            @csrf
            <div class="card shadow p-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="mb-2" for="employment_type_id">Employment Type <span class="text-danger">*</span></label>
                            <select id="employment_type_id" name="employment_type_id" class="form-select">
                                <option value=""> - CHOOSE - </option>
                                @foreach($employment_types as $type)
                                    <option value="{{ $type->id }}">{{ strtoupper($type->name) }}</option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="employee_no">Choose Employees</label>
                            <select id="employees" name="employees[]" class="form-select select2" multiple="multiple" style="width: 75%">
                                <option value=""> - CHOOSE - </option>
                                
                            </select>
                            <div class="error-field"></div>
                        </div>  
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="tranche_id">Tranche <span class="text-danger">*</span></label>
                            <select id="tranche_id" name="tranche_id" class="form-select">
                                <option value=""> - CHOOSE - </option>
                                @foreach($tranches as $tranche)
                                    <option value="{{ $tranche->id }}">{{ strtoupper($tranche->date) }}</option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="salary_grade">Salary Grade <span class="text-danger">*</span></label>
                            <select id="salary_grade" name="salary_grade" class="form-select">
                                <option value=""> - CHOOSE - </option>
                               
                            </select>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="step_id">Steps <span class="text-danger">*</span></label>
                            <select id="step_id" name="step_id" class="form-select">
                                <option value=""> - CHOOSE - </option>
                                @foreach(range(1, 8) as $step)
                                    <option value="{{ $step }}">Step {{ $step }}</option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="mb-2" for="salary">Monthly Rate <span class="text-danger">*</span></label>
                            <input type="text" id="salary" name="salary" class="form-control restricted" value="0" disabled>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="mb-2" for="daily_rate">Daily Rate <span class="text-danger">*</span></label>
                            <input type="text" id="daily_rate" name="daily_rate" class="form-control restricted" value="0" disabled>
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

        const infoUrl = @json(route('hris.employee.information'));
        let selectedEmployees = @json($selectedEmployee ?? []);

        $('#employees').val(selectedEmployees).trigger('change');

        const url = $('#form').attr('action');
        post(url);

        $('#employment_type_id').on('change', function() {
            const id = $(this).val();
            $('#salary').val(0)
            $.ajax({
                type: "GET",
                url: infoUrl,
                data: {
                    'employment_type_id': id
                },
                dataType: "json",
                success: function (response) {
                    const res = response;
                    console.log(res);

                    const $tranche = $('#tranche_id')
                    $('#tranche_id').html('<option value=""> - CHOOSE TRANCHE - </option>'); 
                    res.tranches.forEach(item => {
                        const date = new Date(item.date);
                        const formatted = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                        $tranche.append(`<option value="${item.id}">${formatted}</option>`);
                    });

                    const $employees = $('#employees');
                    $employees.html('<option value=""> - CHOOSE - </option>');
                    const uniqueEmployees = [];

                    res.employees.forEach(emp => {
                        if (!uniqueEmployees.includes(emp.employee_no)) {
                            uniqueEmployees.push(emp.employee_no);
                            const fullName = `${emp.firstname} ${emp.lastname}`;
                            $employees.append(`<option value="${emp.employee_no}">${fullName}</option>`);
                        }
                    });
                },
                error: function(xhr, status, error) {
                    console.error(status, error);
                }
            });
        });

        
        $('#tranche_id').on('change', function () {
            const tranche_id = $(this).val();
            $.get(infoUrl, { forSalaryGrade: true, tranche_id }, function (response) {
                const $select = $('#salary_grade');
                $select.empty().append('<option value=""> - CHOOSE - </option>');
                $.each(response.data, function (_, value) {
                    $select.append(`<option value="${value}">${value}</option>`);
                });
                $select.trigger('change');
            }, 'json');
        });

        $('#tranche_id, #step_id, #salary_grade').on('change', function () {
            const tranche_id = $('#tranche_id').val();
            const step_id = $('#step_id').val();
            const salary_grade = $('#salary_grade').val();

            $.get(infoUrl, { tranche_id, step_id, salary_grade }, function (response) {
                if (response.data) {
                    const amount = parseFloat(response.data.salary || 0);
                    $('#salary').val(amount.toFixed(2));
                    $('#daily_rate').val((amount / 22).toFixed(2));
                }
            }, 'json');
        });


    });
</script>
@endsection

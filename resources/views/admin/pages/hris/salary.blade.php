@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Update Salary" subtitle="Change or update employee salary" >
            <x-button-link 
                :href="route('hris.employee.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
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
                                
                            </select>
                            <div class="error-field"></div>
                        </div>  
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="tranche_id">Tranche <span class="text-danger">*</span></label>
                            <select id="tranche_id" name="tranche_id" class="form-select">
                                <option value=""> - CHOOSE - </option>
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
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="effectivity_date">Effectivity Date <span class="text-danger">*</span></label>
                            <input type="date" name="effectivity_date" id="effectivity_date" class="form-control">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="mb-2" for="salary">Monthly Rate <span class="text-danger">*</span></label>
                            <input type="text" id="salary" name="salary" class="form-control restricted" value="0" disabled>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-md-4 mb-3">
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
$(function () {
    const infoUrl = @json(route('hris.employee.information'));
    const selectedEmployee = @json($selectedEmployee);
    const url = $('#form').attr('action');

    const initializeEmployeeInfo = () => {
        const $employmentType = $('#employment_type_id');
        const $tranche = $('#tranche_id');
        const $salaryGrade = $('#salary_grade');
        const $step = $('#step_id');
        const $salary = $('#salary');
        const $dailyRate = $('#daily_rate');
        const $effectivityDate = $('#effectivity_date');
        const $employees = $('#employees');

        $employmentType.on('change', function () {
            const id = $(this).val();
            $salary.val(0);

            $.ajax({
                type: "GET",
                url: infoUrl,
                data: { employment_type_id: id },
                dataType: "json",
                success: function (response) {
                    const { tranches, employees } = response;

                    $tranche.html('<option value=""> - CHOOSE TRANCHE - </option>');
                    tranches.forEach(item => {
                        const formatted = new Date(item.date).toLocaleDateString('en-US', {
                            year: 'numeric', month: 'long', day: 'numeric'
                        });
                        $tranche.append(`<option value="${item.id}">${formatted}</option>`);
                    });

                    $employees.html('<option value=""> - CHOOSE - </option>');
                    const seen = new Set();
                    employees.forEach(emp => {
                        if (!seen.has(emp.employee_no)) {
                            seen.add(emp.employee_no);
                            $employees.append(`<option value="${emp.employee_no}">${emp.firstname} ${emp.lastname}</option>`);
                        }
                    });

                    if (selectedEmployee.employee_no) {
                        $employees.val(selectedEmployee.employee_no).trigger('change.select2');
                    }

                    if (selectedEmployee.tranche_id) {
                        $tranche.val(selectedEmployee.tranche_id).trigger('change');
                    }
                },
                error: function (xhr, status, error) {
                    console.error(status, error);
                }
            });
        });

        $tranche.on('change', function () {
            const tranche_id = $(this).val();
            if (!tranche_id) return;

            $.get(infoUrl, { forSalaryGrade: true, tranche_id }, function (response) {
                $salaryGrade.empty().append('<option value=""> - CHOOSE - </option>');
                $.each(response.data, (_, value) => {
                    $salaryGrade.append(`<option value="${value}">${value}</option>`);
                });

                if (selectedEmployee.salary_grade) {
                    $salaryGrade.val(selectedEmployee.salary_grade).trigger('change');
                }
            }, 'json');
        });

        $salaryGrade.add($step).add($tranche).on('change', function () {
            const tranche_id = $tranche.val();
            const step_id = $step.val();
            const salary_grade = $salaryGrade.val();

            if (!tranche_id || !step_id || !salary_grade) return;

            $.get(infoUrl, { tranche_id, step_id, salary_grade }, function (response) {
                if (response.data) {
                    const amount = parseFloat(response.data.salary || 0);
                    $salary.val(amount.toFixed(2));
                    $dailyRate.val((amount / 22).toFixed(2));
                }
            }, 'json');
        });

        // Initialize selected employee values
        if (selectedEmployee && Object.keys(selectedEmployee).length > 0) {
            $employmentType.val(selectedEmployee.employment_type_id).trigger('change');
            $step.val(selectedEmployee.step);
            $effectivityDate.val(selectedEmployee.effectivity_date || '');
        }
    };

    initializeEmployeeInfo();
    post(url);
});
</script>
@endsection
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
                            <label class="mb-2" for="employee_no">Choose Employees <span class="text-danger">*</span></label>
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
                        <div class="accordion mb-4" id="organizationAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOrganization">
                                    <button class="accordion-button" type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#collapseOrganization" 
                                            aria-expanded="true" 
                                            aria-controls="collapseOrganization">
                                        <strong>Organization and Employment Details</strong>
                                    </button>
                                </h2>
                                <div id="collapseOrganization" 
                                    class="accordion-collapse collapse show" 
                                    aria-labelledby="headingOrganization" 
                                    data-bs-parent="#organizationAccordion">
                                    
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-12 col-md-6 mb-3">
                                                <label for="division" class="mb-3">Choose Division <span class="text-danger">*</span></label>
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
                                                <label for="units" class="mb-3">Choose Unit <span class="text-danger">*</span></label>
                                                <select id="unit_id" name="unit_id" class="form-select text-uppercase">
                                                    <option value=""> - CHOOSE -</option>
                                                </select>
                                                <div class="error-field"></div>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="mb-2" for="employment_type_id">
                                                    Employment Type <span class="text-danger">*</span>
                                                </label>
                                                <select id="employment_type_id" name="employment_type_id" class="form-select">
                                                    <option value=""> - CHOOSE - </option>
                                                    @foreach($employment_types as $type)
                                                        <option value="{{ $type->id }}">
                                                            {{ strtoupper($type->name) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="error-field"></div>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="mb-2" for="position_id">
                                                    Position <span class="text-danger">*</span>
                                                </label>
                                                <select id="position_id" name="position_id" class="form-select">
                                                    <option value=""> - CHOOSE - </option>
                                                </select>
                                                <div class="error-field"></div>
                                            </div>

                                            <div class="col-12 col-md-6 mb-3">
                                                <label class="mb-2" for="shift_id">Shift Schedule <span class="text-danger">*</span></label>
                                                <select id="shift_id" name="shift_id" class="form-select">
                                                    <option value=""> - CHOOSE - </option>
                                                        @foreach($shifts as $shift)
                                                        <option value="{{ $shift->id }}">{{ strtoupper($shift->name) }}</option>
                                                        @endforeach
                                                    </select>
                                                <div class="error-field"></div>
                                            </div>
                                            <div class="col-12 col-md-6 mb-3">
                                                <label class="mb-2" for="schedule_id">Days Schedule <span class="text-danger">*</span></label>
                                                <select id="schedule_id" name="schedule_id" class="form-select">
                                                    <option value=""> - CHOOSE - </option>
                                                    @foreach($schedules as $schedule)
                                                    <option value="{{ $schedule->id }}">{{ strtoupper($schedule->name) }}</option>
                                                    @endforeach
                                                </select>
                                                </select>
                                                <div class="error-field"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingSalary">
                                    <button class="accordion-button text-uppercase fw-bold" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#collapseSalary" 
                                            aria-expanded="true">
                                        Salary & Payroll Details
                                    </button>
                                </h2>
                                <div id="collapseSalary" class="accordion-collapse collapse show">
                                    <div class="accordion-body">
                                        <div class="row mt-3">
                                            <div class="col-12 col-md-8 mb-3">
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label class="mb-2" for="tranche_id">Tranche <span class="text-danger">*</span></label>
                                                        <select id="tranche_id" name="tranche_id" class="form-select">
                                                            <option value=""> - CHOOSE - </option>
                                                        </select>
                                                        <div class="error-field"></div>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
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
                                                        <label class="mb-2" for="salary_grade">Salary Grade <span class="text-danger">*</span></label>
                                                        <select name="salary_grade" id="salary_grade" class="form-select">
                                                            <option value=""> - CHOOSE - </option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="mb-2" for="salary_frequency">Salary Frequency <span class="text-danger">*</span></label>
                                                        <select id="salary_frequency" name="salary_frequency" class="form-select">
                                                            <option value=""> - CHOOSE - </option>
                                                            <option value="once">Once A Month</option>
                                                            <option value="twice">Twice A Month</option>
                                                        </select>
                                                        <div class="error-field"></div>
                                                    </div>
                                                    <div id="salary_cutoff_container" class="col-md-4 mb-3" style="display: none;">
                                                        <label class="mb-2" for="salary_cutoff">Salary Every <span class="text-danger">*</span></label>
                                                        <select id="salary_cutoff" name="salary_cutoff" class="form-select">
                                                            <option value=""> - CHOOSE - </option>
                                                            <option value="first_cutoff">First Cut-Off</option>
                                                            <option value="second_cutoff">Second Cut-Off</option>
                                                        </select>
                                                        <div class="error-field"></div>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="mb-2" for="deduction_applied">Deduction Applied <span class="text-danger">*</span></label>
                                                        <select id="deduction_applied" name="deduction_applied" class="form-select">
                                                            <option value=""> - CHOOSE - </option>
                                                            <option value="first_cutoff">First Cut Off</option>
                                                            <option value="second_cutoff">Second Cut Off</option>
                                                            <option value="both">Both Cut Off</option>
                                                        </select>
                                                        <div class="error-field"></div>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="mb-2" for="salary_method">Salary Method <span class="text-danger">*</span></label>
                                                        <select id="salary_method" name="salary_method" class="form-select">
                                                            <option value=""> - CHOOSE - </option>
                                                            <option value="cash">Cash</option>
                                                            <option value="bank transfer">Bank Transfer</option>
                                                            <option value="paycheck">Paycheck</option>
                                                            <option value="e-wallet">E-Wallet</option>
                                                        </select>
                                                        <div class="error-field"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4 mb-3">
                                                <div class="card shadow">
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-12 mb-3">
                                                                <label class="mb-2" for="salary">Salary</label>
                                                                <input type="text" id="salary" name="salary" class="form-control" disabled>
                                                                <div class="error-field"></div>
                                                            </div>
                                                            <div class="col-md-12 mb-3">
                                                                <label class="mb-2" for="daily_rate">Daily Rate</label>
                                                                <input type="text" id="daily_rate" name="daily_rate" class="form-control" disabled>
                                                                <div class="error-field"></div>
                                                            </div>
                                                            <div class="col-md-12 mb-3 cutoff first-cutoff" style="display: none;">
                                                                <label class="mb-2" for="first_cutoff_amount">1st Cutoff Amount</label>
                                                                <input type="text" id="first_cutoff_amount" name="first_cutoff_amount" class="form-control" disabled>
                                                                <div class="error-field"></div>
                                                            </div>
                                                            <div class="col-md-12 mb-3 cutoff second-cutoff" style="display: none;">
                                                                <label class="mb-2" for="second_cutoff_amount">2nd Cutoff Amount</label>
                                                                <input type="text" id="second_cutoff_amount" name="second_cutoff_amount" class="form-control" disabled>
                                                                <div class="error-field"></div> 
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

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
        const infoUrl = @json(route('hris.employee.information'));

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
                    const $position = $('#position_id');
                    const $tranche = $('#tranche_id');

                    $position.html('<option value=""> - CHOOSE POSITION - </option>');
                    res.positions.forEach(item => {
                        $position.append(`<option value="${item.id}">${item.name.toUpperCase()}</option>`);
                    });

                    $tranche.html('<option value=""> - CHOOSE TRANCHE - </option>');
                    res.tranches.forEach(item => {
                        const date = new Date(item.date);
                        const formatted = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                        $tranche.append(`<option value="${item.id}">${formatted}</option>`);
                    });

                    $tranche.trigger('change');
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

        $('#tranche_id').on('change', function () {
            const tranche_id = $(this).val();
            $.get(infoUrl, { forSalaryGrade: true, tranche_id }, function (response) {
                const $select = $('#salary_grade');
                $select.empty().append('<option value=""> - CHOOSE - </option>');
                $.each(response.data, function (_, value) {
                    $select.append(`<option value="${value}" >${value}</option>`);
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
                    updateCutoffAmounts();
                }
            }, 'json');
        });

        $('#salary_frequency').on('change', function () {
            const val = $(this).val();
            $('.cutoff').hide();

            if (val === 'once') {
                $('#salary_cutoff_container').show();
            } else if (val === 'twice') {
                $('#salary_cutoff_container').hide().val('');
                $('.first-cutoff, .second-cutoff').show();
            } else {
                $('#salary_cutoff_container').hide().val('');
            }

            updateCutoffAmounts();
        }).trigger('change');

        function updateCutoffAmounts() {
            const salaryVal = parseFloat($('#salary').val()) || 0;
            const frequency = $('#salary_frequency').val();
            const cutoff = $('#salary_cutoff').val();

            if (frequency === 'twice') {
                const half = (salaryVal / 2).toFixed(2);
                $('#first_cutoff_amount').val(half);
                $('#second_cutoff_amount').val(half);
            } else if (frequency === 'once') {
                if (cutoff === 'first_cutoff') {
                    $('#first_cutoff_amount').val(salaryVal.toFixed(2));
                    $('#second_cutoff_amount').val('');
                } else if (cutoff === 'second_cutoff') {
                    $('#second_cutoff_amount').val(salaryVal.toFixed(2));
                    $('#first_cutoff_amount').val('');
                }
            } else {
                $('#first_cutoff_amount, #second_cutoff_amount').val('');
            }
        }


    });
</script>
@endsection

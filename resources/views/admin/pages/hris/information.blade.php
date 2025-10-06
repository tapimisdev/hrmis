@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="{{$isExists ? 'Update Employee Records' : 'Add New Employee'}}" subtitle="Create new employee's personal data sheet and portal account" >
            <a href="{{route('hris.employee.index')}}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
                <i class="fa-solid fa-arrow-left me-2"></i> Back
            </a>
        </x-header>
        
        @if($isExists)
            <x-hris-menu active="information" empno="{{$employee_no}}" />
        @endif

        <form id="form" action="{{!$isExists ? route('hris.employee.information') : route('hris.employee.information', ['employee_no' => $employee_no])}}" method="post">
            @method('POST')
            @csrf
            <div class="card shadow p-3">
                <div class="card-body">
                  <div class="accordion" id="employeeAccordion">
                    
                    {{-- EMPLOYEE INFORMATION --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingInfo">
                            <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInfo" aria-expanded="true">
                                Employee Information
                            </button>
                        </h2>
                        <div id="collapseInfo" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="mb-2" for="employee_no">Employee No. <span class="text-danger"></span></label>
                                        <input type="text" id="employee_no" name="employee_no" class="form-control" value="{{ optional($data)->employee_no ?? '' }}">
                                        <div class="error-field"></div>
                                    </div>
                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="mb-2" for="biometrics_id">Biometrics ID</label>
                                        <input type="text" id="biometrics_id" name="biometrics_id" class="form-control" value="{{ optional($data)->biometrics_id ?? '' }}">
                                        <div class="error-field"></div>
                                    </div>
                                    <div class="col-12 col-md-3 mb-3">
                                        <label class="mb-2" for="date_hired">Date Hired <span class="text-danger"></span></label>
                                        <input type="date" id="date_hired" name="date_hired" class="form-control" value="{{ optional($data)->date_hired ?? '' }}">
                                        <div class="error-field"></div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="mb-2" for="status">Account Status <span class="text-danger">*</span></label>
                                        <select id="status" name="status" class="form-select">
                                        @foreach(['' => '- CHOOSE -', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                                        <option value="{{ $value }}" {{ (optional($data)->account_status ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                        </select>
                                        <div class="error-field"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ORGANIZATION DETAILS --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOrg">
                            <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOrg" aria-expanded="true">
                            Organization Details
                            </button>
                        </h2>
                        <div id="collapseOrg" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="mb-2" for="division_id">Division <span class="text-danger"></span></label>
                                    <select id="division_id" name="division_id" class="form-select">
                                        <option value=""> - CHOOSE - </option>
                                        @foreach($divisions as $division)
                                        <option value="{{ $division->id }}" {{ (optional($data)->division_id ?? '') == $division->id ? 'selected' : '' }}>{{ strtoupper($division->name) }}</option>
                                        @endforeach
                                    </select>
                                    <div class="error-field"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="mb-2" for="unit_id">Unit <span class="text-danger"></span></label>
                                    <select id="unit_id" name="unit_id" class="form-select">
                                        @if(optional($data)->unit_id)
                                        <option value="{{ optional($data)->unit_id }}" selected>{{ strtoupper(optional($data)->unit_name) }}</option>
                                        @else
                                        <option value=""> - CHOOSE DIVISION FIRST - </option>
                                        @endif
                                    </select>
                                    <div class="error-field"></div>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- EMPLOYMENT DETAILS --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingEmp">
                            <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEmp" aria-expanded="true">
                            Employment Details
                            </button>
                        </h2>
                        <div id="collapseEmp" class="accordion-collapsecollapse show">
                            <div class="accordion-body">
                                <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="mb-2" for="employment_type_id">Employment Type <span class="text-danger"></span></label>
                                    <select id="employment_type_id" name="employment_type_id" class="form-select">
                                        <option value=""> - CHOOSE - </option>
                                        @foreach($employment_types as $type)
                                        <option value="{{ $type->id }}" {{ (optional($data)->employment_type_id ?? '') == $type->id ? 'selected' : '' }}>{{ strtoupper($type->name) }}</option>
                                        @endforeach
                                    </select>
                                    <div class="error-field"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="mb-2" for="position_id">Position <span class="text-danger"></span></label>
                                    <select id="position_id" name="position_id" class="form-select">
                                        @if(optional($data)->position_id)
                                        <option value="{{ optional($data)->position_id }}" selected>{{ strtoupper(optional($data)->position_name) }}</option>
                                        @else
                                        <option value=""> - CHOOSE - </option>
                                        @endif
                                    </select>
                                    <div class="error-field"></div>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label class="mb-2" for="shift_id">Shift Schedule <span class="text-danger"></span></label>
                                    <select id="shift_id" name="shift_id" class="form-select">
                                         <option value=""> - CHOOSE - </option>
                                            @foreach($shifts as $shift)
                                            <option value="{{ $shift->id }}" {{ (optional($data)->shift_id ?? '') == $shift->id ? 'selected' : '' }}>{{ strtoupper($shift->name) }}</option>
                                            @endforeach
                                        </select>
                                    <div class="error-field"></div>
                                </div>
                                <div class="col-12 col-md-6 mb-3">
                                    <label class="mb-2" for="schedule_id">Days Schedule <span class="text-danger"></span></label>
                                    <select id="schedule_id" name="schedule_id" class="form-select">
                                        <option value=""> - CHOOSE - </option>
                                        @foreach($schedules as $schedule)
                                        <option value="{{ $schedule->id }}" {{ (optional($data)->work_schedule_id ?? '') == $schedule->id ? 'selected' : '' }}>{{ strtoupper($schedule->name) }}</option>
                                        @endforeach
                                    </select>
                                    </select>
                                    <div class="error-field"></div>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SALARY & PAYROLL DETAILS --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingSalary">
                            <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSalary" aria-expanded="true">
                                Salary & Payroll Details
                            </button>
                        </h2>
                        <div id="collapseSalary" class="accordion-collapse collapse show">
                            <div class="accordion-body">

                                {{-- Tranche & Step (Only PL) --}}
                                <div class="row tranche-step">
                                    @if(optional($data)->employment_type_id)
                                        <div class="col-md-4 mb-3">
                                            <label class="mb-2" for="tranche_id">Tranche <span class="text-danger">*</span></label>
                                            <select id="tranche_id" name="tranche_id" class="form-select">
                                                <option value=""> - CHOOSE - </option>
                                                @foreach($tranches as $tranche)
                                                    <option value="{{ $tranche->id }}" {{ (optional($data)->tranche_id ?? '') == $tranche->id ? 'selected' : '' }}>
                                                        {{ strtoupper($tranche->name) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="mb-2" for="step_id">Steps <span class="text-danger">*</span></label>
                                            <select id="step_id" name="step_id" class="form-select">
                                                <option value=""> - CHOOSE - </option>
                                                @foreach(range(1, 8) as $step)
                                                    <option value="{{ $step }}" {{ (optional($data)->step ?? '') == $step ? 'selected' : '' }}>Step {{ $step }}</option>
                                                @endforeach
                                            </select>
                                            <div class="error-field"></div>
                                        </div>
                                    @endif
                                </div>

                                <div class="row">
                                    {{-- Salary Frequency --}}
                                    <div class="col-md-4 mb-3">
                                        <label class="mb-2" for="salary_frequency">Salary Frequency <span class="text-danger">*</span></label>
                                        <select id="salary_frequency" name="salary_frequency" class="form-select">
                                            @foreach([''=> '- CHOOSE -', 'once' => 'Once A Month', 'twice' => 'Twice A Month'] as $value => $label)
                                                <option value="{{ $value }}" {{ (optional($data)->salary_frequency ?? '') == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="error-field"></div>
                                    </div>

                                    {{-- Show only if frequency is "once" --}}
                                    <div id="salary_cutoff_container" class="col-md-4 mb-3" style="display: none;">
                                        <label class="mb-2" for="salary_cutoff">Salary Every <span class="text-danger">*</span></label>
                                        <select id="salary_cutoff" name="salary_cutoff" class="form-select">
                                            @foreach([''=> '- CHOOSE -', 'first_cutoff' => 'First Cut-Off', 'second_cutoff' => 'Second Cut-Off'] as $value => $label)
                                                <option value="{{ $value }}" {{ (optional($data)->salary_cutoff ?? '') == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="error-field"></div>
                                    </div>

                                    {{-- Salary / Daily Rate --}}

                                    <div class="col-md-3 mb-3">
                                        <label class="mb-2" for="salary">Salary <span class="text-danger">*</span></label>
                                        <input type="text" id="salary" name="salary" class="form-control"
                                            value="{{ optional($data)->salary ?? '' }}">
                                        <div class="error-field"></div>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="mb-2" for="daily_rate">Daily Rate</label>
                                        <input type="text" id="daily_rate" name="daily_rate" class="form-control"
                                            value="{{ optional($data)->daily_rate ?? '' }}">
                                        <div class="error-field"></div>
                                    </div>

                                    {{-- Cutoff Amounts (conditionally shown by JS) --}}
                                    <div class="col-md-3 mb-3 cutoff first-cutoff" style="display: none;">
                                        <label class="mb-2" for="first_cutoff_amount">1st Cutoff Amount <span class="text-danger">*</span></label>
                                        <input type="text" id="first_cutoff_amount" name="first_cutoff_amount" class="form-control"
                                            value="{{ optional($data)->first_cutoff_amount ?? '' }}">
                                        <div class="error-field"></div>
                                    </div>

                                    <div class="col-md-3 mb-3 cutoff second-cutoff" style="display: none;">
                                        <label class="mb-2" for="second_cutoff_amount">2nd Cutoff Amount <span class="text-danger">*</span></label>
                                        <input type="text" id="second_cutoff_amount" name="second_cutoff_amount" class="form-control"
                                            value="{{ optional($data)->second_cutoff_amount ?? '' }}">
                                        <div class="error-field"></div> 
                                    </div>

                                    {{-- Deduction --}}
                                    <div class="col-md-4 mb-3">
                                        <label class="mb-2" for="deduction_applied">Deduction Applied <span class="text-danger">*</span></label>
                                        <select id="deduction_applied" name="deduction_applied" class="form-select">
                                            <option value=""> - CHOOSE - </option>
                                            <option value="first_cutoff" {{ optional($data)->deduction_applied == 'first_cutoff' ? 'selected' : '' }}>First Cut Off</option>
                                            <option value="second_cutoff" {{ optional($data)->deduction_applied == 'second_cutoff' ? 'selected' : '' }}>Second Cut Off</option>
                                            <option value="both" {{ optional($data)->deduction_applied == 'both' ? 'selected' : '' }}>Both Cut Off</option>
                                        </select>
                                        <div class="error-field"></div>
                                    </div>

                                    {{-- Salary Method --}}
                                    <div class="col-md-4 mb-3">
                                        <label class="mb-2" for="salary_method">Salary Method <span class="text-danger">*</span></label>
                                        <select id="salary_method" name="salary_method" class="form-select">
                                            @foreach(['' => '- CHOOSE -', 'cash' => 'Cash', 'bank transfer' => 'Bank Transfer', 'paycheck' => 'Paycheck', 'e-wallet' => 'E-Wallet'] as $value => $label)
                                                <option value="{{ $value }}" {{ (optional($data)->salary_method ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <div class="error-field"></div>
                                    </div>

                                    {{-- Payroll No --}}
                                    <div class="col-md-6 mb-3">
                                        <label class="mb-2" for="payroll_account_number">Payroll Account No.</label>
                                        <input type="text" id="payroll_account_number" name="payroll_account_number" class="form-control"
                                            value="{{ optional($data)->payroll_account_no ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                  </div>
                </div>

                <div class="card-footer bg-transparent border-0 d-flex justify-content-end mt-3">
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
    $(function () {
        const url = $('#form').attr('action');
        const salary = @json(optional($data)->salary ?? 0);
        const daily_rate = @json(optional($data)->daily_rate ?? 0);

        post(url);

        $('#division_id').on('change', function () {
            const id = $(this).val();
            const url = @json(route('hris.employee.information'));
            $.get(url, { division_id: id }, function (response) {
                const res = response.data;
                $('#unit_id').html('<option value=""> - CHOOSE UNIT - </option>');
                res.forEach(item => {
                    $('#unit_id').append(`<option value="${item.id}">${item.name.toUpperCase()}</option>`);
                });
            }, 'json');
        });

        $('#employment_type_id').on('change', function () {
            const id = $(this).val();
            const url = @json(route('hris.employee.information'));
            const selectedPositionId = @json(optional($data)->position_id);

            $.get(url, { employment_type_id: id }, function (response) {
                const res = response.data;
                $('#position_id').html('<option value=""> - CHOOSE POSITION - </option>');
                res.forEach(item => {
                    let selected = (item.id == selectedPositionId) ? 'selected' : '';
                    $('#position_id').append(`<option value="${item.id}" ${selected}>${item.name.toUpperCase()}</option>`);
                });

                if (id == 1) {
                    $('.tranche-step').show();
                    $('#salary, #daily_rate, #first_cutoff_amount, #second_cutoff_amount')
                        .prop('readonly', true)
                        .addClass('restricted');
                } else {
                    $('.tranche-step').hide();
                    $('#salary, #daily_rate, #first_cutoff_amount, #second_cutoff_amount')
                        .prop('readonly', false)
                        .removeClass('restricted');
                }
            }, 'json');
        });

        $('#position_id').on('change', function () {
            const id = $(this).val();
            const url = @json(route('hris.employee.information'));
            $.get(url, { position_id: id }, function (response) {
                let selectedType = $("#employment_type_id").val();
                if (selectedType === "2" && response.data) {
                    let salary = parseFloat(response.data.salary || 0);
                    if (salary > 0) {
                        $('#salary').val(salary.toFixed(2));
                        $('#daily_rate').val((salary / 22).toFixed(2));
                        updateCutoffAmounts();
                    }
                }
            }, 'json');
        });

        $('#tranche_id, #step_id').on('change', function () {
            const employment_type_id = $('#employment_type_id').val();
            if (employment_type_id == 2) return;

            const tranche_id = $('#tranche_id').val();
            const step_id = $('#step_id').val();
            const url = @json(route('hris.employee.information'));

            $.ajax({
                type: "GET",
                url: url,
                data: { tranche_id, step_id },
                dataType: "json",
                success: function (response) {
                    if (response.data) {
                        let salary = parseFloat(response.data.salary || 0);
                        if (salary > 0) {
                            $('#salary').val(salary.toFixed(2));
                            $('#daily_rate').val((salary / 22).toFixed(2));
                            updateCutoffAmounts();
                        }
                    }
                }
            });
        });

        $('#salary_frequency').on('change', function () {
            const frequency = $(this).val();
            $('.cutoff').hide();

            if (frequency === 'once') {
                $('#salary_cutoff_container').show();
                handleSalaryCutoff();
                updateCutoffAmounts();
            } else if (frequency === 'twice') {
                $('#salary_cutoff_container').hide();
                $('#salary_cutoff').val('');
                $('.first-cutoff, .second-cutoff').show();
                updateCutoffAmounts();
            } else {
                $('#salary_cutoff_container').hide();
                $('#salary_cutoff').val('');
            }
        });

        $('#salary_cutoff').on('change', function () {
            const frequency = $('#salary_frequency').val();
            const cutoff = $(this).val();

            handleSalaryCutoff();

            if (frequency === 'once' && cutoff) {
                $('.first-cutoff input, .second-cutoff input').val('0.00');
                $('#salary').val(salary ?? 0.00);
                $('#daily_rate').val(daily_rate ?? 0.00);
                updateCutoffAmounts();
            } else {
                $('#salary_frequency').trigger('change');
                updateCutoffAmounts();
            }
        });

        $('#salary').on('keyup', function () {
            const salary = parseFloat($(this).val()) || 0.00;
            const frequency = $('#salary_frequency').val();

            $('#daily_rate').val((salary / 22).toFixed(2));
            updateCutoffAmounts();
        });

        $('#salary_frequency').trigger('change');
        $('#salary_cutoff').trigger('change');
        $('#employment_type_id').trigger('change');

        function handleSalaryCutoff() {
            const selected = $('#salary_cutoff').val();
            $('.cutoff').hide();

            if (selected === 'first_cutoff') {
                $('.first-cutoff').show();
            } else if (selected === 'second_cutoff') {
                $('.second-cutoff').show();
            }
        }

        function updateCutoffAmounts() {
            let salary = parseFloat($('#salary').val()) || 0.00;
            const frequency = $('#salary_frequency').val();
            const cutoff = $('#salary_cutoff').val();

            if (frequency === 'twice') {
                const half = (salary / 2).toFixed(2);
                $('#first_cutoff_amount').val(half);
                $('#second_cutoff_amount').val(half);
            } else if (frequency === 'once') {
                if (cutoff === 'first_cutoff') {
                    $('#first_cutoff_amount').val(salary.toFixed(2));
                    $('#second_cutoff_amount').val('');
                } else if (cutoff === 'second_cutoff') {
                    $('#second_cutoff_amount').val(salary.toFixed(2));
                    $('#first_cutoff_amount').val('');
                }
            } else {
                $('#first_cutoff_amount').val('');
                $('#second_cutoff_amount').val('');
            }
        }
    });
</script>
@endsection

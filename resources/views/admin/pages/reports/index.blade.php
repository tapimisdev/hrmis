@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Reports" subtitle="View reports based on processed payroll">
        </x-header>
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-uppercase fw-bold active" id="pills-employee-tab" data-bs-toggle="pill" data-bs-target="#pills-employee" type="button" role="tab" aria-controls="pills-employee" aria-selected="true">Employee</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-uppercase fw-bold" id="pills-payroll-tab" data-bs-toggle="pill" data-bs-target="#pills-payroll" type="button" role="tab" aria-controls="pills-payroll" aria-selected="false">Payroll</button>
                    </li>
                    </li>
                </ul>
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="pills-employee" role="tabpanel" aria-labelledby="pills-employee-tab" tabindex="0">
                        <div class="alert alert-info">Pull out records based on your desired filter results</div>
                        <div class="row">
                            <div class="col-12 col-md-3 mb-3">
                                <label for="employment_type" class="mb-2">Employment Type</label>
                                <select name="employment_type" id="employment_type" class="form-select">
                                    <option value=""> - CHOOSE - </option>
                                    <option value="regular">Regular / Plantilla</option>
                                    <option value="cos">COS / Contractual</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3 mb-3">
                                <label for="preferred_selection" class="mb-2">Preferred Selection</label>
                                <select name="preferred_selection" id="preferred_selection" class="form-select">
                                    <option value="single">Single</option>
                                    <option value="multiple">Multiple</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3 mb-3">
                                <label for="gender" class="mb-2">Gender</label>
                                <select name="gender" id="gender" class="form-select">
                                    <option value="all">All</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3 mb-3">
                                <label for="date_hired" class="mb-2">Date Hired</label>
                                <input type="date" name="date_hired" id="date_hired" class="form-control">
                            </div>
                            <div class="col-12 col-md-3 mb-3">
                                <label for="gender" class="mb-2">Gender</label>
                                <select name="gender" id="gender" class="form-select">
                                    <option value="all">All</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade show active" id="pills-payroll" role="tabpanel" aria-labelledby="pills-payroll-tab" tabindex="0">

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {

        const registry = @json($payroll_registry);
        console.log(registry);

        $('#report_type').on('change', function() {
            handleReportType();
        });

        function handleReportType() {
            let val = $('#report_type').val();
            let container = $('.date_preference_container');

            container.empty();

            if (val === 'employee_information') {
                container.append(`
                    <label for="date_preference" class="mb-2">Date Preference</label>
                    <input type="text" name="date_preference" id="date_preference" class="form-control datepicker">
                `);
                $("#date_preference").daterangepicker();
            } else {
                let container = $('.date_preference_container');
                container.empty();
                let selectHtml = `
                    <label for="date_preference" class="mb-2">Date Preference</label>
                    <select name="date_preference" id="date_preference" class="form-control">
                        <option value=""> - CHOOSE - </option>
                `;
                registry.forEach(item => {
                    selectHtml += `<option value="${item.id}">${item.label} - (${item.period_covered})</option>`;
                });
                selectHtml += `</select>`;
                container.append(selectHtml);
            }
        }

        $('#preferred_selection').on('change', function() {
            handleEmployeeSelection();
        });

        function handleEmployeeSelection() {
            let val = $('#preferred_selection').val();
            let employeeSelect = $('#employee_no');

            if (val === 'multiple') {
                employeeSelect.attr('multiple', 'multiple').addClass('select2').select2();

                employeeSelect.find('option:first').remove();
            } else {
                if (employeeSelect.hasClass('select2-hidden-accessible')) {
                    employeeSelect.select2('destroy');
                }
                employeeSelect.removeAttr('multiple').removeClass('select2');

                if (employeeSelect.find('option:first').val() !== "") {
                    employeeSelect.prepend('<option value="" selected> - ALL - </option>');
                }
            }
        }


    });
</script>
@endsection
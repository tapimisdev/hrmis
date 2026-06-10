@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header
            title="Transfer Shift"
            subtitle="Assign a new shift and work schedule to one or more employees"
        >
            <x-button-link
                :href="route('hris.employee.index')"
                icon="fa-solid fa-arrow-left me-2"
                text="Back"
                variant="danger"
            />
        </x-header>

        <form id="form-transfer-shift" action="{{ route('hris.employee.transfer-shift') }}" method="post">
            @csrf

            <div class="card shadow">
                <div class="card-body">
                    <div class="alert alert-info">
                        The effectivity date will be set to the date this transfer is processed.
                        Existing shift history will be retained.
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="mb-2" for="employees">
                                Choose Employees <span class="text-danger">*</span>
                            </label>
                            <select
                                id="employees"
                                name="employees[]"
                                class="form-select select2"
                                multiple
                                style="width: 100%"
                            >
                                @foreach ($employees as $employee)
                                    <option
                                        value="{{ $employee->employee_no }}"
                                        @selected(in_array($employee->employee_no, $selectedEmployees, true))
                                    >
                                        {{ trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')) }}
                                        ({{ $employee->employee_no }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="shift_id">
                                Shift <span class="text-danger">*</span>
                            </label>
                            <select id="shift_id" name="shift_id" class="form-select">
                                <option value="">- CHOOSE -</option>
                                @foreach ($shifts as $shift)
                                    <option value="{{ $shift->id }}">{{ strtoupper($shift->name) }}</option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="work_schedule_id">
                                Work Schedule <span class="text-danger">*</span>
                            </label>
                            <select id="work_schedule_id" name="work_schedule_id" class="form-select">
                                <option value="">- CHOOSE -</option>
                                @foreach ($schedules as $schedule)
                                    <option value="{{ $schedule->id }}">{{ strtoupper($schedule->name) }}</option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                    Transfer Shift <i class="fa-solid fa-arrow-right ms-2"></i>
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        $(function () {
            post($('#form-transfer-shift').attr('action'), false, '#form-transfer-shift');
        });
    </script>
@endsection

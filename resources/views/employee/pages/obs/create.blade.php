@extends('employee.layout.app')

@section('content')
<div class="container-fluid min-vh-100">
    <x-employee-navbar>
        <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
    </x-employee-navbar>

    <x-header-employee title="Pass Slip / Apply" subtitle="Create a pass slip application in this module">
        <a href="javascript:history.back()" class="btn btn-danger py-3 px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header-employee>

    <div class="card rounded-3">
        <div class="card-header fw-bold d-flex align-items-center">
            <i class="fa-solid fa-file-pen me-2"></i> Official Business / Pass Slip Application Form
        </div>
        <form method="POST" action="{{ route('obs.store') }}" id="form" enctype="multipart/form-data">
        @csrf
        <div class="card-body">

            <div class="row g-3">
                {{-- Destination --}}
                <div class="col-md-6 mb-3">
                    <label for="destination" class="form-label fw-semibold">
                        Destination <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="destination" id="destination" class="form-control" placeholder="Enter destination">
                    <div class="error-field"></div>
                </div>

                {{-- Purpose --}}
                <div class="col-md-6 mb-3">
                    <label for="purpose" class="form-label fw-semibold">
                        Purpose <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="purpose" id="purpose" class="form-control" placeholder="Enter purpose">
                    <div class="error-field"></div>
                </div>

                {{-- Date From --}}
                <div class="col-md-6 mb-3">
                    <label for="date_from" class="form-label fw-semibold">
                        Date From <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="date_from" id="date_from" class="form-control">
                    <div class="error-field"></div>
                </div>

                {{-- Date To --}}
                <div class="col-md-6 mb-3">
                    <label for="date_to" class="form-label fw-semibold">
                        Date To <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="date_to" id="date_to" class="form-control">
                    <div class="error-field"></div>
                </div>

                {{-- Time Out --}}
                <div class="col-md-6 mb-3">
                    <label for="time_out" class="form-label fw-semibold">Time Out</label>
                    <input type="time" name="time_out" id="time_out" class="form-control">
                    <div class="error-field"></div>
                </div>

                {{-- Time In --}}
                <div class="col-md-6 mb-3">
                    <label for="time_in" class="form-label fw-semibold">Time In</label>
                    <input type="time" name="time_in" id="time_in" class="form-control">
                    <div class="error-field"></div>
                </div>

                {{-- Mode of Transport --}}
                <div class="col-md-6 mb-3">
                    <label for="mode_of_transport" class="form-label fw-semibold">Mode of Transport</label>
                    <input type="text" name="mode_of_transport" id="mode_of_transport" class="form-control" placeholder="Car, Bus, Train...">
                    <div class="error-field"></div>
                </div>

                {{-- Estimated Expense --}}
                <div class="col-md-6 mb-3">
                    <label for="estimated_expense" class="form-label fw-semibold">Estimated Expense</label>
                    <input type="number" name="estimated_expense" id="estimated_expense" class="form-control" placeholder="0.00" min="0" step="0.01">
                    <div class="error-field"></div>
                </div>

                {{-- Charge To --}}
                <div class="col-md-12 mb-3">
                    <label for="charge_to" class="form-label fw-semibold">Charge To</label>
                    <input type="text" name="charge_to" id="charge_to" class="form-control" placeholder="Department/Cost Center">
                    <div class="error-field"></div>
                </div>

                {{-- Note --}}
                <div class="col-md-12 mb-3">
                    <label for="remarks" class="form-label fw-semibold">Note</label>
                    <textarea name="remarks" id="remarks" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    <div class="error-field"></div>
                </div>

                {{-- Attachments --}}
                <div class="col-md-12 mb-3">
                    <label for="attachments" class="form-label fw-semibold">Attachments</label>
                    <input type="file" name="attachments[]" id="attachments" class="form-control" multiple>
                    <div class="error-field"></div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-transparent border-0 mt-4 mb-4">
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4 py-2">
                    <i class="fa-solid fa-paper-plane me-2"></i> Submit
                </button>
            </div>
        </div>
    </form>

    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function() {
        const data = @json($applications);
        const url = $('#form').attr('action');
        post(url);
    });
</script>
@endsection
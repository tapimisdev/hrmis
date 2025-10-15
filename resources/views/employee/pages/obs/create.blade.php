@extends('employee.layout.app')

@section('content')
<div class="container-fluid pt-3">

    <header-vue title="DOST TAPI"></header-vue>

    <x-header-employee title="Apply for Official Business Slip" subtitle="Create official business slip application in this module">
        <a href="javascript:history.back()" class="btn btn-outline-danger py-3 px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header-employee>

    <div class="card rounded-3">
        <div class="card-header fw-bold d-flex align-items-center">
            <i class="fa-solid fa-file-pen me-2"></i> OBS Application Form
        </div>
        <form method="POST" id="myForm" enctype="multipart/form-data">
            @csrf
            <div class="card-body">

                <div class="row g-3">
                    {{-- Destination --}}
                    <div class="col-md-6">
                        <label for="destination" class="form-label fw-semibold">Destination <span class="text-danger">*</span></label>
                        <input type="text" name="destination" id="destination" class="form-control" placeholder="Enter destination" required>
                        <span id="destination_error" class="text-danger d-none"></span>
                    </div>

                    {{-- Purpose --}}
                    <div class="col-md-6">
                        <label for="purpose" class="form-label fw-semibold">Purpose <span class="text-danger">*</span></label>
                        <input type="text" name="purpose" id="purpose" class="form-control" placeholder="Enter purpose" required>
                        <span id="purpose_error" class="text-danger d-none"></span>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    {{-- Date From --}}
                    <div class="col-md-6">
                        <label for="date_from" class="form-label fw-semibold">Date From <span class="text-danger">*</span></label>
                        <input type="date" name="date_from" id="date_from" class="form-control" required>
                        <span id="date_from_error" class="text-danger d-none"></span>
                    </div>

                    {{-- Date To --}}
                    <div class="col-md-6">
                        <label for="date_to" class="form-label fw-semibold">Date To <span class="text-danger">*</span></label>
                        <input type="date" name="date_to" id="date_to" class="form-control" required>
                        <span id="date_to_error" class="text-danger d-none"></span>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    {{-- Time Out --}}
                    <div class="col-md-6">
                        <label for="time_out" class="form-label fw-semibold">Time Out</label>
                        <input type="time" name="time_out" id="time_out" class="form-control">
                        <span id="time_out_error" class="text-danger d-none"></span>
                    </div>

                    {{-- Time In --}}
                    <div class="col-md-6">
                        <label for="time_in" class="form-label fw-semibold">Time In</label>
                        <input type="time" name="time_in" id="time_in" class="form-control">
                        <span id="time_in_error" class="text-danger d-none"></span>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    {{-- Mode of Transport --}}
                    <div class="col-md-6">
                        <label for="mode_of_transport" class="form-label fw-semibold">Mode of Transport</label>
                        <input type="text" name="mode_of_transport" id="mode_of_transport" class="form-control" placeholder="Car, Bus, Train...">
                        <span id="mode_of_transport_error" class="text-danger d-none"></span>
                    </div>

                    {{-- Estimated Expense --}}
                    <div class="col-md-6">
                        <label for="estimated_expense" class="form-label fw-semibold">Estimated Expense</label>
                        <input type="number" name="estimated_expense" id="estimated_expense" class="form-control" placeholder="0.00" min="0" step="0.01">
                        <span id="estimated_expense_error" class="text-danger d-none"></span>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    {{-- Charge To --}}
                    <div class="col-md-6">
                        <label for="charge_to" class="form-label fw-semibold">Charge To</label>
                        <input type="text" name="charge_to" id="charge_to" class="form-control" placeholder="Department/Cost Center">
                        <span id="charge_to_error" class="text-danger d-none"></span>
                    </div>

                    {{-- Remarks --}}
                    <div class="col-md-6">
                        <label for="remarks" class="form-label fw-semibold">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                        <span id="remarks_error" class="text-danger d-none"></span>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    {{-- Attachments --}}
                    <div class="col-12">
                        <label for="attachments" class="form-label fw-semibold">Attachments (optional)</label>
                        <input type="file" name="attachments[]" id="attachments" class="form-control" multiple>
                        <span id="attachments_error" class="text-danger d-none"></span>
                    </div>
                </div>

            </div>

            <div class="card-footer bg-light">
                <div class="d-flex justify-content-end">
                    <button id="submit-button" type="button" class="btn btn-primary px-4 py-2">
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
    $(document).ready(function() {
        $('#submit-button').click(e => {
            e.preventDefault();

            $('.text-danger').addClass('d-none');
            $('.form-control').removeClass('is-invalid');
            $('.form-select').removeClass('is-invalid');

            let form = $('#myForm')[0];
            let formData = new FormData(form);

            $('#submit-button').prop('disabled', true);

            axios.post(`{{ route('obs.store') }}`, formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then((response) => {
                Swal.fire({
                    title: "Success!",
                    text: "Your official business slip has been submitted.",
                    icon: "success"
                }).then(() => {
                    window.location.href = "{{ route('obs.index') }}";
                });
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    $.each(error.response.data.errors, function(field, errorMessage) {
                        var errorSpanId = '#' + field + '_error';
                        $(`#${field}`).addClass('is-invalid');
                        $(errorSpanId).removeClass('d-none').text(errorMessage[0]);
                    });
                } else {
                    Swal.fire({
                        title: "Oops!",
                        text: error.response?.data?.message || "Something went wrong.",
                        icon: "error"
                    });
                }
            })
            .finally(() => {
                $('#submit-button').prop('disabled', false);
            });
        })
    });
</script>
@endsection

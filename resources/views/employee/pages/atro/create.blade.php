@extends('employee.layout.app')

@section('content')
<div class="container-fluid pt-3 pt-4">

    <header-vue title="DOST TAPI"></header-vue>

    <x-header-employee title=" Overtime / Apply" subtitle="Create overtime application in this module">
        <a href="javascript:history.back()" class="btn btn-danger py-3 px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header-employee>

    <div class="card rounded-3">
        <div class="card-header fw-bold d-flex align-items-center">
            <i class="fa-solid fa-clock me-2"></i> Overtime Application Form
        </div>
        <form method="POST" id="myForm" enctype="multipart/form-data">
            @csrf
            <div class="card-body">

                <div class="row g-3">
                    {{-- Date --}}
                    <div class="col-md-12">
                        <label for="date" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="date" class="form-control" required>
                        <span id="date_error" class="text-danger d-none"></span>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    {{-- Start Time --}}
                    <div class="col-md-4">
                        <label for="start_time" class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" id="start_time" class="form-control" required>
                        <span id="start_time_error" class="text-danger d-none"></span>
                    </div>

                    {{-- End Time --}}
                    <div class="col-md-4">
                        <label for="end_time" class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                        <input type="time" name="end_time" id="end_time" class="form-control" required>
                        <span id="end_time_error" class="text-danger d-none"></span>
                    </div>

                     {{-- Total Hours --}}
                    <div class="col-md-4">
                        <label for="total_hours" class="form-label fw-semibold">Total Hours</label>
                        <input type="number" step="0.01" min="0" name="total_hours" id="total_hours" class="form-control" placeholder="Optional (auto-calculated)">
                        <span id="total_hours_error" class="text-danger d-none"></span>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    {{-- Reason --}}
                    <div class="col-12">
                        <label for="reason" class="form-label fw-semibold">Reason</label>
                        <textarea name="reason" id="reason" class="form-control" rows="4" placeholder="State your reason..."></textarea>
                        <span id="reason_error" class="text-danger d-none"></span>
                    </div>
                </div>

            </div>

            <div class="card-footer bg-light">
                <div class="d-flex justify-content-end">
                    <button id="submit-overtime" type="button" class="btn btn-primary px-4 py-3">
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
        $('#submit-overtime').click(e => {
            e.preventDefault();

            $('.text-danger').addClass('d-none');
            $('.form-control').removeClass('is-invalid');
            $('.form-select').removeClass('is-invalid');

            let form = $('#myForm')[0];
            let formData = new FormData(form); // handles file uploads

            $('#submit-overtime').prop('disabled', true);

            axios.post(`{{ route('overtime.store') }}`, formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then((response) => {
                Swal.fire({
                    title: "Success!",
                    text: "Your overtime application has been submitted.",
                    icon: "success"
                }).then(() => {
                    window.location.href = "{{ route('overtime.index') }}";
                });
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    // Validation errors
                    $.each(error.response.data.errors, function(field, errorMessage) {
                        var errorSpanId = '#' + field + '_error';
                        $(`#${field}`).addClass('is-invalid');

                        // Show the error message in the respective error span
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
                $('#submit-overtime').prop('disabled', false);
            });
        });

        // Optional: auto-calculate total hours
        $('#start_time, #end_time').on('change', function() {
            let start = $('#start_time').val();
            let end = $('#end_time').val();
            if (start && end) {
                let diff = (new Date('1970-01-01T' + end) - new Date('1970-01-01T' + start)) / 1000 / 3600;
                $('#total_hours').val(diff > 0 ? diff.toFixed(2) : 0);
            }
        });
    });
</script>
@endsection

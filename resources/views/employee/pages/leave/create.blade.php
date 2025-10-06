@extends('employee.layout.app')

@section('content')
<div class="container pt-4">

    <x-header title="Apply for Leave" subtitle="Create leave application in this module">
        <a href="javascript:history.back()" class="btn btn-outline-danger py-3 px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header>

    <div class="card rounded-3 mb-5">
        <div class="card-header fw-bold d-flex align-items-center">
            <i class="fa-solid fa-file-pen me-2"></i> Application Form
        </div>
        <form method="POST" id="myForm" enctype="multipart/form-data">
            @csrf
            <div class="card-body">

                <div class="row g-3">
                    {{-- Leave Type --}}
                    <div class="col-md-6">
                        <label for="leave_id" class="form-label fw-semibold">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_id" id="leave_id" class="form-select" required>
                            <option value="">-- Select Leave Type --</option>
                            @foreach ($leaves as $leave)
                                <option value="{{ $leave->id }}">{{ $leave->name }}</option>
                            @endforeach
                        </select>
                        <span id="leave_id_error" class="text-danger d-none"></span>
                    </div>

                    {{-- Number of Days --}}
                    <div class="col-md-6">
                        <label for="days" class="form-label fw-semibold">Number of Days</label>
                        <input type="number" name="days" id="days" class="form-control" min="1" value="1" required>
                        <span id="days_error" class="text-danger d-none"></span>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    {{-- Start Date --}}
                    <div class="col-md-6">
                        <label for="start_date" class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" class="form-control" required>
                        <span id="start_date_error" class="text-danger d-none"></span>
                    </div>

                    {{-- End Date --}}
                    <div class="col-md-6">
                        <label for="end_date" class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="end_date" class="form-control" required>
                        <span id="end_date_error" class="text-danger d-none"></span>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    {{-- Reason --}}
                    <div class="col-12">
                        <label for="reason" class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" class="form-control" rows="4" placeholder="State your reason..." required></textarea>
                        <span id="reason_error" class="text-danger d-none"></span>
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
            let formData = new FormData(form); // handles file uploads

            $('#submit-button').prop('disabled', true);

            axios.post(`{{ route('leaves.store') }}`, formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then((response) => {
                Swal.fire({
                    title: "Success!",
                    text: "Your leave application has been submitted.",
                    icon: "success"
                }).then(() => {
                    window.location.href = "{{ route('leaves.index') }}";
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
                $('#submit-button').prop('disabled', false);
            });
        })
    });
</script>
@endsection

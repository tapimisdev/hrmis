@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
 <div class="container p-4 pb-5">
    <x-header title="Shift Schedule" subtitle="Manage shift schedule in this module">
        <a href="{{ route('shift.index') }}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header>
    <form id="form" method="post">
        @csrf
        <div class="card shadow p-3">
            <div class="card-header bg-transparent">
                <h4 class="m-0 mb-1 pt-3 text-uppercase fw-medium">
                    Edit Shift
                </h4>
            </div>

            <div class="card-body">
                <div class="row my-3">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="name">Shift Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control"
                            value="{{ $shift->name ?? '' }}">
                        <div class="text-danger name_error"></div>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="is_flexible">Flexible</label>
                        <select id="is_flexible" name="is_flexible" class="form-select">
                            <option value="0" {{ (isset($shift) && !$shift->is_flexible) ? 'selected' : '' }}>No</option>
                            <option value="1" {{ (isset($shift) && $shift->is_flexible) ? 'selected' : '' }}>Yes</option>
                        </select>
                        <div class="text-danger is_flexible_error"></div>
                    </div>
                </div>

                <div class="row my-3">
                    <div class="col-12 col-md-4 mb-3 {{ (isset($shift) && $shift->is_flexible) ? '' : 'd-none' }}" 
                        id="earliest_time_container">
                        <label class="mb-2" for="earliest_time">Earliest Time <span class="text-danger">*</span></label>
                        <input type="time" id="earliest_time" name="earliest_time" class="form-control"
                            value="{{ isset($shift->earliest_time) ? \Carbon\Carbon::parse($shift->earliest_time)->format('H:i') : '' }}">
                        <div class="text-danger earliest_time_error"></div>
                    </div>

                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="start_time">Start Time <span class="text-danger">*</span></label>
                        <input type="time" id="start_time" name="start_time" class="form-control"
                            value="{{ isset($shift->start_time) ? \Carbon\Carbon::parse($shift->start_time)->format('H:i') : '' }}">
                        <div class="text-danger start_time_error"></div>
                    </div>

                    <div class="col-12 col-md-4 {{ (isset($shift) && $shift->is_flexible) ? 'd-none' : '' }}" 
                        id="end_time_container">
                        <label class="mb-2" for="end_time">End Time <span class="text-danger">*</span></label>
                        <input type="time" id="end_time" name="end_time" class="form-control"
                            value="{{ isset($shift->end_time) ? \Carbon\Carbon::parse($shift->end_time)->format('H:i') : '' }}">
                        <div class="text-danger end_time_error"></div>
                    </div>

                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="minimum_overtime_hours">Minimum Overtime Hours</label>
                        <input type="number" step="0.01" min="0" id="minimum_overtime_hours" 
                            name="minimum_overtime_hours" class="form-control"
                            value="{{ $shift->minimum_overtime_hours ?? '' }}">
                        <div class="text-danger minimum_overtime_hours_error"></div>
                    </div>
                </div>

                <div class="row my-3">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="break_out_time">Break Out Time</label>
                        <input type="time" id="break_out_time" name="break_out_time" class="form-control"
                            value="{{ isset($shift->break_out_time) ? \Carbon\Carbon::parse($shift->break_out_time)->format('H:i') : '' }}">
                        <div class="text-danger break_out_time_error"></div>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="break_in_time">Break In Time</label>
                        <input type="time" id="break_in_time" name="break_in_time" class="form-control"
                            value="{{ isset($shift->break_in_time) ? \Carbon\Carbon::parse($shift->break_in_time)->format('H:i') : '' }}">
                        <div class="text-danger break_in_time_error"></div>
                    </div>
                </div>

                <div class="row my-3">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="is_break_required">Break Required</label>
                        <select id="is_break_required" name="is_break_required" class="form-select">
                            <option value="1" {{ (isset($shift) && $shift->is_break_required) ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ (isset($shift) && isset($shift->is_break_required) && !$shift->is_break_required) ? 'selected' : '' }}>No</option>
                        </select>
                        <div class="text-danger is_break_required_error"></div>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="is_night_shift">Night Shift</label>
                        <select id="is_night_shift" name="is_night_shift" class="form-select">
                            <option value="0" {{ (isset($shift) && !$shift->is_night_shift) ? 'selected' : '' }}>No</option>
                            <option value="1" {{ (isset($shift) && $shift->is_night_shift) ? 'selected' : '' }}>Yes</option>
                        </select>
                        <div class="text-danger is_night_shift_error"></div>
                    </div>
                </div>
            </div>

            <div class="card-footer border-top bg-transparent border-0 pt-4 d-flex justify-content-end">
                <button type="submit" id="update-button"
                        class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                    {{ isset($shift) ? 'Update' : 'Save' }}
                </button>
            </div>
        </div>
    </form>


 </div>
@endsection

@section('scripts')
<script>
$(function() {

    function toggleFlexibleFields() {
        let selected = $("#is_flexible").val();

        if (selected == 1) {
            // Flexible shift
            $("#earliest_time_container").removeClass("d-none");
            $("#earliest_time").prop("disabled", false);

            $("#end_time_container").addClass("d-none");
            $("#end_time").prop("disabled", true).val('');
        } else {
            // Fixed shift
            $("#end_time_container").removeClass("d-none");
            $("#end_time").prop("disabled", false);

            $("#earliest_time_container").addClass("d-none");
            $("#earliest_time").prop("disabled", true).val('');
        }
    }

    // Initialize on page load
    toggleFlexibleFields();

    // Change event
    $("#is_flexible").on("change", toggleFlexibleFields);

    // Handle form submission for update
        $(document).on("click", "#update-button", function (e) {
            e.preventDefault();
            $(".error-field").text("");

            let formData = {
                _token: $("input[name=_token]").val(),
                name: $("#name").val(),
                is_flexible: $("#is_flexible").val(),
                earliest_time: $("#earliest_time").val(),
                start_time: $("#start_time").val(),
                end_time: $("#end_time").val(),
                minimum_overtime_hours: $("#minimum_overtime_hours").val(),
                break_out_time: $("#break_out_time").val(),
                break_in_time: $("#break_in_time").val(),
                is_break_required: $("#is_break_required").val(),
                is_night_shift: $("#is_night_shift").val(),
            };

            // Get shift id from a hidden input or JS variable
            let shiftId = "{{ $shift->id ?? '' }}";
            axios.put(`/admin/settings/shift/${shiftId}`, formData)
                .then(function (response) {
                    Swal.fire({
                        icon: "success",
                        title: "Updated!",
                        text: "Shift has been updated successfully.",
                        timer: 2000,
                        showConfirmButton: false
                    });
                })
                .catch(function (error) {
                    if (error.response && error.response.status === 422) {
                         // Remove previous error states
                        $('.is-invalid').removeClass('is-invalid');
                        $('.error-field').text('');
                        // Show new errors
                        $.each(error.response.data.errors, function(field, errorMessage) {
                            $(`#${field}`).addClass('is-invalid');
                            $(`.${field}_error`).text(errorMessage[0]);
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Oops...",
                            text: "Something went wrong!",
                        });
                    }
                });
        });
});
</script>

@endsection
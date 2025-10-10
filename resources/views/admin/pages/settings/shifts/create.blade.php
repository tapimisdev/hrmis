@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
 <div class="container pt-4 px-3">
    <x-header title="Shift Schedule" subtitle="Manage shift schedule in this module">
        <x-button-link 
            :href="route('shift.index')" 
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
        />
    </x-header>
    <form id="form" id="myForm" method="post">
        @csrf
        <div class="card shadow p-3">
            <div class="card-header bg-transparent">
                <h4 class="m-0 mb-1 pt-3 text-uppercase fw-medium">
                    Create Shift
                </h4>
            </div>

            <div class="card-body">
                <div class="row my-3">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="name">Shift Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control">
                        <div class="name_error"></div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="is_night_shift">Flexible</label>
                        <select id="is_flexible" name="is_night_shift" class="form-select">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                        <div class="is_flexible_error"></div>
                    </div>
                </div>

                <div class="row my-3">
                    <div class="col-12 col-md-4 mb-3 d-none" id="earliest_time_container">
                        <label class="mb-2" for="earliest_time">Earliest Time <span class="text-danger">*</span></label>
                        <input type="time" id="earliest_time" name="earliest_time" class="form-control">
                        <div class="earliest_time_error"></div>
                    </div>

                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="start_time">Start Time <span class="text-danger">*</span></label>
                        <input type="time" id="start_time" name="start_time" class="form-control">
                        <div class="start_time_error"></div>
                    </div>

                    <div class="col-12 col-md-4" id="end_time_container">
                        <label class="mb-2" for="end_time">End Time <span class="text-danger">*</span></label>
                        <input type="time" id="end_time" name="end_time" class="form-control">
                        <div class="end_time_error"></div>
                    </div>

                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="minimum_overtime_hours">Minimum Overtime Hours</label>
                        <input type="number" step="0.01" min="0" id="minimum_overtime_hours" name="minimum_overtime_hours"
                            class="form-control">
                        <div class="minimum_overtime_hours_error"></div>
                    </div>
                </div>

                <div class="row my-3">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="break_out_time">Break Out Time</label>
                        <input type="time" id="break_out_time" name="break_out_time" class="form-control">
                        <div class="break_out_time_error"></div>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="break_in_time">Break In Time</label>
                        <input type="time" id="break_in_time" name="break_in_time" class="form-control">
                        <div class="break_in_time_error"></div>
                    </div>
                </div>

                
                <div class="row my-3">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="is_break_required">Break Required</label>
                        <select id="is_break_required" name="is_break_required" class="form-select">
                            <option value="1" selected>Yes</option>
                            <option value="0">No</option>
                        </select>
                        <div class="is_break_required_error"></div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="is_night_shift">Night Shift</label>
                        <select id="is_night_shift" name="is_night_shift" class="form-select">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                        <div class="is_night_shift_error"></div>
                    </div>
                </div>

            </div>

            <div class="card-footer border-top bg-transparent border-0 pt-4 d-flex justify-content-end">
                <button type="button" id="submit-button"
                        class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                    Save
                </button>
            </div>
        </div>
    </form>

 </div>
@endsection

@section('scripts')
<script>
    $(function() {

        $("#is_flexible").on("change", function(){
            let selected = $(this).val();

           if (selected == 1) {
                $("#earliest_time_container").removeClass("d-none");
                $("#earliest_time").prop("disabled", false);

                $("#end_time").val('').prop("disabled", true);
                $("#end_time_container").addClass("d-none");
            } else {
                $("#end_time_container").removeClass("d-none");
                $("#end_time").prop("disabled", false);

                $("#earliest_time").val('').prop("disabled", true);
                $("#earliest_time_container").addClass("d-none");
            }


        });

        $('#submit-button').click(e => {
            e.preventDefault();
            $('#submit-button').prop('disabled', true);

            // Clear previous errors
            $('.is-invalid').removeClass('is-invalid');
            $('.error-field').text('');

            const formData = {
                name: $('#name').val(),
                earliest_time: $('#earliest_time').val(),
                start_time: $('#start_time').val(),
                end_time: $('#end_time').val(),
                minimum_overtime_hours: $('#minimum_overtime_hours').val(),
                break_out_time: $('#break_out_time').val(),
                break_in_time: $('#break_in_time').val(),
                is_break_required: $('#is_break_required').val(),
                is_night_shift: $('#is_night_shift').val(),
                is_flexible: $('#is_flexible').val(),
                _token: $('input[name="_token"]').val()
            };

            axios.post(`/admin/settings/shift`, formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then((response) => {
                Swal.fire({
                    title: "Success!",
                    text: "Shift has been saved.",
                    icon: "success"
                }).then(() => {
                    window.location.href = "{{ route('shift.index') }}";
                });
            })
            .catch(error => {
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
                        title: "Oops!",
                        text: "Something went wrong, try again later!",
                        icon: "error"
                    });
                }
            }).finally(() => {
                $('#submit-button').prop('disabled', false);
            });
        });
    });
</script>
@endsection
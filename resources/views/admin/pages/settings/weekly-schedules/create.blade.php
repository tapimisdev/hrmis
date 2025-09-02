@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
 <div class="container p-4 pb-5">

    <x-header title="Weekly Schedule" subtitle="Add weekly schedule  in this module">
        <a href="{{ route('weekly-schedules.index') }}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header>
    <form id="form" method="post">
        @csrf
        <div class="card shadow p-3">
            <div class="card-header bg-transparent">
                <h4 class="m-0 mb-1 pt-3 text-uppercase fw-medium">
                    Create Weekly Schedule
                </h4>
            </div>
            <div class="card-body">
                <div class="row my-3">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="name">Schedule Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control">
                        <div class="name_error error-field"></div>
                    </div>
                </div>
                <div class="row my-3">
                    <div class="col-12">
                        <label class="mb-2">Days Included</label>
                        <div class="d-flex flex-wrap gap-3 days">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_monday" name="is_monday" value="1" checked>
                                <label class="form-check-label" for="is_monday">Monday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_tuesday" name="is_tuesday" value="1" checked>
                                <label class="form-check-label" for="is_tuesday">Tuesday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_wednesday" name="is_wednesday" value="1" checked>
                                <label class="form-check-label" for="is_wednesday">Wednesday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_thursday" name="is_thursday" value="1" checked>
                                <label class="form-check-label" for="is_thursday">Thursday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_friday" name="is_friday" value="1" checked>
                                <label class="form-check-label" for="is_friday">Friday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_saturday" name="is_saturday" value="1">
                                <label class="form-check-label" for="is_saturday">Saturday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_sunday" name="is_sunday" value="1">
                                <label class="form-check-label" for="is_sunday">Sunday</label>
                            </div>
                        </div>
                        <div class="days_error error-field"></div>
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

        $('#submit-button').click(e => {
            e.preventDefault();
            $('#submit-button').prop('disabled', true);

            // Clear previous errors
            $('.is-invalid').removeClass('is-invalid');
            $('.error-field').text('');

            const formData = {
                name: $('#name').val(),
                is_monday: $('#is_monday').prop('checked'),
                is_tuesday: $('#is_tuesday').prop('checked'),
                is_wednesday: $('#is_wednesday').prop('checked'),
                is_thursday: $('#is_thursday').prop('checked'),
                is_friday: $('#is_friday').prop('checked'),
                is_saturday: $('#is_saturday').prop('checked'),
                is_sunday: $('#is_sunday').prop('checked'),
                _token: $('input[name="_token"]').val()
            };

            axios.post(`/admin/settings/weekly-schedules`, formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then((response) => {
                Swal.fire({
                    title: "Success!",
                    text: "Weekly Schedule has been saved.",
                    icon: "success"
                }).then(() => {
                    window.location.href = "{{ route('weekly-schedules.index') }}";
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
@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
 <div class="container p-4 pb-5">

    <x-header title="Leave" subtitle="Add Leave in this module">
        <a href="{{ route('settings.leaves.index') }}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header>
    <form id="form" method="post">
        @csrf
        <div class="card shadow p-3">
            <div class="card-header bg-transparent">
                <h4 class="m-0 mb-1 pt-3 text-uppercase fw-medium">
                    Create Leave
                </h4>
            </div>
            <div class="card-body">
                <div class="row my-3">
                    <div class="col-12 col-md-9 mb-3">
                        <label class="mb-2" for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" placeholder="Enter the name of deduction in here" class="form-control">
                        <div class="name_error error-field"></div>
                    </div>
                </div>
                <div class="row my-3">
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="is_cumulative">Is Cumulative</label>
                        <select id="is_cumulative" name="is_cumulative" class="form-select">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                        <div class="is_cumulative_error"></div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="no_of_days">No. of Days <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="no_of_days" placeholder="0" name="no_of_days" class="form-control">
                        <div class="no_of_days_error error-field"></div>
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
                is_cumulative: $('#is_cumulative').val(),
                no_of_days: $('#no_of_days').val(),
                _token: $('input[name="_token"]').val()
            };

            axios.post(`/admin/settings/leaves`, formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then((response) => {
                Swal.fire({
                    title: "Success!",
                    text: "Leave has been saved.",
                    icon: "success"
                }).then(() => {
                    window.location.href = "{{ route('settings.leaves.index') }}";
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
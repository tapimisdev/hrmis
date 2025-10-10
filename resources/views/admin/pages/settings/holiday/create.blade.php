@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
 <div class="container p-4 pb-5">

    <x-header title="Holiday" subtitle="Manage holiday in this module">
        <x-button-link 
            :href="route('holiday.index')" 
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
        />
    </x-header>
    <form id="form" method="post">
        @csrf
        <div class="card shadow p-3">
            <div class="card-header bg-transparent">
                <h4 class="m-0 mb-1 pt-3 text-uppercase fw-medium">
                    Create Holiday
                </h4>
            </div>
            <div class="card-body">
                <div class="row my-3">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="name">Holiday Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control">
                        <div class="name_error error-field"></div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="date">Date <span class="text-danger">*</span></label>
                        <input type="date" id="date" name="date" class="form-control">
                        <div class="date_error error-field"></div>
                    </div>
                </div>
                <div class="row my-3">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="type">Type <span class="text-danger">*</span></label>
                        <select id="type" name="type" class="form-select">
                            <option value="regular" selected>Regular Holiday</option>
                            <option value="special_working">Special Working Day</option>
                            <option value="special_non_working">Special Non-working Day</option>
                            <option value="company">Company-declared Holiday</option>
                        </select>
                        <div class="type_error error-field"></div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="is_repeating">Repeats Yearly?</label>
                        <select id="is_repeating" name="is_repeating" class="form-select">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                        <div class="is_repeating_error error-field"></div>
                    </div>
                </div>

                {{-- New Rate Fields --}}
                <div class="row my-3">
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="no_work_rate">No Work Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="no_work_rate" name="no_work_rate" class="form-control" placeholder="e.g., 1.00">
                        <div class="no_work_rate_error error-field"></div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="work_rate">Work Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="work_rate" name="work_rate" class="form-control" placeholder="e.g., 1.30">
                        <div class="work_rate_error error-field"></div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="overtime_rate">Overtime Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="overtime_rate" name="overtime_rate" class="form-control" placeholder="e.g., 1.50">
                        <div class="overtime_rate_error error-field"></div>
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
                date: $('#date').val(),
                type: $('#type').val(),
                is_repeating: $('#is_repeating').val(),
                no_work_rate: $('#no_work_rate').val(),
                work_rate: $('#work_rate').val(),
                overtime_rate: $('#overtime_rate').val(),
                _token: $('input[name="_token"]').val()
            };

            axios.post(`/admin/settings/holiday`, formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then((response) => {
                Swal.fire({
                    title: "Success!",
                    text: "Holiday has been saved.",
                    icon: "success"
                }).then(() => {
                    window.location.href = "{{ route('holiday.index') }}";
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

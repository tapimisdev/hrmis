@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
 <div class="container p-4 pb-5">

    <x-header title="Holiday" subtitle="Manage Holiday in this module">
        <a href="{{ route('holiday.index') }}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header>
    <form id="form" method="post">
        @csrf
        <div class="card shadow p-3">
            <div class="card-header bg-transparent">
                <h4 class="m-0 mb-1 pt-3 text-uppercase fw-medium">
                    Edit Holiday
                </h4>
            </div>
            <div class="card-body">
                <div class="row my-3">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="name">Holiday Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ $holiday->name ?? '' }}">
                        <div class="text-danger name_error error-field"></div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="date">Date <span class="text-danger">*</span></label>
                        <input type="date" id="date" name="date" class="form-control" value="{{ isset($holiday->date) ? \Carbon\Carbon::parse($holiday->date)->format('Y-m-d') : '' }}">
                        <div class="text-danger date_error error-field"></div>
                    </div>
                </div>
                <div class="row my-3">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="type">Type <span class="text-danger">*</span></label>
                        <select id="type" name="type" class="form-select">
                            <option value="regular" {{ (isset($holiday) && $holiday->type == 'regular') ? 'selected' : '' }}>Regular Holiday</option>
                            <option value="special_working" {{ (isset($holiday) && $holiday->type == 'special_working') ? 'selected' : '' }}>Special Working Day</option>
                            <option value="special_non_working" {{ (isset($holiday) && $holiday->type == 'special_non_working') ? 'selected' : '' }}>Special Non-working Day</option>
                            <option value="company" {{ (isset($holiday) && $holiday->type == 'company') ? 'selected' : '' }}>Company-declared Holiday</option>
                        </select>
                        <div class="text-danger type_error error-field"></div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="is_repeating">Repeats Yearly?</label>
                        <select id="is_repeating" name="is_repeating" class="form-select">
                            <option value="0" {{ (isset($holiday) && !$holiday->is_repeating) ? 'selected' : '' }}>No</option>
                            <option value="1" {{ (isset($holiday) && $holiday->is_repeating) ? 'selected' : '' }}>Yes</option>
                        </select>
                        <div class="text-danger is_repeating_error error-field"></div>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top bg-transparent border-0 pt-4 d-flex justify-content-end">
                <button type="button" id="update-button"
                        class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                    Update
                </button>
            </div>
        </div>
    </form>


 </div>
@endsection

@section('scripts')
<script>
$(function() {

    // Handle form submission for update
    $(document).on("click", "#update-button", function (e) {
        e.preventDefault();
        $(".error-field").text("");

        let formData = {
            _token: $("input[name=_token]").val(),
            name: $("#name").val(),
            date: $("#date").val(),
            type: $("#type").val(),
            is_repeating: $("#is_repeating").val(),
        };

        // Get holiday id from a hidden input or JS variable
        let holidayId = "{{ $holiday->id ?? '' }}";
        axios.put(`/admin/settings/holiday/${holidayId}`, formData)
            .then(function (response) {
                Swal.fire({
                    icon: "success",
                    title: "Updated!",
                    text: "Holiday has been updated successfully.",
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
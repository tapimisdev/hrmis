@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
 <div class="container p-4 pb-5">

    <x-header title="Leave" subtitle="Edit Leave in this module">
        <a href="{{ route('settings.leaves.index') }}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header>
    <form id="form" method="post">
        @csrf
        <div class="card shadow p-3">
            <div class="card-header bg-transparent">
                <h4 class="m-0 mb-1 pt-3 text-uppercase fw-medium">
                    Edit Leave
                </h4>
            </div>
            <div class="card-body">
                <div class="row my-3">
                    <div class="col-12 col-md-12 mb-3">
                        <label class="mb-2" for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control"
                            value="{{ $leave->name ?? '' }}">
                        <div class="text-danger name_error error-field"></div>
                    </div>
                </div>
                <div class="row my-3">
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="is_cumulative">Is Cumulative</label>
                        <select value="{{ $leave->is_cumulative ?? '' }}" id="is_cumulative" name="is_cumulative" class="form-select">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                        <div class="is_cumulative_error"></div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="credit_to_deduct">No. of Days <span class="text-danger">*</span></label>
                        <input value="{{ $leave->credit_to_deduct ?? '' }}" type="number" step="0.01" id="credit_to_deduct" placeholder="0" name="no_of_days" class="form-control">
                        <div class="credit_to_deduct_error error-field"></div>
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
            is_cumulative: $("#is_cumulative").val(),
            credit_to_deduct: $("#credit_to_deduct").val(),
            date: $("#date").val(),
        };

        let leaveId = "{{ $leave->id ?? '' }}";

        axios.put(`/admin/settings/leaves/${leaveId}`, formData)
            .then(function (response) {
                Swal.fire({
                    icon: "success",
                    title: "Updated!",
                    text: response.data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            })
            .catch(function (error) {
                if (error.response && error.response.status === 422) {
                    $('.is-invalid').removeClass('is-invalid');
                    $('.error-field').text('');

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
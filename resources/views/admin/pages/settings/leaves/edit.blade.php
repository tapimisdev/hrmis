@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
 <div class="container-fluid">

    <x-header title="Leave" subtitle="Edit Leave in this module">
        <x-button-link 
            :href="route('settings.leaves.index')" 
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
                        <label class="mb-2" for="to_be_credited">To Be Credited <span class="text-danger">*</span></label>
                        <input value="{{ $leave->to_be_credited ?? '' }}" type="number" step="0.01" id="to_be_credited" placeholder="0" name="to_be_credited" class="form-control">
                        <div class="to_be_credited_error error-field"></div>
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
            to_be_credited: $("#to_be_credited").val(),
            date: $("#date").val(),
        };

        let leaveId = "{{ $leave->id ?? '' }}";

        axios.put(`/admin/maintenance/leaves/${leaveId}`, formData)
            .then(function (response) {
                Swal.fire({
                    icon: "success",
                    title: "Updated!",
                    text: response.data.message,
                    timer: 2000,
                    showConfirmButton: true
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
                        title: "Oops!",
                        text: error.response.data.message,
                        icon: "error"
                    });
                }
            });
    });
});
</script>

@endsection
@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
 <div class="container pt-4 px-3">

    <x-header title="Earning" subtitle="Edit Earning in this module">
        <x-button-link 
            :href="route('earnings.index')" 
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
                    Edit Earning
                </h4>
            </div>
            <div class="card-body">
                <div class="row my-3">
                    <div class="col-12 col-md-12 mb-3">
                        <label class="mb-2" for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control"
                            value="{{ $earnings->name ?? '' }}">
                        <div class="text-danger name_error error-field"></div>
                    </div>
                </div>
                <div class="row my-3">
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="first_term">First Term Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="first_term" name="first_term" class="form-control" value="0"
                            value="{{ $earnings->first_term ?? '' }}">
                        <div class="text-danger first_term_error error-field"></div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="second_term">Second Term Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="second_term" name="second_term" class="form-control" value="0"
                            value="{{ $earnings->second_term ?? '' }}">
                        <div class="text-danger second_term_error error-field"></div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="is_taxable">Is Taxable?</label>
                        <select id="is_taxable" name="is_taxable" class="form-select">
                            <option value="0" {{ (isset($earnings) && !$earnings->is_taxable) ? 'selected' : '' }}>No</option>
                            <option value="1" {{ (isset($earnings) && $earnings->is_taxable) ? 'selected' : '' }}>Yes</option>
                        </select>
                        <div class="text-danger is_taxable_error error-field"></div>
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
            first_term: $("#first_term").val(),
            second_term: $("#second_term").val(),
            is_taxable: $('#is_taxable').val(),
            date: $("#date").val(),
        };

        let earningsId = "{{ $earnings->id ?? '' }}";

        axios.put(`/admin/maintenance/earnings/${earningsId}`, formData)
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
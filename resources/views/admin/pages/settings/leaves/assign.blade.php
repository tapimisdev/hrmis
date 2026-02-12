@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <x-header title="Assign Leaves" subtitle="Manage and assign where to deduct the particular leave type">
        <x-button-link 
            :href="route('settings.leaves.index')" 
            icon="fa-solid fa-arrow-left me-2" 
            text="Go Back" 
            variant="danger"
        />
    </x-header>

    <form id="form" action="/admin/maintenance/leaves/assign" method="post">
        @csrf
        <div class="alert alert-info text-uppercase mb-4 mt-5">
            Use this module to assign where each leave type’s credits should be deducted. 
            Existing assignments are pre-selected, and you can update or set "No Deductions" as needed. 
            Changes are saved instantly and validated to ensure accurate leave management.
        </div>
        <div class="row">
            @foreach($leaves as $leave)
                @php
                    $assigned = $assignLeave->firstWhere('leave_id', $leave->id);
                    $selectedId = $assigned ? $assigned->deduct_credit_id : null;
                @endphp

                <div class="col-12 col-md-4">
                    <div class="card shadow mb-3">
                        <div class="card-body py-3">
                            <h5 class="card-title text-uppercase fw-bold mt-2 mb-2 text-clamp-1">{{ $leave->name }}</h5>
                            <label for="credit_deduct_{{ $leave->id }}" class="mt-3 mb-2">
                                Where to Deduct the Credits <span class="text-danger">*</span>
                            </label>
                            <select name="credit_deduct[{{ $leave->id }}]" id="credit_deduct_{{ $leave->id }}" class="form-select">
                                <option value="" disabled {{ is_null($selectedId) ? 'selected' : '' }}> - CHOOSE -</option>
                                <option value="none" {{ $selectedId === null ? 'selected' : '' }}> No Credit Deduction </option>
                                @foreach($leaves as $option)
                                    <option value="{{ $option->id }}" {{ $selectedId == $option->id ? 'selected' : '' }}>
                                        {{ $option->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="error-field text-danger mt-1"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary px-5 py-2 text-uppercase fw-medium">Save</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
$(function() {

    const url = $('#form').attr('action');
    post(url);

    // $('#form').submit(function(e) {
    //     e.preventDefault();

    //     const submitButton = $(this).find('button[type="submit"]');
    //     submitButton.prop('disabled', true);

    //     $('.is-invalid').removeClass('is-invalid');
    //     $('.error-field').text('');

    //     const formData = {
    //         _token: $('input[name="_token"]').val(),
    //         leaves: {}
    //     };

    //     $('select[id^="credit_deduct_"]').each(function() {
    //         const leaveId = $(this).attr('id').replace('credit_deduct_', '');
    //         formData.leaves[leaveId] = $(this).val();
    //     });

    //     axios.post(`/admin/maintenance/leaves/assign`, formData, {
    //         headers: {
    //             'Accept': 'application/json',
    //             'Content-Type': 'application/json'
    //         }
    //     })
    //     .then(response => {
    //         Swal.fire({
    //             title: "Success!",
    //             text: "Leaves have been assigned successfully.",
    //             icon: "success"
    //         }).then(() => window.location.reload());
    //     })
    //     .catch(error => {
    //         if (error.response && error.response.status === 422) {
    //             $.each(error.response.data.errors, function(field, messages) {
    //                 if (field.startsWith('leaves.')) {
    //                     const leaveId = field.split('.')[1];
    //                     const select = $(`#credit_deduct_${leaveId}`);
    //                     select.addClass('is-invalid');
    //                     select.siblings('.error-field').text(messages[0]);
    //                 }
    //             });
    //         } else {
    //             Swal.fire({
    //                 title: "Oops!",
    //                 text: error.response?.data?.message || 'Something went wrong!',
    //                 icon: "error"
    //             });
    //         }
    //     })
    //     .finally(() => submitButton.prop('disabled', false));
    // });

    // )};

});
</script>
@endsection

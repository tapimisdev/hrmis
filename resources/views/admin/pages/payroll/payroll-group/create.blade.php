@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
 <div class="container-fluid">

    <x-header title="Payroll Group" subtitle="Manage payroll groups in this module">
        <x-button-link
            :href="route('payroll.group.index')"
            icon="fa-solid fa-arrow-left me-2"
            text="Back"
            variant="danger"
            class="js-back-with-fallback"
        />
    </x-header>

    <form id="form" action="{{ route('payroll.group.store') }}" method="post">
        @csrf

        <div class="card shadow p-3" style="max-width: 820px;">
            <div class="card-header bg-transparent">
                <h4 class="m-0 mb-1 pt-3 text-uppercase fw-medium">
                    Create Payroll Group
                </h4>
            </div>

            <div class="card-body">
                <div class="row my-3">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="name">Payroll Group Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="e.g., COS Payroll Group">
                        <div class="error-field"></div>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="employment_type_id">Employment Type <span class="text-danger">*</span></label>
                        <select id="employment_type_id" name="employment_type_id" class="form-select">
                            <option value=""> - CHOOSE EMPLOYMENT TYPE - </option>
                            @foreach ($employmentTypes ?? [] as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <div class="error-field"></div>
                    </div>
                </div>

                <div class="row my-3">
                    <div class="col-12 mb-3">
                        <label class="mb-2" for="remarks">Remarks</label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="4" placeholder="Optional notes..."></textarea>
                        <div class="error-field"></div>
                    </div>
                </div>
            </div>

            <div class="card-footer border-top bg-transparent border-0 pt-4 d-flex justify-content-end">
                <button type="submit" id="submit-button"
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
        const url = $('#form').attr('action');
        post(url);
    });
</script>
@endsection

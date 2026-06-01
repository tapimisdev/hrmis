@extends('employee.layout.app')

@section('content')
<div class="container-fluid min-vh-100">
    <x-employee-navbar>
        <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
    </x-employee-navbar>

    <x-header-employee title="Special Order / Apply" subtitle="Create special order application in this module">
        <a href="javascript:history.back()" class="btn btn-danger py-3 px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header-employee>

    <div class="card rounded-3 mb-5">
        <div class="card-header fw-bold d-flex align-items-center text-uppercase py-3">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div>
                    <i class="fa-solid fa-car-on me-2"></i> Special Order Application Form
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('special-order.store') }}" id="form" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4 mb-3">
                        <label for="date" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="date" class="form-control">
                        <div class="error-field"></div>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="so_no" class="form-label fw-semibold">Special Order No. <span class="text-danger">*</span></label>
                        <input type="text" name="so_no" id="so_no" class="form-control">
                        <div class="error-field"></div>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-4 mb-3">
                        <label for="shift" class="form-label fw-semibold">Shift <span class="text-danger">*</span></label>
                        <select name="shift" id="shift" class="form-select">
                            <option value="">- CHOOSE -</option>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                            <option value="wholeday">Whole Day</option>
                        </select>
                        <div class="error-field"></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="within_metro_manila" class="form-label fw-semibold">Within Metro Manila? <span class="text-danger">*</span></label>
                        <select name="within_metro_manila" id="within_metro_manila" class="form-select">
                            <option value="">- CHOOSE -</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                        <div class="error-field"></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="is_hazardous" class="form-label fw-semibold">Is Hazardous? <span class="text-danger">*</span></label>
                        <select name="is_hazardous" id="is_hazardous" class="form-select">
                            <option value="">- CHOOSE -</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                        <div class="error-field"></div>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-12 mb-3">
                        <label for="remarks" class="form-label fw-semibold">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="4" placeholder="Write something..."></textarea>
                        <div class="error-field"></div>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="alert alert-info text-muted fw-bold text-uppercase mb-4" style="font-size: 10px">
                            Note:<br><br>
                            Accepts only the following files (jpg,jpeg,png,doc,docx,pdf)<br>
                            Maximum of 5 files
                        </div>
                        <label for="attachments" class="form-label fw-semibold">Attachments <span class="text-danger">*</span></label>
                        <input type="file" name="attachments[]" id="attachments" class="form-control" multiple>
                        <div class="error-field"></div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 mt-4 mb-4">
                <div class="d-flex align-items-center justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="fa-solid fa-paper-plane me-2"></i> Submit
                    </button>
                </div>
            </div>
        </form>
    </div>
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

@extends('employee.layout.app')

@section('content')
<div class="container-fluid min-vh-100">
    
    <x-employee-navbar>
        <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
    </x-employee-navbar>

    <x-header-employee title=" Overtime / Apply" subtitle="Create overtime application in this module">
        <a href="javascript:history.back()" class="btn btn-danger py-3 px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header-employee>

    <div class="card rounded-3">
        <div class="card-header fw-bold d-flex align-items-center">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div>
                    <i class="fa-solid fa-clock me-2"></i> Overtime Application Form
                </div>
                <div>
                    <div>
                        <a href="#" class="btn btn-dark">
                            <i class="fa-solid fa-download me-1"></i>
                                Form Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('overtime.store') }}" id="form" enctype="multipart/form-data">
            @csrf
            <div class="card-body">

                <div class="row g-3">
                    {{-- Date --}}
                    <div class="col-md-12 mb-3">
                        <label for="date" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="date" class="form-control">
                        <div class="error-field"></div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    {{-- Start Time --}}
                    <div class="col-md-6 mb-3">
                        <label for="start_time" class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" id="start_time" class="form-control">
                        <div class="error-field"></div>
                    </div>

                    {{-- End Time --}}
                    <div class="col-md-6 mb-3">
                        <label for="end_time" class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                        <input type="time" name="end_time" id="end_time" class="form-control">
                        <div class="error-field"></div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    {{-- Reason --}}
                    <div class="col-12 mb-3">
                        <label for="reason" class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" class="form-control" rows="4" placeholder="State your reason..."></textarea>
                        <div class="error-field"></div>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="alert alert-info text-muted fw-bold text-uppercase mb-4" style="font-size: 10px">Note: 
                            <br>
                            <br>
                            Accepts only the following files (jpg,jpeg,png,doc,docx,pdf)
                            <br>
                            Maximum of 5 files
                        </div>
                        <label for="attachments" class="form-label fw-semibold">Attachments <span class="text-danger">*</span></label>
                        <input type="file" name="attachments[]" id="attachments" class="form-control" multiple>
                        <div class="error-field"></div>
                    </div>
                </div>
                <hr class="mt-5 mb-3">
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
        const data = @json($applications);
        const url = $('#form').attr('action');
        post(url);
    });
</script>
@endsection
@extends('employee.layout.app')

@section('content')
<div class="container-fluid pt-3 pt-4">

    <header-vue title="DOST TAPI"></header-vue>

    <x-header-employee title=" Overtime / Apply" subtitle="Create overtime application in this module">
        <a href="javascript:history.back()" class="btn btn-danger py-3 px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header-employee>

    <div class="card rounded-3">
        <div class="card-header fw-bold d-flex align-items-center">
            <i class="fa-solid fa-clock me-2"></i> Overtime Application Form
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
                    <div class="col-md-4 mb-3">
                        <label for="start_time" class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" id="start_time" class="form-control">
                        <div class="error-field"></div>
                    </div>

                    {{-- End Time --}}
                    <div class="col-md-4 mb-3">
                        <label for="end_time" class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                        <input type="time" name="end_time" id="end_time" class="form-control">
                        <div class="error-field"></div>
                    </div>

                     {{-- Total Hours --}}
                    <div class="col-md-4 mb-3">
                        <label for="total_hours" class="form-label fw-semibold">Total Hours</label>
                        <input type="number" step="0.01" min="0" name="total_hours" id="total_hours" class="form-control" placeholder="Optional (auto-calculated)">
                        <div class="error-field"></div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    {{-- Reason --}}
                    <div class="col-12 mb-3">
                        <label for="reason" class="form-label fw-semibold">Reason</label>
                        <textarea name="reason" id="reason" class="form-control" rows="4" placeholder="State your reason..."></textarea>
                        <div class="error-field"></div>
                    </div>
                </div>
                <hr class="mt-5 mb-3">
                <div class="row g-3 mt-2">
                    <div class="col-12 col-md-12">
                        <div for="approvers" class="form-label fw-semibold mb-3">Choose Your Approvers</div>
                        @forelse($approvers as $level => $users)
                            <div class="mb-3">
                                <label for="approvers.{{ $level }}" class="mb-2">{{ ordinal($level) . ' Approver' }}</label>
                                <select name="approvers[{{$level}}][]" id="approvers.{{ $level }}" class="form-select select2 mb-3" multiple>
                                    @foreach($users as $user)
                                        <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                                    @endforeach
                                </select>
                                <div id="approvers">
                                    <div class="error-field"></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-uppercase fw-bold text-muted fst-italic">No approvers found. Please contact administrators.</div>
                        @endforelse
                        <div class="mb-3">
                            <div id="approvers">
                                <div class="error-field"></div>
                            </div>
                        </div>
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
        const data = @json($applications);
        const url = $('#form').attr('action');
        post(url);
    });
</script>
@endsection
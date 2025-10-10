@extends('employee.layout.app')

@section('content')
<div class="container pt-4">

    <x-header title="Apply for Leave" subtitle="Create leave application in this module">
        <a href="javascript:history.back()" class="btn btn-outline-danger py-3 px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header>

    <div class="card rounded-3 mb-5">
        <div class="card-header fw-bold d-flex align-items-center text-uppercase py-3">
            <i class="fa-solid fa-file-pen me-2"></i> Application Form
        </div>
        <form method="POST" action="{{ route('leaves.store') }}" id="form" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-12 mb-3">
                        <label for="leave_id" class="form-label fw-semibold">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_id" id="leave_id" class="form-select">
                            <option value=""> - CHOOSE TYPE - </option>
                            @foreach ($leaves as $leave)
                                <option value="{{ $leave->id }}">{{ $leave->name }}</option>
                            @endforeach
                        </select>
                        <div class="error-field"></div>
                    </div>
                    <div class="col-12 col-md-12 mb-3">
                        <label for="calendar" class="form-label fw-semibold">Choose Dates <span class="text-danger">*</span></label>
                        <div id="calendar" class="full-calendar"></div>
                        <div class="error-field"></div>
                    </div>
                </div>
                <div class="row g-3 mt-2 mb-3">
                    <div class="col-12">
                        <label for="reason" class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" class="form-control" rows="4" placeholder="State your reason..."></textarea>
                        <div class="error-field"></div>
                    </div>
                </div>
                <div class="row g-3 mt-2 mb-3">
                    <div class="col-12">
                        <label for="attachments" class="form-label fw-semibold">Attachments (optional)</label>
                        <input type="file" name="attachments[]" id="attachments" class="form-control" multiple>
                        <div class="error-field"></div>
                    </div>
                </div>
                <hr class="mt-5 mb-4">
                <div class="row g-3 mt-2">
                    <div class="col-12 col-md-12">
                        <div for="approvers" class="form-label fw-semibold text-uppercase mb-3">Choose Your Approvers</div>
                        @foreach($approvers as $level => $users)
                            <div class="mb-3">
                                <label for="approvers.{{ $level }}" class="mb-2">{{ ordinal($level) . ' Approver' }}</label>
                                <select name="approvers[{{$level}}][]" id="approvers.{{ $level }}" class="form-select select2 mb-3" multiple>
                                    @foreach($users as $user)
                                        <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                                    @endforeach
                                </select>
                                <div class="error-field"></div>
                            </div>
                        @endforeach
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
        const url = $('#form').attr('action');
        post(url);

        initCalendar();

        const unavailable = [
            { title: 'Meeting', date: '2025-10-15', description: 'Team meeting in the conference room.' },
            { title: 'Busy', date: '2025-11-03', description: 'Out of office for a personal day.' }
        ];

        const events = generateEventsWithAvailability(unavailable);
        setEvents(events);

    });
</script>
@endsection

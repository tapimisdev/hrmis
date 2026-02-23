@extends('employee.layout.app')

@section('content')
<div class="container-fluid min-vh-100">
    <x-employee-navbar>
        <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
    </x-employee-navbar>

    <x-header-employee title="Pass Slip / Apply" subtitle="Create a pass slip application in this module">
        <a href="javascript:history.back()" class="btn btn-danger py-3 px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header-employee>

    <div class="card rounded-3">
        <div class="card-header fw-bold d-flex align-items-center text-uppercase py-3">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div>
                    <i class="fa-solid fa-file-pen me-2"></i> Official Business / Pass Slip Application Form
                </div>
                <div>
                    <a href="#" class="btn btn-dark">
                        <i class="fa-solid fa-download me-1"></i>
                            Form Download
                    </a>
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('obs.store') }}" id="form" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-12 mb-3">
                        <label for="calendar" class="form-label fw-semibold">Choose Dates <span class="text-danger">*</span></label>
                        <div id="calendar" class="full-calendar"></div>
                        <input type="hidden" name="selectedDates" id="selectedDates">
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
                        <div class="alert alert-info text-muted fw-bold text-uppercase mt-3 mb-4" style="font-size: 10px">Note: 
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
                <hr class="mt-5 mb-4">
                {{--
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
                    </div>
                </div>

                --}}
            </div>
            <div class="card-footer bg-transparent border-0 mt-4 mb-4">
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="fa-solid fa-paper-plane me-2"></i> Submit
                    </button>
                </div>
            </div>
            <div id="slot-modal">
            
            </div>
        </form>
    </div>
</div>
<style>
    @media (max-width: 768px) {
        #calendar {
            padding: 5px;
            font-size: 0.85rem;
        }

        .fc-toolbar.fc-header-toolbar {
            flex-direction: column; 
        }

        .fc-toolbar-chunk {
            margin-bottom: 5px;
        }
    }
</style>
@endsection

@section('scripts')
<script>
    $(function() {
        const data = @json($applications);
        const url = $('#form').attr('action');
        post(url);

        initCalendar();
        const events = generateEventsWithAvailability(data);
        setEvents(events, data);

    });
</script>
@endsection
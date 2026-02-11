@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">

    <x-header title="Overtime Application" subtitle="View overtime application details">
        <x-button-link 
            href="{{route('services.overtime.index')}}"
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
        />
    </x-header>
    <div class="alert alert-primary mb-4 text-uppercase fw-bold text-center">
        This application can be approved directly without requiring approval from level-based approvers because you have admin or superadmin privileges.
    </div>
    <form id="form" action="{{ route('services.overtime.save', ['application' => $data->id]) }}" method="POST">
        @csrf
        @method('POST')
        <input type="hidden" name="action" id="action" value="">
        <div class="card rounded-3 mb-5">
            <div class="card-header fw-bold d-flex align-items-center">
                <i class="fa-solid fa-file-pen me-2"></i> Application Details
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Application No:</th>
                        <td>{{ $data->application_no }}</td>
                    </tr>

                    <tr>
                        <th>Employee No:</th>
                        <td>{{ $data->employee_no }}</td>
                    </tr>

                    <tr>
                        <th>Employee Name:</th>
                        <td>{{ $data->firstname }} {{ $data->lastname }}</td>
                    </tr>

                    <tr>
                        <th>Date:</th>
                        <td>
                            {{ \Carbon\Carbon::parse($data->date)->format('M d, Y (l)') }}
                        </td>
                    </tr>

                    <tr>
                        <th>Time:</th>
                        <td>
                            {{ \Carbon\Carbon::parse($data->start_time)->format('h:i A') }}
                            –
                            {{ \Carbon\Carbon::parse($data->end_time)->format('h:i A') }}
                        </td>
                    </tr>

                    <tr>
                        <th>Total Hours:</th>
                        <td>
                            {{ number_format($data->total_hours ?? 0, 2) }} hour(s)
                        </td>
                    </tr>

                    <tr>
                        <th>Reason:</th>
                        <td>{{ $data->reason ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Attachments:</th>
                        <td id="attachments">
                            <ul class="list-unstyled mb-0">
                                @if (!empty($data->attachments) && count($data->attachments) > 0)
                                    @foreach ($data->attachments as $attachment)
                                        <li>
                                            <a download href="{{ '/storage/' . $attachment->file_path }}">
                                                {{ $attachment->file_name }}
                                            </a>
                                        </li>
                                    @endforeach
                                @else
                                    <li>No attachments available.</li>
                                @endif
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            @php
                                $statusClass = match($data->status) {
                                    'pending' => 'bg-warning',
                                    'approved' => 'bg-success',
                                    'rejected' => 'bg-danger',
                                    'cancelled' => 'bg-secondary',
                                    default => 'bg-secondary',
                                };
                            @endphp

                            <span class="badge {{ $statusClass }}">
                                {{ ucfirst($data->status) }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <th>Applied At:</th>
                        <td>
                            {{ \Carbon\Carbon::parse($data->created_at)->format('M d, Y h:i A') }}
                        </td>
                    </tr>
                </table>

             
                <!-- <div class="mt-4 mb-3">
                    <small class="text-uppercase fw-bold text-muted">Your Approvers</small>

                    @if (!empty($data->approvals) && count($data->approvals) > 0)
                        <div class="accordion mt-2" id="approversAccordion">
                            @foreach ($data->approvals as $level => $approvers)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingLevel{{ $level }}">
                                        <button class="accordion-button text-uppercase fw-bold {{ $loop->first ? '' : 'collapsed' }}" 
                                                style="font-size: 10px" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#collapseLevel{{ $level }}" 
                                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}" 
                                                aria-controls="collapseLevel{{ $level }}">
                                            Level {{ $level }} Approvers
                                        </button>
                                    </h2>
                                    <div id="collapseLevel{{ $level }}" 
                                        class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" 
                                        aria-labelledby="headingLevel{{ $level }}" 
                                        data-bs-parent="#approversAccordion">
                                        <div class="accordion-body p-2">
                                            <ul class="mb-0">
                                                @foreach ($approvers as $approver)
                                                    <li>
                                                        {{ $approver->firstname }} {{ $approver->lastname }} ({{ $approver->employee_no }})
                                                        @if ($approver->status == 'approved')
                                                            <i class="fa-solid fa-check text-primary"></i>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="fst-italic text-muted text-uppercase mt-3">No approvers assigned.</div>
                    @endif
                </div> -->
            </div>
        
            {{-- Action Buttons --}}
            @if($data->status == 'pending')
                <div class="card-footer bg-light d-flex justify-content-end gap-3 py-3 bg-transparent py-4">
                    <button type="submit" name="action" value="rejected" id="btn-rejected" class="fw-bold px-4 py-3 text-uppercase btn btn-danger px-4">
                        <i class="fa-solid fa-xmark me-2"></i> Decline
                    </button>
                    <button type="submit" name="action" value="approve" id="btn-approve" class="fw-bold px-4 py-3 text-uppercase btn btn-primary px-4">
                        <i class="fa-solid fa-check me-2"></i> Approve
                    </button>
                </div>
            @endif
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
     
        const url = $('#form').attr('action');
        $('#btn-rejected').on('click', function() {
            $('#action').val('rejected');
            post(url, true);
        });

        $('#btn-approve').on('click', function() {
            $('#action').val('approve');
            post(url);
        });
            
    });
</script>
@endsection

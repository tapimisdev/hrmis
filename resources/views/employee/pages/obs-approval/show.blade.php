@extends('employee.layout.app')

@section('content')
<div class="container-fluid">

    <x-header title="Pass Slip Application" subtitle="View pass slip application details">
        <x-button-link 
            href="{{route('approval-obs.index')}}"
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
        />
    </x-header>
     <form id="form" action="{{ route('approval-obs.save', ['level' => $data->level, 'id' => $data->id]) }}" method="POST">
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
                        <th width="30%">File No:</th>
                        <td id="file-no">{{ $data->application_no }}</td>
                    </tr>
                    <tr>
                        <th>Employee No:</th>
                        <td id="employee-no">{{ $data->employee_no }}</td>
                    </tr>
                    <tr>
                        <th>Name:</th>
                        <td id="employee-name">{{ $data->firstname . ' ' . $data->lastname }}</td>
                    </tr>
                    <tr>
                        <th>Date From:</th>
                        <td>{{ \Carbon\Carbon::parse($data->date_from)->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <th>Date To:</th>
                        <td>{{ \Carbon\Carbon::parse($data->date_to)->format('M d, Y') }}</td>
                    </tr>
                     <tr>
                        <th>Time In:</th>
                        <td>{{ $data->time_in ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Time Out:</th>
                        <td>{{ $data->time_out ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Destination:</th>
                        <td>{{ $data->destination }}</td>
                    </tr>
                    <tr>
                        <th>Purpose:</th>
                        <td>{{ $data->purpose }}</td>
                    </tr>
                    <tr>
                        <th>Mode of Transport:</th>
                        <td>{{ $data->mode_of_transport ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Estimated Expense:</th>
                        <td>{{ number_format($data->estimated_expense, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Charge To:</th>
                        <td>{{ $data->charge_to ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Note:</th>
                        <td>{{ $data->remarks ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Applied At:</th>
                        <td>{{ \Carbon\Carbon::parse($data->created_at)->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <th>Attachments:</th>
                        <td id="attachments">
                            <ul class="list-unstyled mb-0">
                                @forelse ($data->attachments as $attachment)
                                    <li>
                                        <a download href="{{ '/storage/' . $attachment->file_path }}">
                                            {{ $attachment->file_name ?? 'Unnamed file' }}
                                        </a>
                                    </li>
                                @empty
                                    <li>No attachments available.</li>
                                @endforelse
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
                            <span id="status" class="badge {{ $statusClass }}">
                                {{ ucfirst($data->status) }}
                            </span>
                        </td>
                    </tr>
                    @if($data->status == 'rejected')
                        <tr>
                        <th>Remarks:</th>
                        <td>
                            {{$data->approval_remarks}}
                        </td>
                    </tr>
                    @endif
                </table>
            </div>

        
            {{-- Action Buttons --}}
            @if($data->status == 'pending')
                <div class="card-footer bg-light d-flex justify-content-end gap-3 py-3 bg-transparent">
                    <button type="submit" name="action" value="rejected" id="btn-rejected" class="px-5 py-3 text-uppercase btn btn-danger px-4">
                        <i class="fa-solid fa-xmark me-2"></i> Decline
                    </button>
                    <button type="submit" name="action" value="approve" id="btn-approve" class="px-5 py-3 text-uppercase btn btn-primary px-4">
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

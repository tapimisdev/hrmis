@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">

    <x-header title="Special Order Application" subtitle="View special order application details">
        <x-button-link
            href="{{ route('services.special_order.index') }}"
            icon="fa-solid fa-arrow-left me-2"
            text="Back"
            variant="danger"
        />
    </x-header>

    <div class="alert alert-primary mb-4 text-uppercase fw-bold text-center">
        This application can be approved directly without requiring approval from level-based approvers because you have admin or superadmin privileges.
    </div>

    <form id="form" action="{{ route('services.special_order.save', ['application' => $data->id]) }}" method="POST">
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
                        <th width="30%">SO No.:</th>
                        <td>{{ $data->so_no }}</td>
                    </tr>
                    <tr>
                        <th>Employee No:</th>
                        <td>{{ $data->employee_no }}</td>
                    </tr>
                    <tr>
                        <th>Employee Name:</th>
                        <td>{{ trim($data->firstname . ' ' . $data->lastname) ?: $data->name }}</td>
                    </tr>
                    <tr>
                        <th>Within Metro Manila:</th>
                        <td>{{ $data->within_metro_manila ? 'Yes' : 'No' }}</td>
                    </tr>
                    <tr>
                        <th>Hazardous:</th>
                        <td>{{ $data->isHazardous ? 'Yes' : 'No' }}</td>
                    </tr>
                    <tr>
                        <th>Dates:</th>
                        <td>
                            <ul>
                                @forelse($data->dates as $item)
                                    <li>{{ \Carbon\Carbon::parse($item['date'])->format('M d, Y - (l)') }} - [ {{ $item['shift'] }} ]</li>
                                @empty
                                    <li>No dates available.</li>
                                @endforelse
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <th>Remarks:</th>
                        <td>{{ $data->remarks ?? '-' }}</td>
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
                        <td>{{ \Carbon\Carbon::parse($data->created_at)->format('M d, Y h:i A') }}</td>
                    </tr>
                    <tr>
                        <th>Attachments:</th>
                        <td>
                            <ul class="list-unstyled mb-0">
                                @forelse ($data->attachments as $attachment)
                                    <li>
                                        <a download href="{{ asset('storage/' . $attachment->file_path) }}">
                                            {{ $attachment->file_name }}
                                        </a>
                                    </li>
                                @empty
                                    <li>No attachments available.</li>
                                @endforelse
                            </ul>
                        </td>
                    </tr>
                </table>
            </div>

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

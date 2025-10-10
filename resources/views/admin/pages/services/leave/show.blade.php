@extends('admin.layouts.app')

@section('content')
<div class="container pt-4">

    <x-header title="Leave Application" subtitle="View leave application details">
        <x-button-link 
            :href="javascript:history.back()" 
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
        />
    </x-header>

    <div class="card rounded-3 mb-5">
        <div class="card-header fw-bold d-flex align-items-center">
            <i class="fa-solid fa-file-pen me-2"></i> Application Details
        </div>

        <div class="card-body">

            <div class="row g-3">
                {{-- Leave Type --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Leave Type</label>
                    <input type="text" class="form-control" 
                           value="{{ $data->leave_name ?? '' }}" readonly>
                </div>

                {{-- Number of Days --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Number of Days</label>
                    <input type="text" class="form-control" 
                           value="{{ $data->days ?? '' }}" readonly>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Date</label>
                    <input type="text" class="form-control" 
                           value="{{ \Carbon\Carbon::parse($data->start_date)->format('M d, Y') . ' - ' . \Carbon\Carbon::parse($data->end_date)->format('M d, Y') ?? '' }}" readonly>
                </div>
            </div>

            <div class="row g-3 mt-2">
                {{-- Reason --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">Reason</label>
                    <textarea class="form-control" rows="4" readonly>{{ $data->reason ?? '' }}</textarea>
                </div>
            </div>

            <div class="row g-3 mt-2">
                {{-- Attachments --}}
                <div class="col-12">
                    <label class="form-label fw-semibold">Attachments</label>
                    @if(isset($data->attachments) && count($data->attachments))
                        <ul class="mb-0">
                            @foreach ($data->attachments as $file)
                                <li>
                                    <a href="{{ asset('storage/'.$file) }}" target="_blank">
                                        {{ basename($file) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <input type="text" class="form-control" value="No attachments" readonly>
                    @endif
                </div>
            </div>

        </div>

        {{-- Action Buttons --}}
        <div class="card-footer bg-light d-flex justify-content-end gap-3 py-3 bg-transparent">
            <form action="{{ route('services.leaves.index', $data->id) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="action" id="action" value="rejected">
                <button type="submit" class="px-5 py-3 text-uppercase btn btn-danger px-4">
                    <i class="fa-solid fa-xmark me-2"></i> Decline
                </button>
            </form>
            <form action="{{ route('services.leaves.index', $data->id) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="action" id="action" value="approved">
                <button type="submit" class="px-5 py-3 text-uppercase btn btn-primary px-4">
                    <i class="fa-solid fa-check me-2"></i> Approve
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
     
        const url = $('#form').attr('action');
        put(url);
        
    });
</script>
@endsection

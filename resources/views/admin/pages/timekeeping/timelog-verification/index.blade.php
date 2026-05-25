@extends('admin.layouts.app')

@section('styles')
<style>
    .timelog-verification-table {
        min-width: 1600px;
    }

    .timelog-verification-badge {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.02em;
    }

    .timelog-verification-badge-fn {
        border: 1px solid transparent;
    }

    .timelog-verification-badge-fn-0 {
        background: rgba(13, 110, 253, 0.2);
        color: #dbeafe;
        border-color: rgba(13, 110, 253, 0.35);
    }

    .timelog-verification-badge-fn-1 {
        background: rgba(25, 135, 84, 0.22);
        color: #dcfce7;
        border-color: rgba(25, 135, 84, 0.4);
    }

    .timelog-verification-badge-fn-2 {
        background: rgba(255, 193, 7, 0.28);
        color: #fff3bf;
        border-color: rgba(255, 193, 7, 0.45);
    }

    .timelog-verification-badge-fn-3 {
        background: rgba(13, 202, 240, 0.24);
        color: #cffafe;
        border-color: rgba(13, 202, 240, 0.42);
    }

    .timelog-verification-badge-fn-4 {
        background: rgba(108, 117, 125, 0.32);
        color: #f8f9fa;
        border-color: rgba(173, 181, 189, 0.4);
    }

    .timelog-verification-badge-fn-5 {
        background: rgba(220, 53, 69, 0.22);
        color: #ffe3e6;
        border-color: rgba(220, 53, 69, 0.4);
    }

    .timelog-verification-badge-fn-default {
        background: rgba(108, 117, 125, 0.22);
        color: #e9ecef;
        border-color: rgba(108, 117, 125, 0.35);
    }

    .timelog-verification-empty {
        border: 1px dashed rgba(255, 255, 255, 0.12);
        border-radius: 0.75rem;
        background: rgba(255, 255, 255, 0.03);
        color: #f8f9fa;
    }

    .timelog-verification-empty .text-muted {
        color: rgba(248, 249, 250, 0.7) !important;
    }

    .timelog-verification-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .timelog-verification-badge-muted {
        background: rgba(108, 117, 125, 0.12);
        color: #6c757d;
        border: 1px solid rgba(108, 117, 125, 0.2);
    }

    .timelog-verification-filter-card {
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.02);
    }

    .timelog-verification-filter-group {
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.85rem;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.015);
        height: 100%;
    }
</style>
@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Timelog Verification" subtitle="View and verify raw timelog records only" />

        <div class="card border-0 shadow-sm timelog-verification-filter-card">
            <div class="card-body">
                <form method="GET" action="{{ route('timekeeping.timelog-verification.index') }}">
                    <div class="row g-3">
                        <div class="col-12 col-xl-5">
                            <div class="timelog-verification-filter-group">
                                <label for="search" class="form-label">Search Employee or Device</label>
                                <input
                                    type="text"
                                    name="search"
                                    id="search"
                                    class="form-control"
                                    value="{{ $filters['search'] }}"
                                    placeholder="Employee no, user ID, biometric SN, or employee name"
                                >
                                <div class="form-text">Search by employee number, user ID, employee name, or biometric SN.</div>
                            </div>
                        </div>
                        <div class="col-12 col-xl-5">
                            <div class="timelog-verification-filter-group">
                                <div class="form-label mb-2">Date Range</div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label for="from_date" class="form-label small text-muted mb-1">From Date</label>
                                        <input
                                            type="date"
                                            name="from_date"
                                            id="from_date"
                                            class="form-control"
                                            value="{{ $filters['from_date'] }}"
                                        >
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="to_date" class="form-label small text-muted mb-1">To Date</label>
                                        <input
                                            type="date"
                                            name="to_date"
                                            id="to_date"
                                            class="form-control"
                                            value="{{ $filters['to_date'] }}"
                                        >
                                    </div>
                                </div>
                                <div class="form-text">Leave blank to show all dates, or use one side only for open-ended filtering.</div>
                            </div>
                        </div>
                        <div class="col-12 col-xl-2">
                            <div class="timelog-verification-filter-group d-flex flex-column justify-content-end gap-2">
                                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                <a href="{{ route('timekeeping.timelog-verification.index') }}" class="btn btn-secondary w-100">
                                    Clear Filters
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                    <div>
                        <div class="fw-semibold mb-1">FN Legends</div>
                        <div class="timelog-verification-legend">
                            @foreach ($fnLabels as $value => $label)
                                @php
                                    $fnBadgeClass = 'timelog-verification-badge-fn-' . $value;
                                @endphp
                                <span class="badge timelog-verification-badge timelog-verification-badge-fn {{ $fnBadgeClass }}">{{ $value }} = {{ $label }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if ($timelogs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle timelog-verification-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Date Time</th>
                                    <th>Shift</th>
                                    <th>Work Schedule</th>
                                    <th>FN</th>
                                    <th>Biometric SN</th>
                                    <th>Active Status</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($timelogs as $timelog)
                                    <tr>
                                        <td>
                                            @php
                                                $resolvedEmployeeNo = $timelog->resolved_employee_no;
                                                $profileUrl = $timelog->profile
                                                    ? Storage::url('public/users/' . $resolvedEmployeeNo . '/profile-image/' . $timelog->profile)
                                                    : 'https://ui-avatars.com/api/?name=' . urlencode($timelog->employee_fullname) . '&background=6c757d&color=fff&font-size=0.4&bold=true';
                                            @endphp
                                            <div class="d-flex align-items-center gap-3">
                                                <div style="width: 48px; height: 48px; border: 1px solid rgba(255,255,255,0.08); border-radius: 999px; overflow: hidden; flex-shrink: 0; background: rgba(108, 117, 125, 0.12);">
                                                    <img
                                                        src="{{ $profileUrl }}"
                                                        alt="Profile picture of {{ $timelog->employee_fullname }}"
                                                        style="width: 100%; height: 100%; object-fit: cover;"
                                                    >
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ $timelog->employee_fullname }}</div>
                                                    <div class="small text-muted">User ID: {{ $timelog->user_id }}</div>
                                                    <div class="small text-muted">Employee No: {{ $resolvedEmployeeNo ?? 'N/A' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($timelog->date_time)
                                                <div class="fw-semibold fs-6">{{ $timelog->date_time->format('h:i:s A') }}</div>
                                                <div class="small text-muted">{{ $timelog->date_time->format('M d, Y') }}</div>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $timelog->shift?->name ?? 'N/A' }}</td>
                                        <td>{{ $timelog->workSchedule?->name ?? 'N/A' }}</td>
                                        <td>
                                            @php
                                                $fnBadgeClass = 'timelog-verification-badge-fn-' . $timelog->fn;
                                            @endphp
                                            <span class="badge timelog-verification-badge timelog-verification-badge-fn {{ $fnBadgeClass }}">
                                                {{ $timelog->fn }} - {{ $timelog->fn_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge timelog-verification-badge timelog-verification-badge-muted">
                                                {{ $timelog->biometric_sn }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $timelog->is_active ? 'bg-success' : 'bg-secondary' }} timelog-verification-badge">
                                                {{ $timelog->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($timelog->created_at)
                                                <div class="fw-semibold">{{ $timelog->created_at->format('M d, Y') }}</div>
                                                <div class="small text-muted">{{ $timelog->created_at->format('h:i:s A') }}</div>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if ($timelog->updated_at)
                                                <div class="fw-semibold">{{ $timelog->updated_at->format('M d, Y') }}</div>
                                                <div class="small text-muted">{{ $timelog->updated_at->format('h:i:s A') }}</div>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4">
                        <div class="text-muted small">
                            Showing {{ $timelogs->firstItem() }} to {{ $timelogs->lastItem() }} of {{ $timelogs->total() }} raw timelog records
                        </div>
                        <div>
                            {{ $timelogs->links() }}
                        </div>
                    </div>
                @else
                    <div class="timelog-verification-empty text-center py-5 px-4">
                        <div class="mb-2 fw-semibold">No timelog records found.</div>
                        <div class="text-muted">Try adjusting the search keyword or date filters.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

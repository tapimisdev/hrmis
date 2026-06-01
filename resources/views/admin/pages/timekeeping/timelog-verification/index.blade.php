@extends('admin.layouts.app')

@section('styles')
<style>
    .timelog-verification-table {
        min-width: 1600px;
    }

    .timelog-verification-badge {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        line-height: 1.15;
        opacity: 1 !important;
        padding: 0.35rem 0.55rem;
    }

    .timelog-verification-badge-fn {
        border: 1px solid transparent;
        color: #ffffff !important;
    }

    .timelog-verification-badge-fn-0 {
        background: #2563eb !important;
        border-color: #1d4ed8;
    }

    .timelog-verification-badge-fn-1 {
        background: #16a34a !important;
        border-color: #15803d;
    }

    .timelog-verification-badge-fn-2 {
        background: #f59e0b !important;
        border-color: #d97706;
        color: #111827 !important;
    }

    .timelog-verification-badge-fn-3 {
        background: #0891b2 !important;
        border-color: #0e7490;
    }

    .timelog-verification-badge-fn-4 {
        background: #6b7280 !important;
        border-color: #4b5563;
    }

    .timelog-verification-badge-fn-5 {
        background: #dc2626 !important;
        border-color: #b91c1c;
    }

    .timelog-verification-badge-fn-default {
        background: #4b5563 !important;
        border-color: #374151;
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
        gap: 0.45rem;
    }

    .timelog-verification-badge-muted {
        background: #374151 !important;
        color: #ffffff !important;
        border: 1px solid #1f2937;
        opacity: 1 !important;
    }

    [data-bs-theme="dark"] .timelog-verification-badge-muted {
        background: #f3f4f6 !important;
        color: #111827 !important;
        border-color: #d1d5db;
    }

    .timelog-verification-filter-card {
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.02);
    }

    .timelog-verification-filter-group {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 0.85rem;
        padding: 1rem;
        background: #ffffff;
        box-shadow: 0 0.5rem 1.25rem rgba(15, 23, 42, 0.08);
        height: 100%;
    }

    [data-bs-theme="dark"] .timelog-verification-filter-group {
        border-color: rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.015);
        box-shadow: none;
    }

    .timelog-verification-filter-row {
        row-gap: 0.75rem;
    }

    .timelog-verification-filter-group-compact {
        padding-bottom: 0.65rem;
    }

    .timelog-verification-actions .btn {
        min-height: 44px;
        min-width: 150px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .timelog-verification-actions {
        margin-top: 0.5rem;
    }

    @media (max-width: 1199.98px) {
        .timelog-verification-actions {
            flex-direction: row !important;
            flex-wrap: wrap;
        }

        .timelog-verification-actions .btn {
            flex: 0 0 auto;
            width: auto !important;
        }
    }

    @media (max-width: 767.98px) {
        .timelog-verification-filter-card .card-body {
            padding: 1rem;
        }

        .timelog-verification-filter-group {
            padding: 0.85rem;
        }

        .timelog-verification-actions {
            flex-direction: row !important;
        }

        .timelog-verification-actions .btn {
            width: auto !important;
        }

        .timelog-verification-legend {
            gap: 0.35rem;
        }

        .timelog-verification-badge {
            white-space: normal;
            text-align: left;
        }
    }

    @media (max-width: 575.98px) {
        .timelog-verification-filter-card {
            border-radius: 0.65rem;
        }

        .timelog-verification-filter-group {
            border-radius: 0.65rem;
        }

        .timelog-verification-filter-group .form-text {
            font-size: 0.75rem;
        }

        .timelog-verification-actions {
            flex-direction: column !important;
            align-items: stretch !important;
        }

        .timelog-verification-actions .btn {
            width: 100% !important;
        }
    }
</style>
@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Timelog Verification" subtitle="View and verify raw timelog records only" />

        <div class="card border-0 shadow-sm timelog-verification-filter-card">
            <div class="card-body">
                <form method="GET" action="{{ route('timekeeping.timelog-verification.index') }}">
                    <div class="row timelog-verification-filter-row">
                        <div class="col-12">
                            <div class="timelog-verification-filter-group timelog-verification-filter-group-compact">
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
                        <div class="col-12">
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
                        <div class="col-12">
                            <div class="timelog-verification-actions d-flex justify-content-end align-items-center gap-2">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="{{ route('timekeeping.timelog-verification.index') }}" class="btn btn-secondary">Clear Filters</a>
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

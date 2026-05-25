@extends('admin.layouts.app')

@section('styles')
<style>
    .monthly-payroll-summary-filter-card {
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.02);
    }

    .monthly-payroll-summary-filter-group {
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.85rem;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.015);
        height: 100%;
    }

    .monthly-payroll-summary-table {
        min-width: 1440px;
    }

    .monthly-payroll-summary-empty {
        border: 1px dashed rgba(108, 117, 125, 0.35);
        border-radius: 1rem;
        background: rgba(108, 117, 125, 0.05);
    }

    .monthly-payroll-summary-salary-tooltip {
        border-bottom: 1px dashed rgba(13, 110, 253, 0.45);
        cursor: help;
        white-space: nowrap;
    }

    .monthly-payroll-summary-table th,
    .monthly-payroll-summary-table td {
        padding: 0.6rem 0.75rem;
    }

    .monthly-payroll-summary-table td {
        white-space: nowrap;
    }

    .monthly-payroll-summary-table thead th {
        letter-spacing: 0;
        font-size: 0.9rem;
        white-space: normal;
        line-height: 1.2;
        vertical-align: middle;
        min-width: 120px;
    }

    .monthly-payroll-summary-table th:nth-child(1),
    .monthly-payroll-summary-table td:nth-child(1) {
        width: 120px;
        max-width: 120px;
    }

    .monthly-payroll-summary-table th:nth-child(2),
    .monthly-payroll-summary-table td:nth-child(2) {
        width: 120px;
        max-width: 120px;
    }

    .monthly-payroll-summary-table th:nth-child(3),
    .monthly-payroll-summary-table td:nth-child(3) {
        width: 120px;
        max-width: 120px;
    }

    .monthly-payroll-summary-table th:nth-child(4),
    .monthly-payroll-summary-table td:nth-child(4) {
        width: 120px;
        max-width: 120px;
    }

    .monthly-payroll-summary-table th:nth-child(5),
    .monthly-payroll-summary-table td:nth-child(5) {
        width: 120px;
        max-width: 120px;
    }

    .monthly-payroll-summary-table th:nth-child(n + 6),
    .monthly-payroll-summary-table td:nth-child(n + 6) {
        width: 120px;
        max-width: 120px;
    }
</style>
@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Monthly Payroll Summary" subtitle="View total payroll received by employee for the selected month and year" />

        <div class="card border-0 shadow-sm monthly-payroll-summary-filter-card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('payroll.monthly-summary.index') }}">
                    <div class="row g-3">
                        <div class="col-12 col-xl-5">
                            <div class="monthly-payroll-summary-filter-group">
                                <label for="search" class="form-label">Search Employee</label>
                                <input
                                    type="text"
                                    name="search"
                                    id="search"
                                    class="form-control"
                                    value="{{ $filters['search'] }}"
                                    placeholder="Employee no or employee name"
                                >
                                <div class="form-text">Search by employee number or employee name.</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-2">
                            <div class="monthly-payroll-summary-filter-group">
                                <label for="month" class="form-label">Month</label>
                                <select name="month" id="month" class="form-select">
                                    @foreach ($months as $monthValue => $monthLabel)
                                        <option value="{{ $monthValue }}" {{ (int) $filters['month'] === (int) $monthValue ? 'selected' : '' }}>
                                            {{ $monthLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-12 col-md-6 col-xl-2">
                            <div class="monthly-payroll-summary-filter-group">
                                <label for="year" class="form-label">Year</label>
                                <select name="year" id="year" class="form-select">
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}" {{ (int) $filters['year'] === (int) $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-12 col-xl-3">
                            <div class="monthly-payroll-summary-filter-group d-flex flex-column justify-content-end gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fa-solid fa-magnifying-glass me-2"></i>Filter/Search
                                </button>
                                <a href="{{ route('payroll.monthly-summary.index') }}" class="btn btn-secondary w-100">
                                    <i class="fa-solid fa-rotate-left me-2"></i>Reset
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
                        <div class="fw-semibold mb-1">Selected Period</div>
                        <div class="text-muted">{{ $months[$filters['month']] }} {{ $filters['year'] }}</div>
                    </div>
                    <div class="text-muted small">
                        Showing {{ $summaries->total() }} employee{{ $summaries->total() === 1 ? '' : 's' }}
                    </div>
                </div>

                @if ($summaries->count())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle monthly-payroll-summary-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee No</th>
                                    <th>Employee Name</th>
                                    <th>Salary Grade</th>
                                    <th>Employment Type</th>
                                    <th>Month</th>
                                    <th>Year</th>
                                    <th class="text-end">Salary Total</th>
                                    <th class="text-end">Hazard Pay Total</th>
                                    <th class="text-end">SLA Pay Total</th>
                                    <th class="text-end">Pera &amp; Rata Total</th>
                                    <th class="text-end">Longevity Total</th>
                                    <th class="text-end">Government Bonuses Total</th>
                                    <th class="text-end">Grand Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summaries as $summary)
                                    @php
                                        $isPermanent = (int) $summary->has_permanent_salary === 1 && (int) $summary->has_cos_salary === 0;
                                        $firstCutoffLabel = $isPermanent ? '15th' : '1st Cutoff';
                                        $secondCutoffLabel = $isPermanent ? '30th' : '2nd Cutoff';
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $summary->employee_no }}</td>
                                        <td>{{ $summary->employee_name }}</td>
                                        <td>{{ $summary->salary_grade }}</td>
                                        <td>{{ $summary->employment_type }}</td>
                                        <td>{{ $months[$filters['month']] }}</td>
                                        <td>{{ $filters['year'] }}</td>
                                        <td class="text-end">
                                            <span
                                                class="monthly-payroll-summary-salary-tooltip"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                data-bs-html="true"
                                                title="{{ $firstCutoffLabel }}: {{ number_format((float) $summary->salary_first_cutoff_total, 2) }}<br>{{ $secondCutoffLabel }}: {{ number_format((float) $summary->salary_second_cutoff_total, 2) }}"
                                            >
                                                {{ number_format((float) $summary->salary_total, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ number_format((float) $summary->hazard_pay_total, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $summary->sla_pay_total, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $summary->pera_rata_total, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $summary->longevity_total, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $summary->government_bonus_total, 2) }}</td>
                                        <td class="text-end fw-bold">{{ number_format((float) $summary->grand_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $summaries->links() }}
                    </div>
                @else
                    <div class="monthly-payroll-summary-empty text-center py-5 px-4">
                        <div class="fw-semibold mb-2">No payroll summary records found.</div>
                        <div class="text-muted">Try changing the employee search, month, or year filters.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
            new bootstrap.Tooltip(element);
        });
    });
</script>
@endsection

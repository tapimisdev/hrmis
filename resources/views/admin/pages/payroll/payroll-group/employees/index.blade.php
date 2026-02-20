@extends('admin.layouts.app')

@section('styles')
<style>
    .page-actions {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 1rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .stat-pill {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .45rem .75rem;
        border-radius: 999px;
        background: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
    }

    .stat-pill .badge {
        font-size: .85rem;
        padding: .4rem .6rem;
        border-radius: 999px;
    }

    #employeeTable thead th {
        position: static;
        background: var(--bs-body-bg);
    }

    .row-selectable {
        cursor: pointer;
        transition: background-color .12s ease, box-shadow .12s ease;
    }

    .row-selected {
        background: rgba(var(--bs-success-rgb), .12) !important;
        box-shadow: inset 0 0 0 1px rgba(var(--bs-success-rgb), .25);
    }

    .emp-meta {
        display: flex;
        flex-direction: column;
        line-height: 1.15;
    }

    .search-wrap {
        position: relative;
    }

    .search-wrap .form-control {
        padding-left: 2.35rem;
        background: var(--bs-body-bg);
        border-color: var(--bs-border-color);
        color: var(--bs-body-color);
    }

    .search-icon {
        position: absolute;
        left: .85rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--bs-secondary-color);
        pointer-events: none;
    }

    .btn-soft {
        background: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        color: var(--bs-body-color);
    }

    .btn-soft:hover {
        background: var(--bs-secondary-bg);
    }

    .empty-state {
        border: 1px dashed var(--bs-border-color);
        border-radius: 1rem;
        padding: 2rem 1rem;
        background: var(--bs-tertiary-bg);
        color: var(--bs-secondary-color);
    }

    /* Division header row */
    .division-row td {
        background: var(--bs-tertiary-bg);
        border-top: 1px solid var(--bs-border-color);
        border-bottom: 1px solid var(--bs-border-color);
    }
</style>
@endsection

@section('content')
<div class="container-fluid">

    {{-- Sticky actions --}}
    <div class="page-actions mt-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-0 fw-semibold">Payroll Group Employees</h5>
                <div class="text-muted small">Manage employee assignments in this group</div>
            </div>

            <div class="d-flex gap-2">
                <button id="btnCheckAll" class="btn btn-soft btn-sm">
                    <i class="fa-regular fa-square-check me-2"></i>Check all
                </button>
                <button id="btnUncheckAll" class="btn btn-soft btn-sm">
                    <i class="fa-regular fa-square me-2"></i>Uncheck all
                </button>
                <button id="btnSave" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-save me-2"></i>Save
                </button>
            </div>
        </div>
    </div>

    {{-- GROUP DETAILS --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <div class="text-muted small mb-1">Group</div>
                    <div class="fw-semibold fs-5">{{ $group->name }}</div>
                    <div class="text-muted small">
                        <i class="fa-solid fa-briefcase me-1"></i>
                        {{ $group->employment_type_name ?? '-' }}
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <div class="stat-pill">
                        <i class="fa-solid fa-users text-primary"></i>
                        <div class="text-muted small">In Group</div>
                        <span class="badge bg-primary" id="employeeCount">{{ $group->employee_count ?? 0 }}</span>
                    </div>

                    <div class="stat-pill">
                        <i class="fa-solid fa-check text-success"></i>
                        <div class="text-muted small">Selected Now</div>
                        <span class="badge bg-success" id="selectedCount">0</span>
                    </div>
                </div>
            </div>

            <hr class="my-3">

            <div class="text-muted small mb-1">Remarks</div>
            <div>{{ $group->remarks ?? '-' }}</div>
        </div>
    </div>

    {{-- EMPLOYEE LIST --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <div class="row g-2 mb-3 align-items-center">
                <div class="col-md-7">
                    <div class="position-relative search-wrap">
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        <input id="search" class="form-control" placeholder="Search (name / employee no / division / position)..." />
                    </div>
                </div>
                <div class="col-md-5 text-md-end">
                    <button id="btnClearSearch" class="btn btn-soft btn-sm">
                        <i class="fa-solid fa-xmark me-2"></i>Clear search
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" id="employeeTable">
                    <thead>
                        <tr>
                            <th style="width:70px;" class="text-muted text-uppercase small">Select</th>
                            <th class="text-muted text-uppercase small">Employee</th>
                            <th class="text-muted text-uppercase small">Division</th>
                            <th class="text-muted text-uppercase small">Position</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            // Group employees by division (fallback to 'Unassigned')
                            $grouped = collect($employees)->groupBy(function ($e) {
                                return $e->division_name ?: 'Unassigned';
                            });
                        @endphp

                        @forelse($grouped as $division => $rows)
                            {{-- Division header --}}
                            <tr class="division-row table-light" data-division="{{ strtolower($division) }}">
                                <td colspan="4" class="fw-semibold text-uppercase small">
                                    <i class="fa-solid fa-sitemap me-2"></i>{{ $division }}
                                    <span class="text-muted fw-normal ms-2">({{ $rows->count() }})</span>
                                </td>
                            </tr>

                            {{-- Division employees --}}
                            @foreach($rows as $emp)
                                @php
                                    $empNo = $emp->employee_no ?? null;
                                    $fullName = trim(($emp->lastname ?? '') . ', ' . ($emp->firstname ?? ''));
                                    $isChecked = $empNo && in_array($empNo, $selected ?? []);
                                @endphp
                                <tr class="row-selectable {{ $isChecked ? 'row-selected' : '' }}"
                                    data-emp="{{ $empNo }}"
                                    data-division="{{ strtolower($division) }}"
                                >
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="form-check-input emp-check"
                                            value="{{ $empNo }}"
                                            {{ $isChecked ? 'checked' : '' }}
                                        />
                                    </td>
                                    <td>
                                        <div class="emp-meta">
                                            <span class="fw-semibold">{{ $fullName ?: '-' }}</span>
                                            <span class="text-muted small">
                                                <i class="fa-solid fa-id-badge me-1"></i>#{{ $empNo ?? '-' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>{{ $emp->division_name ?? '-' }}</td>
                                    <td>{{ $emp->position_name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="4" class="py-4">
                                    <div class="empty-state text-center text-muted">
                                        <div class="mb-2">
                                            <i class="fa-regular fa-face-frown fs-3"></i>
                                        </div>
                                        <div class="fw-semibold">No employees found</div>
                                        <div class="small">Try changing your search keywords.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    const groupId = @json($group->id);

    function getSelected() {
        return $('.emp-check:checked').map(function () {
            return $(this).val();
        }).get();
    }

    function syncRowHighlight() {
        $('#employeeTable tbody tr.row-selectable').each(function () {
            const checked = $(this).find('.emp-check').prop('checked');
            $(this).toggleClass('row-selected', checked);
        });
    }

    function updateCount() {
        $('#selectedCount').text(getSelected().length);
        syncRowHighlight();
    }

    updateCount();

    $('#btnCheckAll').on('click', function () {
        $('.emp-check:visible').prop('checked', true);
        updateCount();
    });

    $('#btnUncheckAll').on('click', function () {
        $('.emp-check:visible').prop('checked', false);
        updateCount();
    });

    $(document).on('change', '.emp-check', function () {
        updateCount();
    });

    // Click row to toggle checkbox (except when clicking the checkbox itself)
    $(document).on('click', '.row-selectable', function (e) {
        if ($(e.target).is('input, label, a, button, i')) return;
        const cb = $(this).find('.emp-check');
        cb.prop('checked', !cb.prop('checked')).trigger('change');
    });

    // Search (with proper division header visibility)
    $('#search').on('keyup', function () {
        const q = $(this).val().toLowerCase().trim();

        // Toggle employee rows
        $('#employeeTable tbody tr.row-selectable').each(function () {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(q));
        });

        // Toggle division headers based on visible rows under them
        $('#employeeTable tbody tr.division-row').each(function () {
            let hasVisible = false;

            let $next = $(this).next();
            while ($next.length && !$next.hasClass('division-row')) {
                if ($next.hasClass('row-selectable') && $next.is(':visible')) {
                    hasVisible = true;
                    break;
                }
                $next = $next.next();
            }

            // If search empty -> show all headers
            $(this).toggle(q === '' || hasVisible);
        });
    });

    $('#btnClearSearch').on('click', function () {
        $('#search').val('').trigger('keyup').focus();
    });

    $('#btnSave').on('click', async function () {
        const btn = $(this);
        const employees = getSelected();

        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Saving');

        try {
            const url = `{{ route('payroll.group.employees.store', ':id') }}`.replace(':id', groupId);

            const res = await axios.post(url, { employees }, {
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            if (res?.data?.employee_count !== undefined) {
                $('#employeeCount').text(res.data.employee_count);
            }

            Swal.fire({
                title: "Success!",
                text: res.data.message || "your data has been saved",
                icon: "success"
            });
        } catch (err) {
            console.error(err);
            Swal.fire({
                title: "Oops!",
                text: error.response.data.message,
                icon: "error"
            });
        } finally {
            btn.prop('disabled', false).html('<i class="fa-solid fa-save me-2"></i>Save');
        }
    });
});
</script>
@endsection

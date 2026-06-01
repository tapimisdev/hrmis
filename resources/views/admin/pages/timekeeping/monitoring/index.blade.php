@extends('admin.layouts.app')

@section('styles')
<style>
    .monitoring-card-body {
        height: 400px;
        overflow-y: auto;
        scroll-behavior: smooth;
    }

    .monitoring-filter-card .card-body {
        padding: 1.25rem;
    }

    .monitoring-filter-grid {
        align-items: start;
    }

    .monitoring-filter-grid label {
        min-height: 18px;
    }

    .monitoring-item {
        border-radius: 10px;
        padding: 10px;
        border: 1px solid transparent;
        background: rgba(var(--bs-body-color-rgb), 0.025);
        transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .monitoring-item.is-highlighted {
        border-color: #ffc107;
        background: #fff3cd;
        color: #212529;
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.28);
        transform: translateY(-1px);
    }

    .monitoring-item.is-current-match {
        border-color: #0d6efd;
        background: #e7f1ff;
        color: #212529;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.2);
    }

    .monitoring-item.is-highlighted .text-muted,
    .monitoring-item.is-current-match .text-muted {
        color: #5c636a !important;
    }

    .monitoring-column-card.has-search-match {
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.24), 0 0.5rem 1rem rgba(0, 0, 0, 0.12) !important;
    }

    @media (max-width: 991.98px) {
        .monitoring-filter-grid {
            align-items: stretch;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <x-header title="Timelogs Monitoring" subtitle="View monitoring for employee's timelogs in this module">
    </x-header>

    <div class="my-4">
        <div class="card monitoring-filter-card">
            <div class="card-body">
                <div class="row g-3 monitoring-filter-grid">
                    <div class="col-12 col-lg-4">
                        <label for="date" class="mb-2">Choose Date</label>
                        <input
                            type="date"
                            name="date"
                            id="date"
                            class="form-control"
                            value="{{ request('date', $date) }}"
                            onchange="window.location.href='?date='+this.value"
                        >
                    </div>
                    <div class="col-12 col-lg-8">
                        <label for="monitoringSearch" class="mb-2">Search Employee(s)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input
                                type="search"
                                id="monitoringSearch"
                                class="form-control"
                                placeholder="Type something..."
                                autocomplete="off"
                            >
                            <button class="btn btn-outline-secondary" type="button" id="monitoringSearchClear">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3" id="monitoringColumns">
        <div class="col-md-4">
            <div class="card shadow-sm h-100 monitoring-column-card" data-monitoring-card="clock_in">
                <div class="card-header bg-success text-white">
                    <strong class="text-uppercase">Clock In</strong>
                    <span class="badge bg-light text-dark ms-2">{{ count($columns['clock_in']) }}</span>
                </div>
                <div class="card-body monitoring-card-body" data-monitoring-column="clock_in">
                    @forelse($columns['clock_in'] as $item)
                        @php
                            $employee = $item['employee'];
                            $log = $item['log'];
                            $name = trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? ''));
                            $time = $log['time_in'] ?? '';
                            $search = trim($name . ' ' . ($employee->employee_no ?? '') . ' ' . $time);
                            $profile = $employee->profile
                                ? Storage::url('public/users/' . $employee->employee_no . '/profile-image/' . $employee->profile)
                                : 'https://ui-avatars.com/api/?name=' . urlencode($name ?: '?') . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
                        @endphp
                        <div class="monitoring-item d-flex align-items-center mb-2" data-search="{{ $search }}" data-column="clock_in">
                            <img
                                src="{{ $profile }}"
                                class="rounded-circle me-2"
                                width="40"
                                height="40"
                                style="object-fit: cover"
                                alt="{{ $name }}"
                            >
                            <div>
                                <div class="fw-bold monitoring-item__name">{{ $name }}</div>
                                <small class="text-muted">{{ $time }}</small>
                            </div>
                        </div>
                    @empty
                        <small class="text-muted text-uppercase">No employees</small>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 monitoring-column-card" data-monitoring-card="break">
                <div class="card-header bg-warning text-dark">
                    <strong class="text-uppercase">Break (Out - In)</strong>
                    <span class="badge bg-dark text-white ms-2">{{ count($columns['break']) }}</span>
                </div>
                <div class="card-body monitoring-card-body" data-monitoring-column="break">
                    @forelse($columns['break'] as $item)
                        @php
                            $employee = $item['employee'];
                            $log = $item['log'];
                            $name = trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? ''));
                            $time = $log['break'] ?? '';
                            $search = trim($name . ' ' . ($employee->employee_no ?? '') . ' ' . $time);
                            $profile = $employee->profile
                                ? Storage::url('public/users/' . $employee->employee_no . '/profile-image/' . $employee->profile)
                                : 'https://ui-avatars.com/api/?name=' . urlencode($name ?: '?') . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
                        @endphp
                        <div class="monitoring-item d-flex align-items-center mb-2" data-search="{{ $search }}" data-column="break">
                            <img
                                src="{{ $profile }}"
                                class="rounded-circle me-2"
                                width="40"
                                height="40"
                                style="object-fit: cover"
                                alt="{{ $name }}"
                            >
                            <div>
                                <div class="fw-bold monitoring-item__name">{{ $name }}</div>
                                <small class="text-muted">{{ $time }}</small>
                            </div>
                        </div>
                    @empty
                        <small class="text-muted text-uppercase">No employees</small>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100 monitoring-column-card" data-monitoring-card="clock_out">
                <div class="card-header bg-danger text-white">
                    <strong class="text-uppercase">Clock Out</strong>
                    <span class="badge bg-light text-dark ms-2">{{ count($columns['clock_out']) }}</span>
                </div>
                <div class="card-body monitoring-card-body" data-monitoring-column="clock_out">
                    @forelse($columns['clock_out'] as $item)
                        @php
                            $employee = $item['employee'];
                            $log = $item['log'];
                            $name = trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? ''));
                            $time = $log['time_out'] ?? '';
                            $search = trim($name . ' ' . ($employee->employee_no ?? '') . ' ' . $time);
                            $profile = $employee->profile
                                ? Storage::url('public/users/' . $employee->employee_no . '/profile-image/' . $employee->profile)
                                : 'https://ui-avatars.com/api/?name=' . urlencode($name ?: '?') . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
                        @endphp
                        <div class="monitoring-item d-flex align-items-center mb-2" data-search="{{ $search }}" data-column="clock_out">
                            <img
                                src="{{ $profile }}"
                                class="rounded-circle me-2"
                                width="40"
                                height="40"
                                style="object-fit: cover"
                                alt="{{ $name }}"
                            >
                            <div>
                                <div class="fw-bold monitoring-item__name">{{ $name }}</div>
                                <small class="text-muted">{{ $time }}</small>
                            </div>
                        </div>
                    @empty
                        <small class="text-muted text-uppercase">No employees</small>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (() => {
        const state = {
            query: '',
        };

        const normalize = (value) => String(value ?? '').toLowerCase();

        function applySearch() {
            const query = normalize(state.query.trim());
            const items = [...document.querySelectorAll('.monitoring-item')];

            items.forEach((item) => item.classList.remove('is-highlighted', 'is-current-match'));
            document.querySelectorAll('.monitoring-column-card').forEach((card) => {
                card.classList.remove('has-search-match');
            });

            if (!query) {
                return;
            }

            const matches = items.filter((item) => {
                const haystack = normalize(`${item.dataset.search || ''} ${item.textContent || ''}`);
                return haystack.includes(query);
            });

            matches.forEach((item) => {
                item.classList.add('is-highlighted');
                const card = document.querySelector(`[data-monitoring-card="${item.dataset.column}"]`);
                if (card) {
                    card.classList.add('has-search-match');
                }
            });

            const firstMatch = matches[0];
            if (firstMatch) {
                const card = firstMatch.closest('.monitoring-column-card');

                firstMatch.classList.add('is-current-match');
                scrollItemInsideColumn(firstMatch);
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
                }
            }
        }

        function scrollItemInsideColumn(item) {
            const column = item.closest('[data-monitoring-column]');
            if (!column) return;

            const columnRect = column.getBoundingClientRect();
            const itemRect = item.getBoundingClientRect();
            const itemOffset = column.scrollTop + itemRect.top - columnRect.top;
            const centeredOffset = itemOffset - (column.clientHeight / 2) + (item.clientHeight / 2);
            column.scrollTo({
                top: Math.max(centeredOffset, 0),
                behavior: 'smooth',
            });
        }

        function bindSearch() {
            const input = document.getElementById('monitoringSearch');
            const clear = document.getElementById('monitoringSearchClear');

            if (!input) return;

            const updateSearch = () => {
                state.query = input.value;
                applySearch();
            };

            ['input', 'keyup', 'change', 'search'].forEach((eventName) => {
                input.addEventListener(eventName, updateSearch);
            });

            if (clear) {
                clear.addEventListener('click', () => {
                    state.query = '';
                    input.value = '';
                    applySearch();
                    input.focus();
                });
            }

            updateSearch();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindSearch);
        } else {
            bindSearch();
        }

    })();
</script>
@endsection

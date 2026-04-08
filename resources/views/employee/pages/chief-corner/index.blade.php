@extends('employee.layout.app')

@section('styles')
<style>
    .chief-toolbar-form {
        display: inline-flex;
        gap: 0.75rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .chief-kpi {
        border: 1px solid rgba(var(--bs-primary-rgb), 0.08);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.05);
        border-radius: 1.15rem;
        padding: 1rem 1.1rem;
        background: linear-gradient(180deg, rgba(var(--bs-body-bg-rgb), 1) 0%, rgba(var(--bs-primary-rgb), 0.03) 100%);
        height: 100%;
    }

    .chief-kpi-label {
        font-size: 0.78rem;
        text-transform: uppercase;
        font-weight: 700;
        color: var(--bs-secondary-color);
        margin-bottom: 0.55rem;
        letter-spacing: 0.04em;
    }

    .chief-kpi-value {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1;
    }

    .chief-tab-pane {
        padding-top: 1.25rem;
    }

    .chief-filter-bar {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        margin-bottom: 1.25rem;
        padding: 1.1rem 1.15rem;
        border-radius: 1rem;
        border: 1px solid rgba(var(--bs-primary-rgb), 0.08);
        background:
            linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.05) 0%, transparent 45%),
            rgba(var(--bs-body-bg-rgb), 0.9);
    }

    .chief-filter-copy {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        min-width: 240px;
    }

    .chief-section-eyebrow {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
        color: var(--bs-primary);
    }

    .chief-inline-meta {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .chief-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        padding: 0.45rem 0.8rem;
        font-size: 0.82rem;
        color: var(--bs-body-color);
        background: rgba(var(--bs-primary-rgb), 0.08);
        border: 1px solid rgba(var(--bs-primary-rgb), 0.1);
    }

    .chief-filter-panel {
        min-width: 260px;
        padding: 0.85rem 0.95rem;
        border-radius: 0.95rem;
        background: rgba(var(--bs-body-bg-rgb), 0.88);
        border: 1px solid rgba(var(--bs-primary-rgb), 0.08);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
    }

    .chief-filter-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--bs-secondary-color);
        margin-bottom: 0.45rem;
        letter-spacing: 0.03em;
    }

    .chief-filter-select {
        min-width: 240px;
    }

    .chief-timelog-hero {
        align-items: stretch;
    }

    .chief-timelog-toolbar {
        display: grid;
        grid-template-columns: auto minmax(220px, 1fr) auto;
        gap: 0.85rem;
        align-items: center;
        min-width: min(100%, 620px);
        padding: 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(var(--bs-primary-rgb), 0.08);
        background: rgba(var(--bs-body-bg-rgb), 0.88);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
    }

    .chief-timelog-toolbar .form-control,
    .chief-timelog-toolbar .btn {
        width: 100%;
    }

    .chief-icon-btn {
        width: 52px !important;
        min-width: 52px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 0.9rem;
        font-size: 1rem;
    }

    .chief-timelog-toolbar input[type="month"] {
        min-width: 220px;
    }

    .chief-view-switch {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem;
        border-radius: 999px;
        background: rgba(var(--bs-primary-rgb), 0.06);
        border: 1px solid rgba(var(--bs-primary-rgb), 0.08);
    }

    .chief-stat-switch {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .chief-stat-switch-btn {
        border: 1px solid rgba(var(--bs-primary-rgb), 0.12);
        background: rgba(var(--bs-body-bg-rgb), 0.88);
        color: var(--bs-secondary-color);
        border-radius: 999px;
        padding: 0.55rem 0.9rem;
        font-weight: 700;
        min-height: 42px;
        transition: all 0.2s ease;
    }

    .chief-stat-switch-btn:hover:not(:disabled) {
        color: var(--bs-primary);
        border-color: rgba(var(--bs-primary-rgb), 0.32);
        background: rgba(var(--bs-primary-rgb), 0.06);
    }

    .chief-stat-switch-btn.active {
        color: #fff;
        border-color: transparent;
        background: linear-gradient(135deg, var(--bs-primary) 0%, #0b5ed7 100%);
        box-shadow: 0 10px 24px rgba(var(--bs-primary-rgb), 0.22);
    }

    .chief-stat-switch-btn:disabled {
        opacity: 0.6;
        cursor: wait;
    }

    .chief-view-switch-btn {
        border: 0;
        background: transparent;
        color: var(--bs-secondary-color);
        border-radius: 999px;
        padding: 0.55rem 0.9rem;
        font-weight: 700;
        transition: all 0.2s ease;
    }

    .chief-view-switch-btn.active {
        background: linear-gradient(135deg, var(--bs-primary) 0%, #0b5ed7 100%);
        color: #fff;
        box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb), 0.22);
    }

    .chief-view-switch-btn:disabled {
        opacity: 0.6;
        cursor: wait;
    }

    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        background: var(--bs-body-bg);
        color: var(--bs-body-color);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.5rem;
    }

    .dataTables_wrapper {
        position: relative;
    }

    .dataTables_wrapper .dataTables_processing {
        position: absolute;
        top: 4.25rem;
        left: 50%;
        transform: translateX(-50%);
        margin: 0;
        padding: 0.7rem 1rem;
        width: auto;
        min-width: 180px;
        border-radius: 999px;
        border: 1px solid rgba(var(--bs-primary-rgb), 0.12);
        background: rgba(var(--bs-body-bg-rgb), 0.96);
        box-shadow: 0 14px 35px rgba(15, 23, 42, 0.12);
        color: var(--bs-body-color);
        z-index: 3;
    }

    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 0.75rem;
    }

    .chief-tab-card {
        position: relative;
        min-height: 220px;
        border: 1px solid rgba(var(--bs-primary-rgb), 0.08);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.05);
    }

    .chief-tab-loader {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 0.75rem;
        background: rgba(var(--bs-body-bg-rgb), 0.82);
        backdrop-filter: blur(4px);
        border-radius: inherit;
        z-index: 2;
    }

    .chief-tab-card.chief-card-loading > :not(.chief-tab-loader) {
        visibility: hidden;
    }

    .chief-surface {
        position: relative;
        border: 1px solid rgba(var(--bs-primary-rgb), 0.08);
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.06);
        background:
            radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.08), transparent 26%),
            linear-gradient(180deg, rgba(var(--bs-body-bg-rgb), 1) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
    }

    .chief-page-loader {
        position: absolute;
        top: 1rem;
        right: 1rem;
        z-index: 5;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.8rem 1rem;
        border-radius: 999px;
        background: rgba(var(--bs-body-bg-rgb), 0.96);
        border: 1px solid rgba(var(--bs-primary-rgb), 0.12);
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(6px);
    }

    .chief-tabs {
        gap: 0;
        border-bottom: 1px solid rgba(var(--bs-border-color-rgb), 1);
        margin-bottom: 0.5rem;
    }

    .chief-tabs .nav-link {
        border: 1px solid transparent;
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
        color: var(--bs-secondary-color);
        font-weight: 700;
        padding: 0.85rem 1.25rem;
        background: transparent;
        transition: all 0.2s ease;
        margin-bottom: -1px;
    }

    .chief-tabs .nav-link:hover:not(:disabled) {
        color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), 0.04);
        border-color: transparent transparent rgba(var(--bs-primary-rgb), 0.18) transparent;
    }

    .chief-tabs .nav-link.active {
        color: var(--bs-primary);
        background: rgba(var(--bs-body-bg-rgb), 1);
        border-color: rgba(var(--bs-border-color-rgb), 1) rgba(var(--bs-border-color-rgb), 1) rgba(var(--bs-body-bg-rgb), 1);
        box-shadow: none;
    }

    .chief-tabs .nav-link:disabled {
        opacity: 0.6;
        cursor: wait;
    }

    .chief-table {
        --bs-table-bg: transparent;
        --bs-table-striped-bg: rgba(var(--bs-primary-rgb), 0.03);
        margin-bottom: 0 !important;
    }

    .chief-table thead th {
        border-bottom-width: 1px;
        color: var(--bs-secondary-color);
        font-size: 0.74rem;
        letter-spacing: 0.06em;
        white-space: nowrap;
    }

    .chief-table tbody td {
        vertical-align: middle;
        border-color: rgba(var(--bs-primary-rgb), 0.08);
        white-space: nowrap;
    }

    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length {
        margin-bottom: 1rem;
    }

    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select,
    .chief-filter-bar .form-select,
    .chief-toolbar-form .form-control {
        min-height: 42px;
        border: 1px solid rgba(var(--bs-primary-rgb), 0.12);
        box-shadow: none;
    }

    .dataTables_wrapper .dataTables_filter input:focus,
    .dataTables_wrapper .dataTables_length select:focus,
    .chief-filter-bar .form-select:focus,
    .chief-toolbar-form .form-control:focus {
        border-color: rgba(var(--bs-primary-rgb), 0.45);
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.1);
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: 999px !important;
        margin: 0 0.15rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, var(--bs-primary) 0%, #0b5ed7 100%) !important;
        color: #fff !important;
        border-color: transparent !important;
        box-shadow: 0 10px 22px rgba(var(--bs-primary-rgb), 0.24);
    }

    .dataTables_wrapper .dataTables_info {
        color: var(--bs-secondary-color);
    }

    @media (max-width: 767.98px) {
        .chief-page-loader {
            left: 1rem;
            right: 1rem;
            justify-content: center;
        }

        .chief-tabs {
            overflow-x: auto;
            flex-wrap: nowrap;
            padding-bottom: 0.25rem;
        }

        .chief-tabs .nav-link {
            white-space: nowrap;
        }

        .chief-filter-bar {
            padding: 0.95rem;
        }

        .chief-filter-panel,
        .chief-filter-select {
            width: 100%;
            min-width: 100%;
        }

        .chief-timelog-toolbar {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
            min-width: 100%;
        }

        .chief-timelog-toolbar input[type="month"] {
            grid-column: 1 / -1;
        }

        .chief-view-switch {
            width: 100%;
            justify-content: space-between;
        }

        .chief-stat-switch {
            width: 100%;
            justify-content: flex-start;
        }
    }

    .chief-empty-state {
        padding: 2rem 1rem;
        color: var(--bs-secondary-color);
        text-align: center;
    }
</style>
@endsection

@section('content')
@php
    $activeTab = request('tab', 'overview');
    $allowedTabs = ['overview', 'applications', 'timelogs', 'credits'];
    $activeTab = in_array($activeTab, $allowedTabs, true) ? $activeTab : 'overview';
@endphp
<div class="container-fluid min-vh-100 pb-5">
    <x-employee-navbar>
        <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
    </x-employee-navbar>

    <x-header-employee title="Chief Corner" subtitle="Monitor your division team, submitted applications, timelog trends, and employee credits">
    </x-header-employee>

    <div class="card rounded-4 p-3 mb-4">
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="chief-kpi">
                    <div class="chief-kpi-label">Managed Divisions</div>
                    <div class="chief-kpi-value">{{ $totals['divisions'] }}</div>
                    <div class="small text-muted mt-2">{{ $managedDivisions->pluck('name')->join(', ') ?: 'No division assigned' }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="chief-kpi">
                    <div class="chief-kpi-label">Active Employees</div>
                    <div class="chief-kpi-value">{{ $totals['employees'] }}</div>
                    <div class="small text-muted mt-2">Inside your managed division scope</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="chief-kpi">
                    <div class="chief-kpi-label">Applications</div>
                    <div class="chief-kpi-value">{{ $totals['applications'] }}</div>
                    <div class="small text-muted mt-2">Leave, offset, overtime, and pass slip submissions</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="chief-kpi">
                    <div class="chief-kpi-label">Tracked Employees</div>
                    <div class="chief-kpi-value">{{ $totals['tracked_users'] }}</div>
                    <div class="small text-muted mt-2">{{ strtoupper($periodLabel) }} timelog monitoring</div>
                </div>
            </div>
        </div>
    </div>

    <chief-corner-index
        initial-tab="{{ $activeTab }}"
        selected-month-prop="{{ $selectedMonth }}"
        period-label="{{ strtoupper($periodLabel) }}"
        tab-endpoint-template="{{ route('chief-corner.tab', ['tab' => '__TAB__']) }}"
    ></chief-corner-index>
</div>
@endsection

@extends('employee.layout.app')

@section('content')
<div class="container pt-4">

    <header class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded border">
        <div>
            <h1 class="h3 fw-bold mb-1">Dashboard</h1>
            <p class="text-muted mb-0">Welcome back, <strong>{{ Auth::user()->name }}</strong> 👋 <br>
            Here's an overview of your activities and quick stats.</p>
        </div>
        <div class="text-end">
            <p class="mb-1"><i class="fa-regular fa-calendar"></i> {{ now()->format('F d, Y') }}</p>
            <p class="mb-0"><i class="fa-regular fa-clock"></i> {{ now()->format('h:i A') }}</p>
        </div>
    </header>

    <div class="row g-3">
        <!-- Timelogs -->
        <div class="col-md-6 col-lg-4 col-sm-6">
            <div class="card shadow dashboard-card h-100">
                <div class="card-header bg-light fw-bold">
                    Timelogs
                </div>
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="mb-3 text-muted flex-grow-1">
                        Review your daily attendance logs and ensure accurate time records.
                    </p>
                    <img src="{{ asset('img/logs.svg') }}" class="img-fluid" alt="Timelogs" style="height: 120px">
                </div>
                <div class="card-footer border-top d-flex justify-content-end bg-white border-0 gap-2">
                    <a href="{{ route('checkinout.index') }}" class="btn btn-primary px-4">
                        <i class="fa-solid fa-clock me-2"></i>  Check In/Out
                    </a>
                </div>
            </div>
        </div>

        <!-- Leave Application -->
        <div class="col-md-6 col-lg-4 col-sm-6">
            <div class="card shadow dashboard-card h-100">
                <div class="card-header bg-light fw-bold">
                    Leave Application
                </div>
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="mb-3 text-muted flex-grow-1">
                        Submit your leave requests easily and track their approval status in one place.
                    </p>
                    <img src="{{ asset('img/leave.svg') }}" class="img-fluid" alt="Leave" style="height: 120px">
                </div>
                <div class="card-footer border-top d-flex justify-content-end bg-white border-0 gap-2">
                    <a href="{{ route('leaves.index') }}" class="btn btn-outline-primary px-4">
                        <i class="fa-solid fa-list me-2"></i> View All
                    </a>
                    <a href="{{ route('leaves.create') }}" class="btn btn-primary px-4">
                        <i class="fa-solid fa-paper-plane me-2"></i> Apply
                    </a>
                </div>
            </div>
        </div>

        <!-- Travel Order -->
        <div class="col-md-6 col-lg-4 col-sm-6">
            <div class="card shadow dashboard-card h-100">
                <div class="card-header bg-light fw-bold">
                    Official Business Slip
                </div>
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="mb-3 text-muted flex-grow-1">
                        Manage and monitor your OBS seamlessly, from creation to approval.
                    </p>
                    <img src="{{ asset('img/travel.svg') }}" class="img-fluid" alt="Travel Order" style="height: 120px">
                </div>
                <div class="card-footer border-top d-flex justify-content-end bg-white border-0 gap-2">
                    <a href="{{ route('obs.index') }}" class="btn btn-outline-primary px-4">
                        <i class="fa-solid fa-list me-2"></i> View All
                    </a>
                    <a href="{{ route('obs.create') }}" class="btn btn-primary px-4">
                        <i class="fa-solid fa-plus me-2"></i> New Order
                    </a>
                </div>
            </div>
        </div>

        <!-- Overtime -->
        <div class="col-md-6 col-lg-4 col-sm-6">
            <div class="card shadow dashboard-card h-100">
                <div class="card-header bg-light fw-bold">
                    Overtime
                </div>
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="mb-3 text-muted flex-grow-1">
                        File your overtime requests and monitor their approval status quickly and efficiently.
                    </p>
                    <img src="{{ asset('img/overtime.svg') }}" class="img-fluid" alt="Overtime" style="height: 120px">
                </div>
                <div class="card-footer border-top d-flex justify-content-end bg-white border-0 gap-2">
                    <a href="{{ route('overtime.index') }}" class="btn btn-outline-primary px-4">
                        <i class="fa-solid fa-list me-2"></i> View All
                    </a>
                    <a href="{{ route('overtime.create') }}" class="btn btn-primary px-4">
                        <i class="fa-solid fa-plus me-2"></i> File Overtime
                    </a>
                </div>
            </div>
        </div>

        <!-- Payslip -->
        <div class="col-md-6 col-lg-4 col-sm-6">
            <div class="card shadow dashboard-card h-100">
                <div class="card-header bg-light fw-bold">
                    Payslip
                </div>
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="mb-3 text-muted flex-grow-1">
                        Access and download your payslips securely anytime, anywhere.
                    </p>
                    <img src="{{ asset('img/payslip.svg') }}" class="img-fluid" alt="Payslip" style="height: 120px">
                </div>
                <div class="card-footer border-top d-flex justify-content-end bg-white border-0 gap-2">
                    <button class="btn btn-outline-primary px-4">
                        <i class="fa-solid fa-list me-2"></i> View All
                    </button>
                    <button class="btn btn-primary px-4">
                        <i class="fa-solid fa-download me-2"></i> Download Latest
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
@endsection
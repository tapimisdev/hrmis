<div class="sidebar border-1">
    <div class="sidebar-title">
        <img src="{{ asset('img/HR_NROLL.png') }}" alt="logo">
    </div>

    <ul class="sidebar-list">
        {{-- Dashboard --}}
        <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link pe-5">
                <i class="fa-solid fa-house me-2"></i> Dashboard
            </a>
        </li>

        {{-- HRIS --}}
        <li class="sidebar-item {{ Str::contains(request()->path(), 'hris') ? 'active' : '' }}">
            <a class="sidebar-link collapse-link dropdown-toggle d-flex justify-content-between"
               data-bs-toggle="collapse" data-bs-target="#hris"
               role="button" aria-expanded="false" aria-controls="hris">
                <div>
                    <i class="fa-solid fa-coins px-2"></i> HRIS
                </div>
            </a>
            <div class="collapse collapsable" id="hris" data-bs-parent=".sidebar-list">
                <ul class="nested-list">
                    <li class="nested-item px-3 py-2">
                        <a href="{{ route('hris.employee.index') }}" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-chart-simple"></i> Employee List
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Timekeeping --}}
        <li class="sidebar-item {{ Str::contains(request()->path(), 'timekeeping') ? 'active' : '' }}">
            <a class="sidebar-link collapse-link dropdown-toggle d-flex justify-content-between"
               data-bs-toggle="collapse" data-bs-target="#timekeeping"
               role="button" aria-expanded="false" aria-controls="timekeeping">
                <div>
                    <i class="fa-solid fa-clock px-2"></i> Timekeeping
                </div>
            </a>
            <div class="collapse collapsable" id="timekeeping" data-bs-parent=".sidebar-list">
                <ul class="nested-list">
                    <li class="nested-item px-3 py-2">
                        <a href="{{ route('timelogs.index') }}" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-stopwatch"></i> Timelogs
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Payroll --}}
        <li class="sidebar-item {{ Str::contains(request()->path(), 'payroll') ? 'active' : '' }}">
            <a class="sidebar-link collapse-link dropdown-toggle d-flex justify-content-between"
               data-bs-toggle="collapse" data-bs-target="#payroll"
               role="button" aria-expanded="false" aria-controls="payroll">
                <div>
                    <i class="fa-solid fa-money-check-dollar px-2"></i> Payroll
                </div>
            </a>
            <div class="collapse collapsable" id="payroll" data-bs-parent=".sidebar-list">
                <ul class="nested-list">
                    <li class="nested-item px-3 py-2">
                        <a href="{{ route('salary.index') }}" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-calculator"></i> Salary
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Services --}}
        <li class="sidebar-item {{ Str::contains(request()->path(), 'services') ? 'active' : '' }}">
            <a class="sidebar-link collapse-link dropdown-toggle d-flex justify-content-between"
               data-bs-toggle="collapse" data-bs-target="#services"
               role="button" aria-expanded="false" aria-controls="services">
                <div>
                    <i class="fa-solid fa-briefcase px-2"></i> Services
                </div>
            </a>
            <div class="collapse collapsable" id="services" data-bs-parent=".sidebar-list">
                <ul class="nested-list">
                    <li class="nested-item px-3 py-2">
                        <a href="{{ route('services.events.index') }}" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-chart-simple"></i> Events & Announcements
                        </a>
                    </li>
                    <li class="nested-item px-3 py-2">
                        <a href="#" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-chart-simple"></i> Leave Application
                        </a>
                    </li>
                    <li class="nested-item px-3 py-2">
                        <a href="#" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-chart-simple"></i> Passlip Application
                        </a>
                    </li>
                    <li class="nested-item px-3 py-2">
                        <a href="#" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-chart-simple"></i> Overtime Application
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Reports --}}
        <li class="sidebar-item {{ Str::contains(request()->path(), 'reports') ? 'active' : '' }}">
            <a class="sidebar-link collapse-link dropdown-toggle d-flex justify-content-between"
               data-bs-toggle="collapse" data-bs-target="#reports"
               role="button" aria-expanded="false" aria-controls="reports">
                <div>
                    <i class="fa-solid fa-file-alt px-2"></i> Reports
                </div>
            </a>
            <div class="collapse collapsable" id="reports" data-bs-parent=".sidebar-list">
                <ul class="nested-list">
                    <li class="nested-item px-3 py-2">
                        <a href="#" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-chart-simple"></i> Report Item
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Settings --}}
        <li class="sidebar-item {{ Str::contains(request()->path(), 'settings') ? 'active' : '' }}">
            <a class="sidebar-link collapse-link dropdown-toggle d-flex justify-content-between"
               data-bs-toggle="collapse" data-bs-target="#settings"
               role="button" aria-expanded="false" aria-controls="settings">
                <div>
                    <i class="fa-solid fa-gear px-2"></i> Settings
                </div>
            </a>
            <div class="collapse collapsable" id="settings" data-bs-parent=".sidebar-list">
                <ul class="nested-list">
                    <li class="nested-item px-3 py-2">
                        <a href="{{ route('organization.index', ['tab' => 'agency']) }}" class="d-flex gap-2 align-items-center">
                           <i class="fa-solid fa-building"></i> Organization
                        </a>
                    </li>
                    <li class="nested-item px-3 py-2">
                        <a href="{{ route('employment-types.index') }}" class="d-flex gap-2 align-items-center">
                           <i class="fa-solid fa-briefcase"></i> Employment Types
                        </a>
                    </li>
                    <li class="nested-item px-3 py-2">
                        <a href="{{ route('positions.index') }}" class="d-flex gap-2 align-items-center">
                           <i class="fa-solid fa-user-tie"></i> Positions
                        </a>
                    </li>
                    <li class="nested-item px-3 py-2">
                        <a href="{{ route('role-and-permission.index') }}" class="d-flex gap-2 align-items-center">
                           <i class="fa-solid fa-user-shield"></i> Roles & Permissions
                        </a>
                    </li>
                    <li class="nested-item px-3 py-2">
                        <a href="{{ route('shift.index') }}" class="d-flex gap-2 align-items-center">
                           <i class="fa-solid fa-clock"></i> Shifts
                        </a>
                    </li>
                    <li class="nested-item px-3 py-2">
                        <a href="{{ route('weekly-schedules.index') }}" class="d-flex gap-2 align-items-center">
                           <i class="fa-solid fa-calendar-week"></i> Weekly Schedules
                        </a>
                    </li>
                    <li class="nested-item px-3 py-2">
                        <a href="{{ route('holiday.index') }}" class="d-flex gap-2 align-items-center">
                           <i class="fa-solid fa-calendar-day"></i> Holidays
                        </a>
                    </li>
                    <li class="nested-item px-3 py-2">
                        <a href="{{ route('earnings.index') }}" class="d-flex gap-2 align-items-center">
                           <i class="fa-solid fa-hand-holding-dollar"></i> Earnings
                        </a>
                    </li>
                    <li class="nested-item px-3 py-2">
                        <a href="{{ route('deductions.index') }}" class="d-flex gap-2 align-items-center">
                           <i class="fa-solid fa-money-bill-wave"></i> Deductions
                        </a>
                    </li>
                    <li class="nested-item px-3 py-2">
                        <a href="{{ route('settings.leaves.index') }}" class="d-flex gap-2 align-items-center">
                            Leaves
                        </a>
                    </li>
                    <li class="nested-item p-2">
                        <a href="{{ route('settings.tranche.index') }}" class="d-flex gap-2 align-items-center">
                            Tranches
                        </a>
                    </li>
                    <li class="nested-item p-2">
                        <a href="{{ route('settings.approvers.index') }}" class="d-flex gap-2 align-items-center">
                            Approvers
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Logout --}}
        <li class="sidebar-item d-lg-none">
            <a class="sidebar-link pe-5" href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fa-solid fa-right-from-bracket"></i> {{ __('Logout') }}
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </li>
    </ul>
</div>

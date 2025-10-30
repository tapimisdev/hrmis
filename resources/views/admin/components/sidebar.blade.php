<div class="sidebar">
    <!-- Logo Section -->
    <div class="sidebar-title">
        <img src="{{ asset('img/orbit.png') }}" alt="Orbit">
    </div>

    <!-- Navigation List -->
    <ul class="sidebar-list">
        
        {{-- Dashboard --}}
        <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link">
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>
        </li>

        {{-- HRIS --}}
        <li class="sidebar-item {{ Str::contains(request()->path(), 'hris') ? 'active' : '' }}">
            <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'hris') ? '' : 'collapsed' }}"
               data-bs-toggle="collapse" 
               data-bs-target="#hris"
               role="button" 
               aria-expanded="{{ Str::contains(request()->path(), 'hris') ? 'true' : 'false' }}" 
               aria-controls="hris">
                <i class="fa-solid fa-users"></i>
                <span>HRIS</span>
            </a>
            <div class="collapse collapsable {{ Str::contains(request()->path(), 'hris') ? 'show' : '' }}" 
                 id="hris">
                <ul class="nested-list">
                    <li class="nested-item">
                        <a href="{{ route('hris.employee.index') }}" 
                           class="{{ request()->routeIs('hris.employee.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-user-group"></i>
                            <span>Employee List</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Timekeeping --}}
        <li class="sidebar-item {{ Str::contains(request()->path(), 'timekeeping') ? 'active' : '' }}">
            <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'timekeeping') ? '' : 'collapsed' }}"
               data-bs-toggle="collapse" 
               data-bs-target="#timekeeping"
               role="button" 
               aria-expanded="{{ Str::contains(request()->path(), 'timekeeping') ? 'true' : 'false' }}" 
               aria-controls="timekeeping">
                <i class="fa-solid fa-clock"></i>
                <span>Timekeeping</span>
            </a>
            <div class="collapse collapsable {{ Str::contains(request()->path(), 'timekeeping') ? 'show' : '' }}" 
                 id="timekeeping">
                <ul class="nested-list">
                    <li class="nested-item">
                        <a href="{{ route('timelogs.index') }}"
                           class="{{ request()->routeIs('timelogs.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-stopwatch"></i>
                            <span>Timelogs</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('import.timelogs.index') }}"
                           class="{{ request()->routeIs('import.timelogs.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-file-import"></i>
                            <span>Import Timelogs</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Payroll --}}
        <li class="sidebar-item {{ Str::contains(request()->path(), 'payroll') ? 'active' : '' }}">
            <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'payroll') ? '' : 'collapsed' }}"
               data-bs-toggle="collapse" 
               data-bs-target="#payroll"
               role="button" 
               aria-expanded="{{ Str::contains(request()->path(), 'payroll') ? 'true' : 'false' }}" 
               aria-controls="payroll">
                <i class="fa-solid fa-money-check-dollar"></i>
                <span>Payroll</span>
            </a>
            <div class="collapse collapsable {{ Str::contains(request()->path(), 'payroll') ? 'show' : '' }}" 
                 id="payroll">
                <ul class="nested-list">
                    <li class="nested-item">
                        <a href="{{ route('salary.index') }}"
                           class="{{ request()->routeIs('salary.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-calculator"></i>
                            <span>Salary</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Services --}}
        <li class="sidebar-item {{ Str::contains(request()->path(), 'service') ? 'active' : '' }}">
            <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'services') ? '' : 'collapsed' }}"
               data-bs-toggle="collapse" 
               data-bs-target="#services"
               role="button" 
               aria-expanded="{{ Str::contains(request()->path(), 'services') ? 'true' : 'false' }}" 
               aria-controls="services">
                <i class="fa-solid fa-briefcase"></i>
                <span>Services</span>
            </a>
            <div class="collapse collapsable {{ Str::contains(request()->path(), 'services') ? 'show' : '' }}" 
                 id="services">
                <ul class="nested-list">
                    <li class="nested-item">
                        <a href="{{ route('services.events.index') }}"
                           class="{{ request()->routeIs('services.events.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-calendar-days"></i>
                            <span>Events & <br> Announcements</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('services.suspensions.index') }}"
                           class="{{ request()->routeIs('services.suspensions.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-ban"></i>
                            <span>Suspensions</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('services.leaves.index') }}"
                           class="{{ request()->routeIs('services.leaves.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-umbrella-beach"></i>
                            <span>Leave Application</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('services.pass_slip.index') }}"
                           class="{{ request()->routeIs('services.pass_slip.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-id-card"></i>
                            <span>Pass Slip Application</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('services.overtime.index') }}"
                           class="{{ request()->routeIs('services.overtime.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-id-card"></i>
                            <span>Overtime Application</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Reports --}}
        <li class="sidebar-item {{ Str::contains(request()->path(), 'reports') ? 'active' : '' }}">
            <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'reports') ? '' : 'collapsed' }}"
            data-bs-toggle="collapse" 
            data-bs-target="#reports"
            role="button"
            aria-expanded="{{ Str::contains(request()->path(), 'reports') ? 'true' : 'false' }}" 
            aria-controls="reports">
                <i class="fa-solid fa-file-lines"></i>
                <span>Reports</span>
            </a>

            <div class="collapse collapsable {{ Str::contains(request()->path(), 'reports') ? 'show' : '' }}" id="reports">
                <ul class="nested-list">

                    {{-- REGULAR REPORTS --}}
                    <li class="nested-item">
                        <a class="dropdown-toggle collapsed" data-bs-toggle="collapse" href="#regularReports" role="button"
                        aria-expanded="false" aria-controls="regularReports">
                            <i class="fa-solid fa-user-tie"></i>
                            <span>Regular</span>
                        </a>
                        <div class="collapse" id="regularReports">
                            <ul class="nested-sublist">
                                <li><a href="#" class="{{ request()->routeIs('reports.midyear') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> Mid-Year Bonus</a></li>
                                <li><a href="#" class="{{ request()->routeIs('reports.payroll') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> Payroll</a></li>
                                <li><a href="#" class="{{ request()->routeIs('reports.payslip') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> Payslip</a></li>
                                <li><a href="#" class="{{ request()->routeIs('reports.longevity') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> Longevity (LP)</a></li>
                                <li><a href="#" class="{{ request()->routeIs('reports.pera') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> PERA & RATA</a></li>
                                <li><a href="#" class="{{ request()->routeIs('reports.salarytax') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> Salary Tax</a></li>
                                <li><a href="#" class="{{ request()->routeIs('reports.hptax') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> HP Tax</a></li>
                                <li><a href="#" class="{{ request()->routeIs('reports.longevitytax') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> Longevity Tax</a></li>
                                <li><a href="#" class="{{ request()->routeIs('reports.actualpresence') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> Actual Presence HP</a></li>
                                <li><a href="#" class="{{ request()->routeIs('reports.hazardpay') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> Hazard Pay (HP)</a></li>
                            </ul>
                        </div>
                    </li>

                    {{-- COS REPORTS --}}
                    <li class="nested-item">
                        <a class="dropdown-toggle collapsed" data-bs-toggle="collapse" href="#cosReports" role="button"
                        aria-expanded="false" aria-controls="cosReports">
                            <i class="fa-solid fa-user-clock"></i>
                            <span>COS</span>
                        </a>
                        <div class="collapse" id="cosReports">
                            <ul class="nested-sublist">
                                <li><a href="#" class="{{ request()->routeIs('reports.cospayroll') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> Payroll</a></li>
                                <li><a href="#" class="{{ request()->routeIs('reports.cospayslip') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> Payslip</a></li>
                                <li><a href="#" class="{{ request()->routeIs('reports.coshazard') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> Hazard Pay</a></li>
                                <li><a href="#" class="{{ request()->routeIs('reports.costax') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> Tax Summary</a></li>
                            </ul>
                        </div>
                    </li>

                </ul>
            </div>
        </li>


        {{-- Maintenance --}}
        <li class="sidebar-item {{ Str::contains(request()->path(), 'maintenance') ? 'active' : '' }}">
            <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'maintenance') ? '' : 'collapsed' }}"
               data-bs-toggle="collapse" 
               data-bs-target="#maintenance"
               role="button" 
               aria-expanded="{{ Str::contains(request()->path(), 'maintenance') ? 'true' : 'false' }}" 
               aria-controls="maintenance">
                <i class="fa-solid fa-gear"></i>
                <span>Maintenance</span>
            </a>
            <div class="collapse collapsable {{ Str::contains(request()->path(), 'maintenance') ? 'show' : '' }}" 
                 id="maintenance">
                <ul class="nested-list">
                    <li class="nested-item">
                        <a href="{{ route('organization.index', ['tab' => 'agency']) }}"
                           class="{{ request()->routeIs('organization.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-building"></i>
                            <span>Organization</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('projects.index') }}"
                           class="{{ request()->routeIs('projects.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-diagram-project"></i>
                            <span>Projects</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('employment-types.index') }}"
                           class="{{ request()->routeIs('employment-types.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-briefcase"></i>
                            <span>Employment Types</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('positions.index') }}"
                           class="{{ request()->routeIs('positions.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-user-tie"></i>
                            <span>Positions</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('role-and-permission.index') }}"
                           class="{{ request()->routeIs('role-and-permission.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-user-shield"></i>
                            <span>Roles & Permissions</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('shift.index') }}"
                           class="{{ request()->routeIs('shift.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span>Shifts</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('weekly-schedules.index') }}"
                           class="{{ request()->routeIs('weekly-schedules.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-calendar-week"></i>
                            <span>Weekly Schedules</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('holiday.index') }}"
                           class="{{ request()->routeIs('holiday.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-calendar-day"></i>
                            <span>Holidays</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('earnings.index') }}"
                           class="{{ request()->routeIs('earnings.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                            <span>Earnings</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('deductions.index') }}"
                           class="{{ request()->routeIs('deductions.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                            <span>Deductions</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('settings.leaves.index') }}"
                           class="{{ request()->routeIs('settings.leaves.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-leaf"></i>
                            <span>Leaves</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('settings.tranche.index') }}"
                           class="{{ request()->routeIs('settings.tranche.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>Tranches</span>
                        </a>
                    </li>
                    <li class="nested-item">
                        <a href="{{ route('settings.approvers.index') }}"
                           class="{{ request()->routeIs('settings.approvers.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-user-check"></i>
                            <span>Approvers</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        {{-- Logout (Mobile Only) --}}
        <li class="sidebar-item d-lg-none">
            <a class="sidebar-link" 
               href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </li>
    </ul>
</div>
<!-- Sidebar -->
<aside>
    <button @click="toggleMobileMenu" class="d-md-none x-mark"><i class="fa-solid fa-xmark"></i></button>
    <div class="sidebar shadow" id="sidebar">
        <!-- Header -->
        <header class="sidebar-header">
            <div class="sidebar-brand">
                <div class="brand-body">
                    <button id="imgSwitchBtn" class="p-0 border-0 ">
                        <img src="{{ asset('img/dost-tapi.png') }}" alt="TAPI Logo">
                    </button>
                    <h5>Orbit</h5>
                </div>

                <button id="switchMenuBtn" class="sidebar-toggle-btn">
                    <i class="fa-regular fa-chart-bar"></i>
                </button>
            </div>
        </header>

        <!-- Sidebar Navigation -->
        <nav class="sidebar-nav">
            <div class="mb-2 ms-1">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Menu</small>
            </div>
            <ul class="side-container">
                @can('emp.dashboard.view')
                <!-- Dashboard -->
                <li class="side-items {{ request()->routeIs('dashboard.*') ? 'active' : '' }}">
                    <a href="{{ route('dashboard.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-gauge"></i>
                        </span>
                        <span class="side-text">Dashboard</span>
                    </a>
                </li>
                @endcan

                @canany([
                    'emp.timelogs.view',
                    'emp.timelogs.checkin-out'
                ])
                <!-- Timelogs -->
                <li class="side-items has-submenu {{ request()->routeIs('checkinout.*') ? 'active' : '' }}">
                    <a href="{{ route('checkinout.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-clock"></i>
                        </span>
                        <span class="side-text">Timelogs</span>
                    </a>
                </li>
                @endcanany

                <li class="side-items">
                    <a class="side-link text-body dropdown-toggle 
                        {{ Str::contains(request()->path(), 'credits/leave') || Str::contains(request()->path(), 'credits/offset') ? '' : 'collapsed' }}"
                        data-bs-toggle="collapse"
                        data-bs-target="#credits"
                        role="button"
                        aria-expanded="{{ Str::contains(request()->path(), 'credits/leave') || Str::contains(request()->path(), 'credits/offset') ? 'true' : 'false' }}"
                        aria-controls="credits">

                        <i class="fa-regular fa-calendar"></i>
                        <span class="side-text">Credits</span>
                    </a>

                    <div class="collapse collapsable 
                        {{ Str::contains(request()->path(), 'credits/leave') || Str::contains(request()->path(), 'credits/offset') ? 'show' : '' }}"
                        id="credits">

                        <ul class="nested-list list-unstyled py-2">

                            {{-- Leave --}}
                            <li class="side-items nested-item py-1 px-4 mb-2 {{  Str::contains(request()->path(), 'credits/leave') ? 'active' : '' }}">
                                <a href="{{ route('leave-credits.index') }}"
                                class="d-flex justify-content-center align-items-center  text-light text-decoration-none">
                                    <i class="fa-solid fa-plane-departure"></i>
                                    <span>Leave</span>
                                </a>
                            </li>

                            {{-- Offset --}}
                            <li class="side-items nested-item py-1 px-4 mb-2 {{  Str::contains(request()->path(), 'credits/offset') ? 'active' : '' }}">
                                <a href="{{ route('offset-credits.index') }}"
                                class="d-flex justify-content-center align-items-center  text-light text-decoration-none">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <span>Offset</span>
                                </a>
                            </li>

                        </ul>
                    </div>
                </li>

                @can('emp.dashboard.view')
                <!-- Payslip -->
                <li class="side-items has-submenu {{ request()->routeIs('payslip.*') ? 'active' : '' }}">
                    <a href="{{ route('payslip.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-money-check-dollar"></i>
                        </span>
                        <span class="side-text">Payslip</span>
                    </a>
                </li>
                @endcan
                
                <div class="sidebar-seperator"></div>
                
                <div class="mb-2 ms-1">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Applications</small>
                </div>
                @canany([
                    'emp.leave_application.view',
                    'emp.leave_application.apply'
                ])
                <!-- Leave Application -->
                <li class="side-items has-submenu {{ request()->routeIs('leaves.*') ? 'active' : '' }}">
                    <a href="{{ route('leaves.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-calendar-days"></i>
                        </span>
                        <span class="side-text">Leave</span>
                    </a>
                </li>
                @endcanany

                @canany([
                    'emp.overtime_application.view',
                    'emp.overtime_application.apply'
                ])
                <!-- Overtime -->
                <li class="side-items has-submenu {{ request()->routeIs('overtime.*') ? 'active' : '' }}">
                    <a href="{{ route('overtime.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </span>
                        <span class="side-text">Overtime</span>
                    </a>
                </li>
                @endcanany

                {{--
                <div class="sidebar-seperator"></div>
                
                <!-- Leave Application -->
                <li class="side-items has-submenu {{ request()->routeIs('approval-leave.*') ? 'active' : '' }}">
                    <a href="{{ route('approval-leave.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-calendar-days"></i>
                        </span>
                        <span class="side-text">Approval Leave</span>
                    </a>
                </li>

                <!-- Pass Slip -->
                <li class="side-items has-submenu {{ request()->routeIs('approval-obs.*') ? 'active' : '' }}">
                    <a href="{{ route('approval-obs.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-file-lines"></i>
                        </span>
                        <span class="side-text">Approval Pass Slip</span>
                    </a>
                </li>

                <!-- Overtime -->
                <li class="side-items has-submenu {{ request()->routeIs('approval-overtime.*') ? 'active' : '' }}">
                    <a href="{{ route('approval-overtime.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </span>
                        <span class="side-text">Approval Overtime</span>
                    </a>
                </li>
                --}}
            </ul>
        </nav>
    </div>
</aside>
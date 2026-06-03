<!-- Sidebar -->
<aside>
    <button class="d-md-none x-mark"><i class="fa-solid fa-xmark"></i></button>
    <div class="sidebar shadow pb-5" id="sidebar">
        <!-- Header -->
        <header class="sidebar-header">
            <div class="sidebar-brand">
                <div class="brand-body">
                    <!-- <button id="imgSwitchBtn" class="p-0 border-0 btn btn-transparent">
                        <img src="{{ asset('img/dost-tapi.png') }}" alt="TAPI Logo">
                    </button> -->
                    <div class="text-center">
                        <h5 style="letter-spacing: 1px">HRIS PORTAL</h5>
                    </div>
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

                <!-- Calendar -->
                 <li class="side-items {{ request()->is('employee/calendar*') ? 'active' : '' }}">
                    <a href="{{ route('calendar.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-calendar"></i>
                        </span>
                        <span class="side-text">Calendar</span>
                    </a>
                </li>
                
                <!-- Announcements -->
                <li class="side-items {{ request()->is('employee/announcements*') ? 'active' : '' }}">
                    <a href="{{ route('announcement.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-bullhorn"></i>
                        </span>
                        <span class="side-text">Announcements</span>
                    </a>
                </li>

                <!-- Messages -->
                <li class="side-items {{ request()->routeIs('employee.messages') ? 'active' : '' }}">
                    <a href="{{ route('employee.messages') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-comments"></i>
                        </span>
                        <span class="side-text">Messages</span>
                    </a>
                </li>

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
                                class="d-flex justify-content-start align-items-center gap-2 text-body text-decoration-none">
                                    <i class="fa-solid fa-plane-departure"></i>
                                    <span>Leave</span>
                                </a>
                            </li>

                            {{-- Offset --}}
                            <li class="side-items nested-item py-1 px-4 mb-2 {{  Str::contains(request()->path(), 'credits/offset') ? 'active' : '' }}">
                                <a href="{{ route('offset-credits.index') }}"
                                class="d-flex justify-content-start align-items-center gap-2 text-body text-decoration-none">
                                    <i class="fa-solid fa-ghost"></i>
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

                @can('emp.behavioral_notices.view')
                <!-- Behavioral Notice -->
                <li class="side-items has-submenu {{ request()->routeIs('behavioral-notices.*') ? 'active' : '' }}">
                    <a href="{{ route('behavioral-notices.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-clipboard-list"></i>
                        </span>
                        <span class="side-text">Behavioral Notice</span>
                    </a>
                </li>
                @endcan

                @if(Auth::user()?->is_division_chief)
                <li class="side-items {{ request()->routeIs('chief-corner.index') ? 'active' : '' }}">
                    <a href="{{ route('chief-corner.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-user-tie"></i>
                        </span>
                        <span class="side-text">Chief Corner</span>
                    </a>
                </li>
                @endif
                
                <div class="sidebar-seperator"></div>
                
                <div class="mb-2 ms-1">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Applications</small>
                </div>
                @canany([
                    'emp.leave_application.view',
                    'emp.leave_application.apply'
                ])
                    @if(Auth::user()->employment_type_id == \App\Enums\EmploymentTypesEnum::REGULAR->value)
                        <!-- Leave Application -->
                        <li class="side-items has-submenu {{ request()->routeIs('leaves.*') ? 'active' : '' }}">
                            <a href="{{ route('leaves.index') }}" class="side-link text-body">
                                <span class="side-icon">
                                    <i class="fa-solid fa-plane-departure"></i>
                                </span>
                                <span class="side-text">Leave</span>
                            </a>
                        </li>
                    @endif
                @endcanany

                @canany([
                    'emp.offset_application.view',
                    'emp.offset_application.apply'
                ])
                <li class="side-items has-submenu {{ request()->routeIs('offset.*') ? 'active' : '' }}">
                    <a href="{{ route('offset.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-ghost"></i>
                        </span>
                        <span class="side-text">Offset</span>
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

                @canany([
                    'emp.special_order_application.view',
                    'emp.special_order_application.apply'
                ])
                <li class="side-items has-submenu {{ request()->routeIs('special-order.*') ? 'active' : '' }}">
                    <a href="{{ route('special-order.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-car-on"></i>
                        </span>
                        <span class="side-text">Special Order</span>
                    </a>
                </li>
                @endcanany

                @canany([
                    'emp.lto_application.view',
                    'emp.lto_application.apply'
                ])
                <li class="side-items has-submenu {{ request()->routeIs('lto.*') ? 'active' : '' }}">
                    <a href="{{ route('lto.index') }}" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-person-walking-luggage"></i>
                        </span>
                        <span class="side-text">Local Travel Order</span>
                    </a>
                </li>
                @endcanany

                @canany([
                    'emp.pass_slip_application.view',
                    'emp.pass_slip_application.apply'
                ])
                
                    <li class="side-items has-submenu {{ request()->routeIs('obs.*') ? 'active' : '' }}">
                        <a href="{{ route('obs.index') }}" class="side-link text-body">
                            <span class="side-icon">
                                <i class="fa-solid fa-torii-gate"></i>
                            </span>
                            <span class="side-text">Pass Slip</span>
                        </a>
                    </li> 
               
                @endcanany

                @if(config('app.allow_multi_approval'))
                    <div class="sidebar-seperator"></div>
                
                    <div class="mb-2 ms-1">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Approvals</small>
                    </div>

                    <!-- Leave Application Approval -->
                    <li class="side-items has-submenu {{ request()->routeIs('approval-leave.*') ? 'active' : '' }}">
                        <a href="{{ route('approval-leave.index') }}" class="side-link text-body">
                            <span class="side-icon">
                                <i class="fa-solid fa-calendar-days"></i>
                            </span>
                            <span class="side-text">Leave Approvals </span>
                        </a>
                    </li>

                    <!-- Pass Slip -->
                    <li class="side-items has-submenu {{ request()->routeIs('approval-obs.*') ? 'active' : '' }}">
                        <a href="{{ route('approval-obs.index') }}" class="side-link text-body">
                            <span class="side-icon">
                                <i class="fa-solid fa-file-lines"></i>
                            </span>
                            <span class="side-text">Pass Slip Approvals</span>
                        </a>
                    </li>

                    <!-- Overtime -->
                    <li class="side-items has-submenu {{ request()->routeIs('approval-overtime.*') ? 'active' : '' }}">
                        <a href="{{ route('approval-overtime.index') }}" class="side-link text-body">
                            <span class="side-icon">
                                <i class="fa-solid fa-hourglass-half"></i>
                            </span>
                            <span class="side-text">Overtime Approvals</span>
                        </a>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
</aside>

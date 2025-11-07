@canany([
    'hr.events_and_announcements.view', 
    'hr.leave_approval.view', 
    'hr.pass_slip_approval.view', 
    'hr.overtime_approval.view'
])
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

            @can('hr.events_and_announcements.view')
            <li class="nested-item">
                <a href="{{ route('services.events.index') }}"
                    class="{{ request()->routeIs('services.events.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Events & <br> Announcements</span>
                </a>
            </li>
            @endcan

            @can('hr.leave_approval.view')
            <li class="nested-item">
                <a href="{{ route('services.leaves.index') }}"
                    class="{{ request()->routeIs('services.leaves.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-umbrella-beach"></i>
                    <span>Leave Application</span>
                </a>
            </li>
            @endcan

            @can('hr.pass_slip_approval.view')
            <li class="nested-item">
                <a href="{{ route('services.pass_slip.index') }}"
                    class="{{ request()->routeIs('services.pass_slip.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-id-card"></i>
                    <span>Pass Slip Application</span>
                </a>
            </li> 
            @endcan

            @can('hr.overtime_approval.view')
            <li class="nested-item">
                <a href="{{ route('services.overtime.index') }}"
                    class="{{ request()->routeIs('services.overtime.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-clock"></i>
                    <span>Overtime Application</span>
                </a>
            </li>
            @endcan

        </ul>
    </div>
</li>
@endcanany

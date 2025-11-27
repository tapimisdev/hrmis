@canany([
    'hr.division.view',
    'hr.unit.view',
    'hr.project.view',
    'hr.employment_type.view',
    'hr.position.view',
    'hr.role_and_permission.view',
    'hr.shift.view',
    'hr.weekly_schedule.view',
    'hr.holiday.view',
    'hr.earnings.view',
    'hr.deductions.view',
    'hr.leave_type.view',
    'hr.tranche.view',
    'hr.approvers.view'
])
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

    <div class="collapse collapsable {{ Str::contains(request()->path(), 'maintenance') ? 'show' : '' }}" id="maintenance">
        <ul class="nested-list">
            @canany([
                'hr.organization.view'
            ])
            <li class="nested-item">
                <a href="{{ route('organization.index', ['tab' => 'agency']) }}"
                    class="{{ request()->routeIs('organization.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-building"></i>
                    <span>Organization</span>
                </a>
            </li>
            @endcanany

            @can('hr.project.view')
            <li class="nested-item">
                <a href="{{ route('projects.index') }}"
                    class="{{ request()->routeIs('projects.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-diagram-project"></i>
                    <span>Projects</span>
                </a>
            </li>
            @endcan

            @can('hr.employment_type.view')
            <li class="nested-item">
                <a href="{{ route('employment-types.index') }}"
                    class="{{ request()->routeIs('employment-types.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-briefcase"></i>
                    <span>Employment Types</span>
                </a>
            </li>
            @endcan

            @can('hr.position.view')
            <li class="nested-item">
                <a href="{{ route('positions.index') }}"
                    class="{{ request()->routeIs('positions.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Positions</span>
                </a>
            </li>
            @endcan

            @can('hr.role_and_permission.view')
            <li class="nested-item">
                <a href="{{ route('role-and-permission.index') }}"
                    class="{{ request()->routeIs('role-and-permission.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>Roles & Permissions</span>
                </a>
            </li>
            @endcan

            @can('hr.shift.view')
            <li class="nested-item">
                <a href="{{ route('shift.index') }}"
                    class="{{ request()->routeIs('shift.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Shifts</span>
                </a>
            </li>
            @endcan

            @can('hr.weekly_schedule.view')
            <li class="nested-item">
                <a href="{{ route('weekly-schedules.index') }}"
                    class="{{ request()->routeIs('weekly-schedules.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-week"></i>
                    <span>Weekly Schedules</span>
                </a>
            </li>
            @endcan

            @can('hr.holiday.view')
            <li class="nested-item">
                <a href="{{ route('holiday.index') }}"
                    class="{{ request()->routeIs('holiday.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-day"></i>
                    <span>Holidays</span>
                </a>
            </li>
            @endcan

            <!-- @can('hr.earnings.view')
            <li class="nested-item">
                <a href="{{ route('earnings.index') }}"
                    class="{{ request()->routeIs('earnings.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                    <span>Earnings</span>
                </a>
            </li>
            @endcan

            @can('hr.deductions.view')
            <li class="nested-item">
                <a href="{{ route('deductions.index') }}"
                    class="{{ request()->routeIs('deductions.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                    <span>Deductions</span>
                </a>
            </li>
            @endcan -->

            @can('hr.leave_type.view')
            <li class="nested-item">
                <a href="{{ route('settings.leaves.index') }}"
                    class="{{ request()->routeIs('settings.leaves.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-leaf"></i>
                    <span>Leaves</span>
                </a>
            </li>
            @endcan

            @can('hr.tranche.view')
            <li class="nested-item">
                <a href="{{ route('settings.tranche.index') }}"
                    class="{{ request()->routeIs('settings.tranche.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Tranches</span>
                </a>
            </li>
            @endcan

            @can('hr.approvers.view')
            <li class="nested-item">
                <a href="{{ route('settings.approvers.index') }}"
                    class="{{ request()->routeIs('settings.approvers.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-check"></i>
                    <span>Approvers</span>
                </a>
            </li>
            @endcan

            <li class="nested-item">
                <a href="{{ route('settings.payroll-components.index') }}"
                    class="{{ request()->routeIs('settings.payroll-components.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-grip-lines"></i>
                    <span>Payroll Components</span>
                </a>
            </li>

            <li class="nested-item">
                <a href="{{ route('settings.payroll-settings.index') }}"
                    class="{{ request()->routeIs('settings.payroll-settings.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-gears"></i>
                    <span>Payroll Settings</span>
                </a>
            </li>
        </ul>
    </div>
</li>
@endcanany

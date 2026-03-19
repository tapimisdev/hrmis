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
<li class="sidebar-item {{ request()->is('admin/maintenance*') ? 'active' : '' }}">
    <a class="sidebar-link dropdown-toggle {{ request()->is('admin/maintenance*') ? '' : 'collapsed' }}"
       data-bs-toggle="collapse"
       data-bs-target="#maintenance"
       role="button"
       aria-expanded="{{ request()->is('admin/maintenance*') ? 'true' : 'false' }}"
       aria-controls="maintenance">
        <i class="fa-solid fa-gear"></i>
        <span>Maintenance</span>
    </a>

    <div class="collapse collapsable {{ request()->is('admin/maintenance*') ? 'show' : '' }}"
         id="maintenance">
        <ul class="nested-list">

            @can('hr.organization.view')
            <li class="nested-item">
                <a href="{{ route('organization.index', ['tab' => 'agency']) }}"
                   class="{{ request()->is('admin/maintenance/organization*') ? 'active' : '' }}">
                    <i class="fa-solid fa-building"></i>
                    <span>Organization</span>
                </a>
            </li>
            @endcan

            @can('hr.project.view')
            <li class="nested-item">
                <a href="{{ route('projects.index') }}"
                   class="{{ request()->is('admin/maintenance/projects*') ? 'active' : '' }}">
                    <i class="fa-solid fa-diagram-project"></i>
                    <span>Projects</span>
                </a>
            </li>
            @endcan

            @can('hr.employment_type.view')
            <li class="nested-item">
                <a href="{{ route('employment-types.index') }}"
                   class="{{ request()->is('admin/maintenance/employment-types*') ? 'active' : '' }}">
                    <i class="fa-solid fa-briefcase"></i>
                    <span>Employment Types</span>
                </a>
            </li>
            @endcan

            @can('hr.position.view')
            <li class="nested-item">
                <a href="{{ route('positions.index') }}"
                   class="{{ request()->is('admin/maintenance/positions*') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Positions</span>
                </a>
            </li>
            @endcan

            @can('hr.role_and_permission.view')
            <li class="nested-item">
                <a href="{{ route('role-and-permission.index') }}"
                   class="{{ request()->is('admin/maintenance/role-and-permission*') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>Roles & Permissions</span>
                </a>
            </li>
            @endcan

            @can('hr.shift.view')
            <li class="nested-item">
                <a href="{{ route('shift.index') }}"
                   class="{{ request()->is('admin/maintenance/shift*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Shifts</span>
                </a>
            </li>
            @endcan

            @can('hr.weekly_schedule.view')
            <li class="nested-item">
                <a href="{{ route('weekly-schedules.index') }}"
                   class="{{ request()->is('admin/maintenance/weekly-schedules*') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-week"></i>
                    <span>Weekly Schedules</span>
                </a>
            </li>
            @endcan

            @can('hr.holiday.view')
            <li class="nested-item">
                <a href="{{ route('holiday.index') }}"
                   class="{{ request()->is('admin/maintenance/holiday*') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-day"></i>
                    <span>Holidays</span>
                </a>
            </li>
            @endcan

            @can('hr.leave_type.view')
            <li class="nested-item">
                <a href="{{ route('settings.leaves.index') }}"
                   class="{{ request()->is('admin/maintenance/leaves*') ? 'active' : '' }}">
                    <i class="fa-solid fa-leaf"></i>
                    <span>Leaves</span>
                </a>
            </li>
            @endcan
            
            @can('hr.leave_type.view')
            <li class="nested-item">
                <a href="{{ route('settings.credits.index', ['type' => 'leave']) }}"
                   class="{{ request()->is('admin/maintenance/credits*') ? 'active' : '' }}">
                    <i class="fa-regular fa-hand-point-up"></i>
                    <span>Credits</span>
                </a>
            </li>
            @endcan

            @can('hr.tranche.view')
            <li class="nested-item">
                <a href="{{ route('settings.tranche.index') }}"
                   class="{{ request()->is('admin/maintenance/tranche*') ? 'active' : '' }}">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Tranches</span>
                </a>
            </li>
            @endcan

            @can('hr.approvers.view')
            <li class="nested-item">
                <a href="{{ route('settings.approvers.index') }}"
                   class="{{ request()->is('admin/maintenance/approvers*') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-check"></i>
                    <span>Approvers</span>
                </a>
            </li>
            @endcan

            @can('hr.government_bonus_rules.view')
            <li class="nested-item">
                <a href="{{ route('government-bonus-types.index') }}"
                    class="{{ request()->is('admin/payroll/government-bonus-types*') ? 'active' : '' }}">
                    <i class="fa-solid fa-list-check"></i>
                    <span>Government Bonus Rules</span>
                </a>
            </li>
            @endcan

            @can('hr.payroll_components.view')
            <li class="nested-item">
                <a href="{{ route('settings.payroll-components.index') }}"
                   class="{{ request()->is('admin/maintenance/payroll-components*') ? 'active' : '' }}">
                    <i class="fa-solid fa-grip-lines"></i>
                    <span>Payroll Components</span>
                </a>
            </li>
            @endcan

            @can('hr.payroll_settings.view')
            <li class="nested-item">
                <a href="{{ route('settings.payroll-settings.index') }}"
                   class="{{ request()->is('admin/maintenance/payroll-settings*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gears"></i>
                    <span>Payroll Settings</span>
                </a>
            </li>
            @endcan

        </ul>
    </div>
</li>
@endcanany

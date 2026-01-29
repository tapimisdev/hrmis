<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any([
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
])): ?>
<li class="sidebar-item <?php echo e(request()->is('admin/maintenance*') ? 'active' : ''); ?>">
    <a class="sidebar-link dropdown-toggle <?php echo e(request()->is('admin/maintenance*') ? '' : 'collapsed'); ?>"
       data-bs-toggle="collapse"
       data-bs-target="#maintenance"
       role="button"
       aria-expanded="<?php echo e(request()->is('admin/maintenance*') ? 'true' : 'false'); ?>"
       aria-controls="maintenance">
        <i class="fa-solid fa-gear"></i>
        <span>Maintenance</span>
    </a>

    <div class="collapse collapsable <?php echo e(request()->is('admin/maintenance*') ? 'show' : ''); ?>"
         id="maintenance">
        <ul class="nested-list">

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.organization.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('organization.index', ['tab' => 'agency'])); ?>"
                   class="<?php echo e(request()->is('admin/maintenance/organization*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-building"></i>
                    <span>Organization</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.project.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('projects.index')); ?>"
                   class="<?php echo e(request()->is('admin/maintenance/projects*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-diagram-project"></i>
                    <span>Projects</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.employment_type.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('employment-types.index')); ?>"
                   class="<?php echo e(request()->is('admin/maintenance/employment-types*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-briefcase"></i>
                    <span>Employment Types</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.position.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('positions.index')); ?>"
                   class="<?php echo e(request()->is('admin/maintenance/positions*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Positions</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.role_and_permission.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('role-and-permission.index')); ?>"
                   class="<?php echo e(request()->is('admin/maintenance/role-and-permission*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>Roles & Permissions</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.shift.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('shift.index')); ?>"
                   class="<?php echo e(request()->is('admin/maintenance/shift*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Shifts</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.weekly_schedule.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('weekly-schedules.index')); ?>"
                   class="<?php echo e(request()->is('admin/maintenance/weekly-schedules*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-calendar-week"></i>
                    <span>Weekly Schedules</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.holiday.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('holiday.index')); ?>"
                   class="<?php echo e(request()->is('admin/maintenance/holiday*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-calendar-day"></i>
                    <span>Holidays</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.leave_type.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('settings.leaves.index')); ?>"
                   class="<?php echo e(request()->is('admin/maintenance/leaves*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-leaf"></i>
                    <span>Leaves</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.tranche.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('settings.tranche.index')); ?>"
                   class="<?php echo e(request()->is('admin/maintenance/tranche*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Tranches</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.approvers.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('settings.approvers.index')); ?>"
                   class="<?php echo e(request()->is('admin/maintenance/approvers*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-user-check"></i>
                    <span>Approvers</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="nested-item">
                <a href="<?php echo e(route('settings.payroll-components.index')); ?>"
                   class="<?php echo e(request()->is('admin/maintenance/payroll-components*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-grip-lines"></i>
                    <span>Payroll Components</span>
                </a>
            </li>

            <li class="nested-item">
                <a href="<?php echo e(route('settings.payroll-settings.index')); ?>"
                   class="<?php echo e(request()->is('admin/maintenance/payroll-settings*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-gears"></i>
                    <span>Payroll Settings</span>
                </a>
            </li>

        </ul>
    </div>
</li>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/admin/components/sidebar/items/maintenance.blade.php ENDPATH**/ ?>
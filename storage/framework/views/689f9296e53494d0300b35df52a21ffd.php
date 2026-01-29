<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any([
    'hr.events_and_announcements.view', 
    'hr.leave_approval.view', 
    'hr.pass_slip_approval.view', 
    'hr.overtime_approval.view'
])): ?>
<li class="sidebar-item <?php echo e(Str::contains(request()->path(), 'service') ? 'active' : ''); ?>">
    <a class="sidebar-link dropdown-toggle <?php echo e(Str::contains(request()->path(), 'services') ? '' : 'collapsed'); ?>"
        data-bs-toggle="collapse" 
        data-bs-target="#services"
        role="button" 
        aria-expanded="<?php echo e(Str::contains(request()->path(), 'services') ? 'true' : 'false'); ?>" 
        aria-controls="services">
        <i class="fa-solid fa-briefcase"></i>
        <span>Services</span>
    </a>
    <div class="collapse collapsable <?php echo e(Str::contains(request()->path(), 'services') ? 'show' : ''); ?>" 
            id="services">
        <ul class="nested-list">

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.events_and_announcements.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('services.events.index')); ?>"
                    class="<?php echo e(request()->is('admin/service/events*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Events & <br> Announcements</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.leave_approval.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('services.leaves.index')); ?>"
                    class="<?php echo e(request()->is('admin/service/leave*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-plane-departure"></i>
                    <span>Leave Application</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.offset_approval.view')): ?>
                <li class="nested-item">
                    <a href="<?php echo e(route('services.offset.index')); ?>"
                        class="<?php echo e(request()->is('admin/service/offset*') ? 'active' : ''); ?>">
                        <i class="fa-solid fa-ghost"></i>
                        <span>Offset Application</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.pass_slip_approval.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('services.pass_slip.index')); ?>"
                    class="<?php echo e(request()->is('admin/service/pass-slip*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-torii-gate"></i>
                    <span>Pass Slip Application</span>
                </a>
            </li> 
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.overtime_approval.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('services.overtime.index')); ?>"
                    class="<?php echo e(request()->is('admin/service/overtime*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-clock"></i>
                    <span>Overtime Application</span>
                </a>
            </li>
            <?php endif; ?>

        </ul>
    </div>
</li>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/admin/components/sidebar/items/service.blade.php ENDPATH**/ ?>
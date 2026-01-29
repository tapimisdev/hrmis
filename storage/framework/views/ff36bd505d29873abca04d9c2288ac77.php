<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.hris.view')): ?>
<li class="sidebar-item <?php echo e(Str::contains(request()->path(), 'hris') ? 'active' : ''); ?>">
    <a href="<?php echo e(route('hris.employee.index')); ?>" class="sidebar-link <?php echo e(Str::contains(request()->path(), 'hris') ? '' : 'collapsed'); ?>">
        <i class="fa-solid fa-users"></i>
        <span>HRIS</span>
    </a>
</li>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/admin/components/sidebar/items/hris.blade.php ENDPATH**/ ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.dashboard.view')): ?>
<li class="sidebar-item <?php echo e(request()->routeIs('admin.dashboard') ? 'active' : ''); ?>">
    <a href="<?php echo e(route('admin.dashboard')); ?>" class="sidebar-link">
        <i class="fa-solid fa-house"></i>
        <span>Dashboard</span>
    </a>
</li>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/components/sidebar/items/dashboard.blade.php ENDPATH**/ ?>
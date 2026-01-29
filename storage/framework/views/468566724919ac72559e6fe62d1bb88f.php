<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.report.view')): ?>
<li class="sidebar-item <?php echo e(Str::contains(request()->path(), 'reports') ? 'active' : ''); ?>">
    <a href="<?php echo e(route('reports.index')); ?>" class="sidebar-link 
        <?php echo e(Str::contains(request()->path(), 'reports') ? '' : 'collapsed'); ?>">
        <i class="fa-regular fa-file"></i>
        <span>Reports</span>
    </a>
</li>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/components/sidebar/items/reports.blade.php ENDPATH**/ ?>
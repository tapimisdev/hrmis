<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.users.view')): ?>
<li class="sidebar-item <?php echo e(Str::contains(request()->path(), 'users') ? 'active' : ''); ?>">
    <a href="<?php echo e(route('users.index')); ?>" class="sidebar-link 
        <?php echo e(Str::contains(request()->path(), 'users') ? '' : 'collapsed'); ?>">
        <i class="fa-solid fa-user-group"></i>
        <span>Users</span>
    </a>
</li>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/components/sidebar/items/users.blade.php ENDPATH**/ ?>
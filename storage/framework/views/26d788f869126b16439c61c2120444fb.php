<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any([
    'hr.payroll_deductions.view'
])): ?>
<li class="sidebar-item <?php echo e(Str::contains(request()->path(), 'admin/modules') ? 'active' : ''); ?>">
    <a class="sidebar-link dropdown-toggle <?php echo e(Str::contains(request()->path(), 'modules') ? '' : 'collapsed'); ?>"
    data-bs-toggle="collapse" 
    data-bs-target="#modules"
    role="button" 
    aria-expanded="<?php echo e(Str::contains(request()->path(), 'modules') ? 'true' : 'false'); ?>" 
    aria-controls="modules">
        <i class="fa-solid fa-money-check-dollar"></i>
        <span>Deductions</span>
    </a>
    <div class="collapse collapsable <?php echo e(Str::contains(request()->path(), 'modules') ? 'show' : ''); ?>" 
        id="modules">
        <ul class="nested-list">
            <?php if(count(getSidebarModules()) === 0): ?>
                <li class="nested-item">
                    <div class="alert alert-danger p-0 p-2 text-center" role="alert">
                        No modules available
                    </div>
                </li>
            <?php else: ?>
                <?php $__currentLoopData = getSidebarModules(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="nested-item">
                        <a href="<?php echo e(route('modules.index', ['slug' => $module->slug])); ?>?tab=<?php echo e($module->tab_slug); ?>"
                        class="<?php echo e(request()->routeIs('modules.index') && 
                            request('slug') === $module->slug && 
                            request('tab') === $module->tab_slug ? 'active' : ''); ?>">
                            <i class="fa-solid fa-calculator"></i>
                            <span class="text-capitalize"><?php echo e($module->module_name); ?></span>
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </ul>
    </div>
</li>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/components/sidebar/items/deductions.blade.php ENDPATH**/ ?>
<?php
    $sections = [
        'earnings' => [
            'label' => 'Earnings', 
            'icon' => 'fa-solid fa-file-invoice-dollar', 
            'permission' => 'hr.payroll_earnings.view'
        ],
        'taxes' => [
            'label' => 'Taxes', 
            'icon' => 'fa-solid fa-file-invoice-dollar', 
            'permission' => 'hr.payroll_taxes.view'
        ],
    ];
    $currentPath = request()->path();
?>

<?php $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $config): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($config['permission'])): ?>
        <?php
            $modules = getPayrollComponents($type);

            // Check if current route belongs to this section
            $isActiveSection = Str::contains($currentPath, $type);

            // Check if any child module is active
            $isActiveChild = collect($modules)->contains(function($module) {
                return request()->routeIs('payroll-components.index') && request('slug') === $module->slug;
            });

            $isExpanded = $isActiveSection || $isActiveChild;
        ?>

        <li class="sidebar-item <?php echo e($isExpanded ? 'active' : ''); ?>">
            <a class="sidebar-link dropdown-toggle <?php echo e($isExpanded ? '' : 'collapsed'); ?>"
               data-bs-toggle="collapse"
               data-bs-target="#<?php echo e($type); ?>"
               role="button"
               aria-expanded="<?php echo e($isExpanded ? 'true' : 'false'); ?>"
               aria-controls="<?php echo e($type); ?>">
                <i class="<?php echo e($config['icon']); ?>"></i>
                <span><?php echo e($config['label']); ?></span>
            </a>

            <div class="collapse collapsable <?php echo e($isExpanded ? 'show' : ''); ?>" id="<?php echo e($type); ?>">
                <ul class="nested-list">
                    <?php if(count($modules) === 0): ?>
                        <li class="nested-item">
                            <div class="alert alert-danger p-0 p-2 text-center" role="alert">
                                No modules available
                            </div>
                        </li>
                    <?php else: ?>
                        <?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="nested-item">
                                <a href="<?php echo e(route('payroll-components.index', ['slug' => urlencode($module->slug)])); ?>"
                                    class="<?php echo e(request()->is('admin/payroll-components/' . $module->slug . '*') ? 'active' : ''); ?>">
                                    <i class="<?php echo e($module->icon); ?>"></i>
                                    <span class="text-capitalize"><?php echo e($module->name); ?></span>
                                </a>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                </ul>
            </div>
        </li>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH /var/www/html/resources/views/admin/components/sidebar/items/payroll-components.blade.php ENDPATH**/ ?>
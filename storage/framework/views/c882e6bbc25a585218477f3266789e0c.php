<div class="sidebar boder bg-body-secondary border-right">
    <!-- Logo Section -->
    <div class="sidebar-title">
        <img src="<?php echo e(asset('img/orbit.png')); ?>" alt="Orbit">
    </div>

    <!-- Navigation List -->
    <ul class="sidebar-list">
        
        
        <?php echo $__env->make('admin.components.sidebar.items.dashboard', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        
        <?php echo $__env->make('admin.components.sidebar.items.hris', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        
        <?php echo $__env->make('admin.components.sidebar.items.users', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        
        <?php echo $__env->make('admin.components.sidebar.items.timekeeping', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        
        <?php echo $__env->make('admin.components.sidebar.items.payroll', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        
        <?php echo $__env->make('admin.components.sidebar.items.payroll-components', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        
        <?php echo $__env->make('admin.components.sidebar.items.deductions', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        
        <?php echo $__env->make('admin.components.sidebar.items.service', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        
        <?php echo $__env->make('admin.components.sidebar.items.maintenance', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        
        <?php echo $__env->make('admin.components.sidebar.items.reports', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        
        <li class="sidebar-item d-lg-none">
            <a class="sidebar-link" 
               href="<?php echo e(route('logout')); ?>"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
            <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST" class="d-none">
                <?php echo csrf_field(); ?>
            </form>
        </li>
    </ul>
</div><?php /**PATH /var/www/html/resources/views/admin/components/sidebar/sidebar.blade.php ENDPATH**/ ?>
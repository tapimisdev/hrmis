<navbar class="d-flex justify-content-between align-items-center py-3 pb-4 mt-3">
    <div class="d-flex align-items-center gap-3">
        <img src="<?php echo e(asset('img/orbit_circle.png')); ?>" alt="DOST Logo" height="36" class="d-none d-md-block" id="header-con">
        <div>
            <h5 class="mb-0 text-light text-uppercase " style="letter-spacing: 3px;">
                <?php echo e($title ?? config('app.name')); ?>

            </h5>
        </div>
    </div>
    <?php echo e($slot); ?>

</navbar><?php /**PATH /var/www/html/resources/views/components/employee-navbar.blade.php ENDPATH**/ ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-4 mt-4">
    <div class="flex-grow-1">
        <h4 class="fw-bold text-uppercase mb-2">
            <?php echo e($title); ?>

        </h4>
        <?php if($subtitle): ?>
            <p class="mb-0" style="font-size: 16px; color: #6b7280; font-weight: 500;">
                <?php echo e($subtitle); ?>

            </p>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <?php echo e($slot); ?>

    </div>
</div>
<div class="mb-4" style="height: 2px; background: linear-gradient(to right, #e5e7eb 0%, #d1d5db 50%, #e5e7eb 100%);"></div><?php /**PATH /var/www/html/resources/views/components/header.blade.php ENDPATH**/ ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any([
    'hr.timekeeping.view', 
    'hr.timekeeping.import',
])): ?>
<li class="sidebar-item <?php echo e(Str::contains(request()->path(), 'timekeeping') ? 'active' : ''); ?>">
    <a class="sidebar-link dropdown-toggle <?php echo e(Str::contains(request()->path(), 'timekeeping') ? '' : 'collapsed'); ?>"
        data-bs-toggle="collapse" 
        data-bs-target="#timekeeping"
        role="button" 
        aria-expanded="<?php echo e(Str::contains(request()->path(), 'timekeeping') ? 'true' : 'false'); ?>" 
        aria-controls="timekeeping">
        <i class="fa-solid fa-clock"></i>
        <span>Timekeeping</span>
    </a>
    <div class="collapse collapsable <?php echo e(Str::contains(request()->path(), 'timekeeping') ? 'show' : ''); ?>" 
            id="timekeeping">
        <ul class="nested-list">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.timekeeping.view')): ?>
           <li class="nested-item">
                <a href="<?php echo e(route('timelogs.index')); ?>"
                class="<?php echo e(Route::is('timelogs.*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-clock"></i>
                    <span>Timelogs</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.correction.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('timelogs-correction.index')); ?>"
                class="<?php echo e(Route::is('timelogs-correction.*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-file-pen"></i>
                    <span>Correction Request</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.webtime.view')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('webtime.index')); ?>"
                class="<?php echo e(Route::is('webtime.*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-user-clock"></i>
                    <span>Web Time Access Control</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.timekeeping.import')): ?>
            <li class="nested-item">
                <a href="<?php echo e(route('import.timelogs.index')); ?>"
                    class="<?php echo e(request()->is('admin/timekeeping/upload-timelogs*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-file-import"></i>
                    <span>Import</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</li>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/components/sidebar/items/timekeeping.blade.php ENDPATH**/ ?>
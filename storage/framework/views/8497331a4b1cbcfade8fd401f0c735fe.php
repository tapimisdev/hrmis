<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any([
    'hr.salary_payroll.view'
])): ?>
<li class="sidebar-item <?php echo e(request()->segment(2) === 'payroll' ? 'active' : ''); ?>">
    <a class="sidebar-link dropdown-toggle <?php echo e(Str::contains(request()->path(), 'admin/payroll/') ? '' : 'collapsed'); ?>"
    data-bs-toggle="collapse" 
    data-bs-target="#payroll"
    role="button" 
    aria-expanded="<?php echo e(Str::contains(request()->path(), 'payroll') ? 'true' : 'false'); ?>" 
    aria-controls="payroll">
        <i class="fa-solid fa-money-check-dollar"></i>
        <span>Payroll</span>
    </a>
    <div class="collapse collapsable <?php echo e(Str::contains(request()->path(), 'admin/payroll/') ? 'show' : ''); ?>" 
        id="payroll">
        <ul class="nested-list">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('hr.salary_payroll.view')): ?>
                <li class="nested-item">
                    <a href="<?php echo e(route('salary-pay.index')); ?>"
                    class="<?php echo e(request()->is('admin/payroll/salary*') ? 'active' : ''); ?>">
                        <i class="fa-solid fa-money-bill-1-wave"></i>
                        <span>Salary</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="nested-item">
                <a href="<?php echo e(route('hazard-pay.index')); ?>"
                class="<?php echo e(request()->is('admin/payroll/hazard-pay*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-money-bill-1-wave"></i>
                    <span>Hazard Pay</span>
                </a>
            </li>

            <li class="nested-item">
                <a href="<?php echo e(route('sla-pay.index')); ?>"
                class="<?php echo e(request()->is('admin/payroll/sla-pay*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-money-bill-1-wave"></i>
                    <span>SLA Pay</span>
                </a>
            </li>

             <li class="nested-item">
                <a href="<?php echo e(route('pera-rata.index')); ?>"
                class="<?php echo e(request()->is('admin/payroll/pera-rata*') ? 'active' : ''); ?>">
                    <i class="fa-solid fa-money-bill-1-wave"></i>
                    <span>Pera & Rata</span>
                </a>
            </li>
        </ul>
    </div>
</li>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/components/sidebar/items/payroll.blade.php ENDPATH**/ ?>
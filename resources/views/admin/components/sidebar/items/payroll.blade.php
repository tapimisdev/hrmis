@canany([
    'hr.salary_payroll.view'
])
<li class="sidebar-item {{ request()->segment(2) === 'payroll' ? 'active' : '' }}">
    <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'payroll') ? '' : 'collapsed' }}"
    data-bs-toggle="collapse" 
    data-bs-target="#payroll"
    role="button" 
    aria-expanded="{{ Str::contains(request()->path(), 'payroll') ? 'true' : 'false' }}" 
    aria-controls="payroll">
        <i class="fa-solid fa-money-check-dollar"></i>
        <span>Payroll</span>
    </a>
    <div class="collapse collapsable {{ Str::contains(request()->path(), 'payroll') ? 'show' : '' }}" 
        id="payroll">
        <ul class="nested-list">
            @can('hr.salary_payroll.view')
                <li class="nested-item">
                    <a href="{{ route('salary.index') }}"
                    class="{{ request()->is('admin/payroll/salary*') ? 'active' : '' }}">
                        <i class="fa-solid fa-money-bill-1-wave"></i>
                        <span>Salary</span>
                    </a>
                </li>
            @endcan

            <li class="nested-item">
                <a href="{{ route('hazard-pay.index') }}"
                class="{{ request()->is('admin/payroll/hazard-pay*') ? 'active' : '' }}">
                    <i class="fa-solid fa-money-bill-1-wave"></i>
                    <span>Hazard Pay</span>
                </a>
            </li>

            <li class="nested-item">
                <a href="{{ route('sla-pay.index') }}"
                class="{{ request()->is('admin/payroll/sla-pay*') ? 'active' : '' }}">
                    <i class="fa-solid fa-money-bill-1-wave"></i>
                    <span>SLA Pay</span>
                </a>
            </li>
        </ul>
    </div>
</li>
@endcanany
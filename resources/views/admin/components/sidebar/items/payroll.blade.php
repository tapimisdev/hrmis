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
                    class="{{ request()->routeIs('salary.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-money-bill-1-wave"></i>
                        <span>Salary</span>
                    </a>
                </li>
            @endcan

            <li class="nested-item">
                <a href="{{ route('hazard-pay.index') }}"
                class="{{ request()->routeIs('hazard-pay.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-money-bill-1-wave"></i>
                    <span>Hazard Pay</span>
                </a>
            </li>
        </ul>
    </div>
</li>
@endcanany
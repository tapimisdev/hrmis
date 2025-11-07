@canany([
    'hr.salary_payroll.view'
])
<li class="sidebar-item {{ Str::contains(request()->path(), 'payroll') ? 'active' : '' }}">
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
                    <i class="fa-solid fa-calculator"></i>
                    <span>Salary</span>
                </a>
            </li>
            @endcan
        </ul>
    </div>
</li>
@endcanany
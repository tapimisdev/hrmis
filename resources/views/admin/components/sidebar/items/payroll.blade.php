@canany([
'hr.salary_payroll.view'
])
<li class="sidebar-item {{ request()->segment(2) === 'payroll' ? 'active' : '' }}">
    <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'admin/payroll/') ? '' : 'collapsed' }}"
        data-bs-toggle="collapse"
        data-bs-target="#payroll"
        role="button"
        aria-expanded="{{ Str::contains(request()->path(), 'payroll') ? 'true' : 'false' }}"
        aria-controls="payroll">
        <i class="fa-solid fa-money-check-dollar"></i>
        <span>Payroll</span>
    </a>
    <div class="collapse collapsable {{ Str::contains(request()->path(), 'admin/payroll/') ? 'show' : '' }}"
        id="payroll">
        <ul class="nested-list">

            @can('hr.salary_payroll.view')
            <li class="nested-item">
                <a href="{{ route('salary-pay.index') }}"
                    class="{{ request()->is('admin/payroll/salary*') ? 'active' : '' }}">
                    <i class="fa-solid fa-money-bill-1-wave"></i>
                    <span>Salary</span>
                </a>
            </li>
            @endcan

            <li class="nested-item">
                <a href="{{ route('hazard-pay.index') }}"
                    class="{{ request()->is('admin/payroll/hazard-pay*') ? 'active' : '' }}">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Hazard Pay</span>
                </a>
            </li>

            <li class="nested-item">
                <a href="{{ route('sla-pay.index') }}"
                    class="{{ request()->is('admin/payroll/sla-pay*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clock"></i>
                    <span>SLA Pay</span>
                </a>
            </li>

            <li class="nested-item">
                <a href="{{ route('pera-rata.index') }}"
                    class="{{ request()->is('admin/payroll/pera-rata*') ? 'active' : '' }}">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <span>Pera & Rata</span>
                </a>
            </li>

            <li class="nested-item">
                <a href="{{ route('longevity-pay.index') }}"
                    class="{{ request()->is('admin/payroll/longevity-pay*') ? 'active' : '' }}">
                    <i class="fa-solid fa-award"></i>
                    <span>Longevity</span>
                </a>
            </li>

            <li class="nested-item">
                <a href="{{ route('government-bonuses.index') }}"
                    class="{{ request()->is('admin/payroll/government-bonuses*') ? 'active' : '' }}">
                    <i class="fa-solid fa-landmark"></i>
                    <span>Government Bonuses</span>
                </a>
            </li>

            <li class="nested-item">
                <a href="{{ route('government-bonus-types.index') }}"
                    class="{{ request()->is('admin/payroll/government-bonus-types*') ? 'active' : '' }}">
                    <i class="fa-solid fa-list-check"></i>
                    <span>Government Bonus Rules</span>
                </a>
            </li>

            <hr class="mt-2 mb-2">

            <li class="nested-item">
                <a href="{{ route('payroll.group.index') }}"
                    class="{{ request()->is('admin/payroll/groups*') ? 'active' : '' }}">
                    <i class="fa-solid fa-people-group"></i>
                    <span>Group</span>
                </a>
            </li>

            <li class="nested-item">
                <a href="{{ route('registry.salary.index') }}"
                    class="{{ request()->is('admin/payroll/import/registry*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-import"></i>
                    <span>Import Registry</span>
                </a>
            </li>
        </ul>
    </div>
</li>
@endcanany

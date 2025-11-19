<li class="sidebar-item {{ Str::contains(request()->path(), 'payroll') ? 'active' : '' }}">
    <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'payroll') ? '' : 'collapsed' }}"
    data-bs-toggle="collapse" 
    data-bs-target="#taxes"
    role="button" 
    aria-expanded="{{ Str::contains(request()->path(), 'payroll') ? 'true' : 'false' }}" 
    aria-controls="taxes">
        <i class="fa-solid fa-file-invoice-dollar"></i>
        <span>Taxes</span>
    </a>
    <div class="collapse collapsable {{ Str::contains(request()->path(), 'payroll') ? 'show' : '' }}" 
        id="taxes">
        <ul class="nested-list">
            <li class="nested-item">
                <a href="{{ route('salary.index') }}"
                class="{{ request()->routeIs('tax.salary.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Tax Salary</span>
                </a>
            </li>
            <li class="nested-item">
                <a href="{{ route('salary.index') }}"
                class="{{ request()->routeIs('salary.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-lines"></i>
                    <span>HP Tax</span>
                </a>
            </li>
            <li class="nested-item">
                <a href="{{ route('salary.index') }}"
                class="{{ request()->routeIs('salary.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-contract"></i>
                    <span>Longe-tax</span>
                </a>
            </li>
        </ul>
    </div>
</li>
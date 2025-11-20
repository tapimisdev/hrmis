<li class="sidebar-item {{ Str::contains(request()->path(), 'admin/deductions') ? 'active' : '' }}">
    <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'payroll') ? '' : 'collapsed' }}"
    data-bs-toggle="collapse" 
    data-bs-target="#taxes"
    role="button" 
    aria-expanded="{{ Str::contains(request()->path(), 'payroll') ? 'true' : 'false' }}" 
    aria-controls="taxes">
        <i class="fa-solid fa-file-invoice-dollar"></i>
        <span>Deductions</span>
    </a>
    <div class="collapse collapsable {{ Str::contains(request()->path(), 'payroll') ? 'show' : '' }}" 
        id="taxes">
        <ul class="nested-list">
            <li class="nested-item">
                <a href="{{ route('tax.salary.index') }}"
                class="{{ request()->routeIs('tax.salary.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-percent"></i>
                    <span>Salary Tax</span>
                </a>
            </li>
            <li class="nested-item">
                <a href="{{ route('salary.index') }}"
                class="{{ request()->routeIs('salary.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-home"></i>
                    <span>HP Tax</span>
                </a>
            </li>
            <li class="nested-item">
                <a href="{{ route('salary.index') }}"
                class="{{ request()->routeIs('salary.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-clock"></i>
                    <span>Longe-tax</span>
                </a>
            </li>
            <li class="nested-item">
                <a href="{{ route('salary.index') }}"
                class="{{ request()->routeIs('salary.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                    <span>Pagibig</span>
                </a>
            </li>
            <li class="nested-item">
                <a href="{{ route('salary.index') }}"
                class="{{ request()->routeIs('salary.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-heart-pulse"></i>
                    <span>Philhealth</span>
                </a>
            </li>
            <li class="nested-item">
                <a href="{{ route('salary.index') }}"
                class="{{ request()->routeIs('salary.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-shield"></i>
                    <span>GSIS</span>
                </a>
            </li>
            <li class="nested-item">
                <a href="{{ route('salary.index') }}"
                class="{{ request()->routeIs('salary.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-university"></i>
                    <span>Landbank</span>
                </a>
            </li>
        </ul>
    </div>
</li>
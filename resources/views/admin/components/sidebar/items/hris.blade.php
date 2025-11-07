@canany([
    'hr.hris.view'
])
<li class="sidebar-item {{ Str::contains(request()->path(), 'hris') ? 'active' : '' }}">
    <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'hris') ? '' : 'collapsed' }}"
        data-bs-toggle="collapse" 
        data-bs-target="#hris"
        role="button" 
        aria-expanded="{{ Str::contains(request()->path(), 'hris') ? 'true' : 'false' }}" 
        aria-controls="hris">
        <i class="fa-solid fa-users"></i>
        <span>HRIS</span>
    </a>
    <div class="collapse collapsable {{ Str::contains(request()->path(), 'hris') ? 'show' : '' }}" 
            id="hris">
        <ul class="nested-list">
            @can('hr.hris.view')
            <li class="nested-item">
                <a href="{{ route('hris.employee.index') }}" 
                    class="{{ request()->routeIs('hris.employee.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-group"></i>
                    <span>Employee List</span>
                </a>
            </li>
            @endcan
        </ul>
    </div>
</li>
@endcanany
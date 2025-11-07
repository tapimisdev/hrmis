<li class="sidebar-item {{ Str::contains(request()->path(), 'reports') ? 'active' : '' }}">
    <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'reports') ? '' : 'collapsed' }}"
        data-bs-toggle="collapse" 
        data-bs-target="#reports"
        role="button" 
        aria-expanded="{{ Str::contains(request()->path(), 'reports') ? 'true' : 'false' }}" 
        aria-controls="reports">
        <i class="fa-solid fa-briefcase"></i>
        <span>Reports</span>
    </a>
    <div class="collapse collapsable {{ Str::contains(request()->path(), 'reports') ? 'show' : '' }}" 
            id="reports">
        <ul class="nested-list">
            <li class="nested-item">
                <a href=""
                    class="{{ request()->routeIs('reports.events.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Regular</span>
                </a>
            </li>
            <li class="nested-item">
                <a href=""
                    class="{{ request()->routeIs('reports.events.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>COS</span>
                </a>
            </li>
        </ul>
    </div>
</li>
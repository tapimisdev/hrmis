<li class="sidebar-item {{ Str::contains(request()->path(), 'reports') ? 'active' : '' }}">
    <a href="{{ route('reports.index') }}" class="sidebar-link 
        {{ Str::contains(request()->path(), 'reports') ? '' : 'collapsed' }}">
        <i class="fa-solid fa-briefcase"></i>
        <span>Reports</span>
    </a>
</li>
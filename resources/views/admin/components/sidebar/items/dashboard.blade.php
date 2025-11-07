@can('hr.dashboard.view')
<li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
    <a href="{{ route('admin.dashboard') }}" class="sidebar-link">
        <i class="fa-solid fa-house"></i>
        <span>Dashboard</span>
    </a>
</li>
@endcan
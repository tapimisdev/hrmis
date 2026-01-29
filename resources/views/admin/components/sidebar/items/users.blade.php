@can('hr.users.view')
<li class="sidebar-item {{ Str::contains(request()->path(), 'users') ? 'active' : '' }}">
    <a href="{{ route('users.index') }}" class="sidebar-link 
        {{ Str::contains(request()->path(), 'users') ? '' : 'collapsed' }}">
        <i class="fa-solid fa-user-group"></i>
        <span>Users</span>
    </a>
</li>
@endcan
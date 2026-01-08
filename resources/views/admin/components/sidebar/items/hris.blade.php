@can('hr.hris.view')
<li class="sidebar-item {{ Str::contains(request()->path(), 'hris') ? 'active' : '' }}">
    <a href="{{ route('hris.employee.index') }}" class="sidebar-link {{ Str::contains(request()->path(), 'hris') ? '' : 'collapsed' }}">
        <i class="fa-solid fa-users"></i>
        <span>HRIS</span>
    </a>
</li>
@endcan

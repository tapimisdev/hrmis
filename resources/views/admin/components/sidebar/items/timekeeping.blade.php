@canany([
    'hr.timekeeping.view', 
    'hr.timekeeping.import',
])
<li class="sidebar-item {{ Str::contains(request()->path(), 'timekeeping') ? 'active' : '' }}">
    <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'timekeeping') ? '' : 'collapsed' }}"
        data-bs-toggle="collapse" 
        data-bs-target="#timekeeping"
        role="button" 
        aria-expanded="{{ Str::contains(request()->path(), 'timekeeping') ? 'true' : 'false' }}" 
        aria-controls="timekeeping">
        <i class="fa-solid fa-clock"></i>
        <span>Timekeeping</span>
    </a>
    <div class="collapse collapsable {{ Str::contains(request()->path(), 'timekeeping') ? 'show' : '' }}" 
            id="timekeeping">
        <ul class="nested-list">
            @can('hr.timekeeping.view')
            <li class="nested-item">
                <a href="{{ route('timelogs.index') }}"
                    class="{{ request()->is('admin/timekeeping/timelogs*') || request()->is('admin/timekeeping/daily-time-record*') ? 'active' : '' }}">
                    <i class="fa-solid fa-stopwatch"></i>
                    <span>Timelogs</span>
                </a>
            </li>
            @endcan
            @can('hr.timekeeping.import')
            <li class="nested-item">
                <a href="{{ route('import.timelogs.index') }}"
                    class="{{ request()->is('admin/timekeeping/upload-timelogs*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-import"></i>
                    <span>Import</span>
                </a>
            </li>
            @endcan
        </ul>
    </div>
</li>
@endif
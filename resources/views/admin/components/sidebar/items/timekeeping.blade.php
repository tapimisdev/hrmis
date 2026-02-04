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
                <a href="{{ route('timelogs-statistics') }}"
                class="{{ Route::is('timelogs-statistics.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clock"></i>
                    <span>Statistics</span>
                </a>
            </li>
            @endcan
            @can('hr.timekeeping.view')
           <li class="nested-item">
                <a href="{{ route('timelogs.index') }}"
                class="{{ Route::is('timelogs.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clock"></i>
                    <span>Timelogs</span>
                </a>
            </li>
            @endcan

            @can('hr.correction.view')
            <li class="nested-item">
                <a href="{{ route('timelogs-correction.index') }}"
                class="{{ Route::is('timelogs-correction.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-pen"></i>
                    <span>Correction Request</span>
                </a>
            </li>
            @endcan

            @can('hr.webtime.view')
            <li class="nested-item">
                <a href="{{ route('webtime.index') }}"
                class="{{ Route::is('webtime.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-clock"></i>
                    <span>Web Time Access Control</span>
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
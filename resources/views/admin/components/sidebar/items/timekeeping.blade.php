@canany([
    'hr.timekeeping.view', 
    'hr.timekeeping.import',
    'hr.timelog-verification.view',
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
                    <i class="fa-solid fa-chart-area"></i>
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

            @can('hr.timelog-verification.view')
           <li class="nested-item">
                <a href="{{ route('timekeeping.timelog-verification.index') }}"
                class="{{ Route::is('timekeeping.timelog-verification.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-list-check"></i>
                    <span>Timelog Verification</span>
                </a>
            </li>
            @endcan

            @can('hr.timekeeping.view')
           <li class="nested-item">
                <a href="{{ route('timekeeping.monitoring.index') }}"
                class="{{ Route::is('timekeeping.monitoring.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-display"></i>
                    <span>Monitoring</span>
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

            @can('hr.timekeeping.view')
            <li class="nested-item">
                <a href="{{ route('timekeeping.behavioral-notices.index') }}"
                class="{{ Route::is('timekeeping.behavioral-notices.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span>Behavioral Notices</span>
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

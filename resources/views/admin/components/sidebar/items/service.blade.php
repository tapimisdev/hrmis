@canany([
    'hr.events_and_announcements.view', 
    'hr.leave_approval.view', 
    'hr.pass_slip_approval.view', 
    'hr.overtime_approval.view',
    'hr.special_order_approval.view',
    'hr.lto_approval.view'
])
<li class="sidebar-item {{ Str::contains(request()->path(), 'service') ? 'active' : '' }}">
    <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'services') ? '' : 'collapsed' }}"
        data-bs-toggle="collapse" 
        data-bs-target="#services"
        role="button" 
        aria-expanded="{{ Str::contains(request()->path(), 'services') ? 'true' : 'false' }}" 
        aria-controls="services">
        <i class="fa-solid fa-briefcase"></i>
        <span>Services</span>
    </a>
    <div class="collapse collapsable {{ Str::contains(request()->path(), 'services') ? 'show' : '' }}" 
            id="services">
        <ul class="nested-list">

            <li class="nested-item">
                <a href="{{ route('services.credits') }}"
                    class="{{ request()->is('admin/service/credits*') ? 'active' : '' }}">
                    <i class="fa-solid fa-credit-card"></i>
                    <span>Credits</span>
                </a>
            </li>

            @can('hr.events_and_announcements.view')
            <li class="nested-item">
                <a href="{{ route('services.events.index') }}"
                    class="{{ request()->is('admin/service/events*') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Events & <br> Announcements</span>
                </a>
            </li>
            @endcan

            @can('hr.suspensions.view')
            <li class="nested-item">
                <a href="{{ route('services.suspensions.index') }}"
                    class="{{ request()->is('admin/service/suspensions*') ? 'active' : '' }}">
                    <i class="fa-solid fa-cloud-rain"></i>
                    <span>Suspensions</span>
                </a>
            </li>
            @endcan

            @can('hr.overtime_approval.view')
            <li class="nested-item">
                <a href="{{ route('services.overtime.index') }}"
                    class="{{ request()->is('admin/service/overtime*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clock"></i>
                    <span>Overtime Application</span>
                </a>
            </li>
            @endcan

            @can('hr.leave_approval.view')
            <li class="nested-item">
                <a href="{{ route('services.leaves.index') }}"
                    class="{{ request()->is('admin/service/leave*') ? 'active' : '' }}">
                    <i class="fa-solid fa-plane-departure"></i>
                    <span>Leave Application</span>
                </a>
            </li>
            @endcan

            @can('hr.offset_approval.view')
                <li class="nested-item">
                    <a href="{{ route('services.offset.index') }}"
                        class="{{ request()->is('admin/service/offset*') ? 'active' : '' }}">
                        <i class="fa-solid fa-ghost"></i>
                        <span>Offset Application</span>
                    </a>
                </li>
            @endcan

            @can('hr.pass_slip_approval.view')
            <li class="nested-item">
                <a href="{{ route('services.pass_slip.index') }}"
                    class="{{ request()->is('admin/service/pass-slip*') ? 'active' : '' }}">
                    <i class="fa-solid fa-torii-gate"></i>
                    <span>Pass Slip Application</span>
                </a>
            </li> 
            @endcan

            @can('hr.special_order_approval.view')
            <li class="nested-item">
                <a href="{{ route('services.special_order.index') }}"
                    class="{{ request()->is('admin/service/special-order*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-signature"></i>
                    <span>Special Order Application</span>
                </a>
            </li>
            @endcan

            @can('hr.lto_approval.view')
            <li class="nested-item">
                <a href="{{ route('services.lto.index') }}"
                    class="{{ request()->is('admin/service/local-travel-order*') ? 'active' : '' }}">
                    <i class="fa-solid fa-route"></i>
                    <span>Local Travel Order Application</span>
                </a>
            </li>
            @endcan

        </ul>
    </div>
</li>
@endcanany

<div class="sidebar boder bg-body-secondary border-right">
    <!-- Logo Section -->
    <div class="sidebar-title">
        <img src="{{ asset('img/orbit.png') }}" alt="Orbit">
    </div>

    <!-- Navigation List -->
    <ul class="sidebar-list">
        
        {{-- Dashboard --}}
        @include('admin.components.sidebar.items.dashboard')

        {{-- HRIS --}}
        @include('admin.components.sidebar.items.hris')

        {{-- Timekeeping --}}
        @include('admin.components.sidebar.items.timekeeping')

        {{-- Payroll --}}
        @include('admin.components.sidebar.items.payroll')

        {{-- Services --}}
        @include('admin.components.sidebar.items.service')

        {{-- Reports --}}
        @include('admin.components.sidebar.items.reports')

        {{-- Deductions --}}
        @include('admin.components.sidebar.items.deductions')

        {{-- Modules --}}
        @include('admin.components.sidebar.items.modules')

        {{-- Maintenance --}}
        @include('admin.components.sidebar.items.maintenance')

        {{-- Logout (Mobile Only) --}}
        <li class="sidebar-item d-lg-none">
            <a class="sidebar-link" 
               href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </li>
    </ul>
</div>
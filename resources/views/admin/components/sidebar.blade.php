<aside class="sidebar shadow-lg border-1">
    <div class="sidebar-title">
        <img src="{{ asset('img/dost-tapi.png') }}" alt="logo">
        <div class="title">HR and Payroll System</div>
    </div>
    <ul class="sidebar-list">
        <!-- Dashboard -->
        <li class="sidebar-item mt-1 {{ request()->routeIs('home') ? 'active' : '' }}">
            <a href="{{ route('home') }}" class="sidebar-link pe-5">
                <i class="fa-solid fa-house me-2"></i> Dashboard
            </a>
        </li>

        <li class="sidebar-item {{ request()->is('financials*') ? 'active' : '' }}">
            <a class="sidebar-link collapse-link dropdown-toggle " data-bs-toggle="collapse" href="#financials" role="button" aria-expanded="false" aria-controls="purchases">
                <i class="fa-solid fa-coins"></i> HRIS
            </a>
            <div class="collapse collapsable" id="financials">
                <ul class="nested-list">
                    <li class="nested-item p-2">
                        <a href="" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-chart-simple"></i>
                            Employee List
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        
        <li class="sidebar-item {{ request()->is('financials*') ? 'active' : '' }}">
            <a class="sidebar-link collapse-link dropdown-toggle " data-bs-toggle="collapse" href="#services" role="button" aria-expanded="false" aria-controls="services">
                <i class="fa-solid fa-coins"></i> Services
            </a>
            <div class="collapse collapsable" id="services">
                <ul class="nested-list">
                    <li class="nested-item p-2">
                        <a href="" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-chart-simple"></i>
                            Employee List
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="sidebar-item {{ request()->is('financials*') ? 'active' : '' }}">
            <a class="sidebar-link collapse-link dropdown-toggle " data-bs-toggle="collapse" href="#reports" role="button" aria-expanded="false" aria-controls="purchases">
                <i class="fa-solid fa-coins"></i> REPORTS
            </a>
            <div class="collapse collapsable" id="reports">
                <ul class="nested-list">
                    <li class="nested-item p-2">
                        <a href="" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-chart-simple"></i>
                            Employee List
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="sidebar-item {{ request()->is('financials*') ? 'active' : '' }}">
            <a class="sidebar-link collapse-link dropdown-toggle " data-bs-toggle="collapse" href="#settings" role="button" aria-expanded="false" aria-controls="purchases">
                <i class="fa-solid fa-coins"></i> Settings
            </a>
            <div class="collapse collapsable" id="settings">
                <ul class="nested-list">
                    <li class="nested-item p-2">
                        <a href="" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-chart-simple"></i>
                            Employee List
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    </ul>
</aside>

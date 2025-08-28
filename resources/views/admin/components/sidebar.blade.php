<aside class="sidebar border-1">
    <div class="sidebar-title">
        <img src="{{ asset('img/DOST-TAPI.png') }}" alt="logo">
        <div class="title">HR and Payroll System</div>
    </div>

    <ul class="sidebar-list">

        <!-- Dashboard -->
        <li class="sidebar-item mt-1 {{ request()->routeIs('home') ? 'active' : '' }}">
            <a href="{{ route('home') }}" class="sidebar-link pe-5">
                <i class="fa-solid fa-house me-2"></i> Dashboard
            </a>
        </li>

        <!-- HRIS -->
        <li class="sidebar-item {{ request()->is('hris*') ? 'active' : '' }}">
            <a class="sidebar-link collapse-link dropdown-toggle" 
               data-bs-toggle="collapse" 
               data-bs-target="#hris" 
               role="button" 
               aria-expanded="false" 
               aria-controls="hris">
                <i class="fa-solid fa-coins"></i> HRIS
            </a>
            <div class="collapse collapsable" id="hris" data-bs-parent=".sidebar-list">
                <ul class="nested-list">
                    <li class="nested-item p-2">
                        <a href="{{ route('hris.employee.index') }}" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-chart-simple"></i> Employee List
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Services -->
        <li class="sidebar-item {{ request()->is('services*') ? 'active' : '' }}">
            <a class="sidebar-link collapse-link dropdown-toggle" 
               data-bs-toggle="collapse" 
               data-bs-target="#services" 
               role="button" 
               aria-expanded="false" 
               aria-controls="services">
                <i class="fa-solid fa-briefcase"></i> Services
            </a>
            <div class="collapse collapsable" id="services" data-bs-parent=".sidebar-list">
                <ul class="nested-list">
                    <li class="nested-item p-2">
                        <a href="#" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-chart-simple"></i> Service Item
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Reports -->
        <li class="sidebar-item {{ request()->is('reports*') ? 'active' : '' }}">
            <a class="sidebar-link collapse-link dropdown-toggle" 
               data-bs-toggle="collapse" 
               data-bs-target="#reports" 
               role="button" 
               aria-expanded="false" 
               aria-controls="reports">
                <i class="fa-solid fa-file-alt"></i> Reports
            </a>
            <div class="collapse collapsable" id="reports" data-bs-parent=".sidebar-list">
                <ul class="nested-list">
                    <li class="nested-item p-2">
                        <a href="#" class="d-flex gap-2 align-items-center">
                            <i class="fa-solid fa-chart-simple"></i> Report Item
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <!-- Settings -->
        <li class="sidebar-item {{ request()->is('settings*') ? 'active' : '' }}">
            <a class="sidebar-link collapse-link dropdown-toggle" 
               data-bs-toggle="collapse" 
               data-bs-target="#settings" 
               role="button" 
               aria-expanded="false" 
               aria-controls="settings">
                <i class="fa-solid fa-gear"></i> Settings
            </a>
            <div class="collapse collapsable" id="settings" data-bs-parent=".sidebar-list">
                <ul class="nested-list">
                    <li class="nested-item p-2">
                        <a href="{{ route('organization.index', ['tab' => 'agency']) }}" class="d-flex gap-2 align-items-center">
                            Organization
                        </a>
                    </li>
                    <li class="nested-item p-2">
                        <a href="{{ route('employment-types.index') }}" class="d-flex gap-2 align-items-center">
                            Employment Types
                        </a>
                    </li>
                    <li class="nested-item p-2">
                        <a href="{{ route('positions.index') }}" class="d-flex gap-2 align-items-center">
                            Positions
                        </a>
                    </li>
                    <li class="nested-item p-2">
                        <a href="{{ route('role-and-permission.index') }}" class="d-flex gap-2 align-items-center">
                            Roles & Permissions
                        </a>
                    </li>
                    <li class="nested-item p-2">
                        <a href="{{ route('shift.index') }}" class="d-flex gap-2 align-items-center">
                            Shifts
                        </a>
                    </li>
                    <li class="nested-item p-2">
                        <a href="{{ route('role-and-permission.index') }}" class="d-flex gap-2 align-items-center">
                            Weekly Schedules
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    </ul>
</aside>

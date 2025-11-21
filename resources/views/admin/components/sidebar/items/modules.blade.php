@canany([
    'hr.salary_payroll.view'
])
<li class="sidebar-item {{ Str::contains(request()->path(), 'admin/modules') ? 'active' : '' }}">
    <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'modules') ? '' : 'collapsed' }}"
    data-bs-toggle="collapse" 
    data-bs-target="#modules"
    role="button" 
    aria-expanded="{{ Str::contains(request()->path(), 'modules') ? 'true' : 'false' }}" 
    aria-controls="modules">
        <i class="fa-solid fa-money-check-dollar"></i>
        <span>Modules</span>
    </a>
    <div class="collapse collapsable {{ Str::contains(request()->path(), 'modules') ? 'show' : '' }}" 
        id="modules">
        <ul class="nested-list">
            @if (count(getSidebarModules()) === 0)
                <li class="nested-item">
                    <div class="alert alert-danger p-0 p-2" role="alert">
                        No modules available
                    </div>
                </li>
            @else
                @foreach (getSidebarModules() as $module)
                    <li class="nested-item">
                        <a href="{{ route('modules.index', ['slug' => $module->slug]) }}?tab={{ $module->tab_slug }}"
                        class="{{ 
                            request()->routeIs('modules.index') && 
                            request('slug') === $module->slug && 
                            request('tab') === $module->tab_slug ? 'active' : '' 
                        }}">
                            <i class="fa-solid fa-calculator"></i>
                            <span class="text-capitalize">{{ $module->module_name }}</span>
                        </a>
                    </li>
                @endforeach
            @endif
        </ul>
    </div>
</li>
@endcanany
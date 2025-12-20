@php
    $sections = [
        'earnings' => [
            'label' => 'Earnings', 
            'icon' => 'fa-solid fa-file-invoice-dollar', 
            'permission' => 'hr.payroll_earnings.view'
        ],
        'taxes' => [
            'label' => 'Taxes', 
            'icon' => 'fa-solid fa-file-invoice-dollar', 
            'permission' => 'hr.payroll_taxes.view'
        ],
    ];
    $currentPath = request()->path();
@endphp

@foreach ($sections as $type => $config)
    @can($config['permission'])
        @php
            $modules = getPayrollComponents($type);

            // Check if current route belongs to this section
            $isActiveSection = Str::contains($currentPath, $type);

            // Check if any child module is active
            $isActiveChild = collect($modules)->contains(function($module) {
                return request()->routeIs('payroll-components.index') && request('slug') === $module->slug;
            });

            $isExpanded = $isActiveSection || $isActiveChild;
        @endphp

        <li class="sidebar-item {{ $isExpanded ? 'active' : '' }}">
            <a class="sidebar-link dropdown-toggle {{ $isExpanded ? '' : 'collapsed' }}"
               data-bs-toggle="collapse"
               data-bs-target="#{{ $type }}"
               role="button"
               aria-expanded="{{ $isExpanded ? 'true' : 'false' }}"
               aria-controls="{{ $type }}">
                <i class="{{ $config['icon'] }}"></i>
                <span>{{ $config['label'] }}</span>
            </a>

            <div class="collapse collapsable {{ $isExpanded ? 'show' : '' }}" id="{{ $type }}">
                <ul class="nested-list">
                    @if (count($modules) === 0)
                        <li class="nested-item">
                            <div class="alert alert-danger p-0 p-2 text-center" role="alert">
                                No modules available
                            </div>
                        </li>
                    @else
                        @foreach ($modules as $module)
                            <li class="nested-item">
                                <a href="{{ route('payroll-components.index', ['slug' => $module->slug]) }}"
                                   class="{{ request()->routeIs('payroll-components.index') && request('slug') === $module->slug ? 'active' : '' }}">
                                    <i class="{{ $module->icon }}"></i>
                                    <span class="text-capitalize">{{ $module->name }}</span>
                                </a>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </li>
    @endcan
@endforeach

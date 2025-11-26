@php
    $sections = [
        'earnings' => ['label' => 'Earnings', 'icon' => 'fa-solid fa-file-invoice-dollar'],
        'taxes' => ['label' => 'Taxes', 'icon' => 'fa-solid fa-file-invoice-dollar'],
    ];
    $currentPath = request()->path();
@endphp

@foreach ($sections as $type => $config)
    @php
        $modules = getPayrollComponents($type);
        $isActive = Str::contains($currentPath, $type);
    @endphp

    <li class="sidebar-item {{ $isActive ? 'active' : '' }}">
        <a class="sidebar-link dropdown-toggle {{ $isActive ? '' : 'collapsed' }}"
           data-bs-toggle="collapse"
           data-bs-target="#{{ $type }}"
           role="button"
           aria-expanded="{{ $isActive ? 'true' : 'false' }}"
           aria-controls="{{ $type }}">
            <i class="{{ $config['icon'] }}"></i>
            <span>{{ $config['label'] }}</span>
        </a>

        <div class="collapse collapsable {{ $isActive ? 'show' : '' }}" id="{{ $type }}">
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
                            <a href="{{ route('tax.index', ['slug' => $module->slug]) }}"
                               class="{{ request()->routeIs('tax.index') && request('slug') === $module->slug ? 'active' : '' }}">
                                <i class="{{ $module->icon }}"></i>
                                <span class="text-capitalize">{{ $module->name }}</span>
                            </a>
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>
    </li>
@endforeach

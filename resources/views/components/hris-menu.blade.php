<ul class="nav nav-pills mb-3 d-block pb-4 sticky-top z-3" style="top: 80px;">
    @foreach ($menus as $menu)

        @if (isset($menu['submenus']))
            <li class="nav-item dropdown-center" role="presentation">

                <a 
                    class="px-5 py-2 text-uppercase fw-bold nav-link dropdown-toggle {{ $menu['active'] }} nav-ellipsis"
                    data-bs-toggle="dropdown"
                    href="#"
                    role="button"
                    title="{{ $menu['name'] }}"
                >
                    {{ Str::limit($menu['name'], 60, '...') }}
                </a>
                <ul class="dropdown-menu">
                    @foreach ($menu['submenus'] as $group => $items)
                        @if(is_array($items) && isset($items[0]))
                            <li>
                                <h6 class="dropdown-header text-uppercase">{{ $group }}</h6>
                            </li>
                            @foreach ($items as $submenu)
                                <li>
                                    <a class="dropdown-item {{ $submenu['active'] ?? '' }}" 
                                    href="{{ $submenu['route'] }}" 
                                    title="{{ $submenu['name'] }}">
                                        {{ $submenu['name'] }}
                                    </a>
                                </li>
                            @endforeach
                            @if($loop->odd)
                                <li><hr class="dropdown-divider"></li>
                            @endif
                        @else
                            <li>
                                <a class="dropdown-item {{ $items['active'] ?? '' }}" 
                                href="{{ $items['route'] }}" 
                                title="{{ $items['name'] }}">
                                    {{ $items['name'] }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>

        @else
            <li class="nav-item" role="presentation">
                <a href="{{ $menu['route'] }}" 
                   class="px-5 py-2 text-uppercase fw-bold nav-link {{ $menu['active'] }} nav-ellipsis" 
                   title="{{ $menu['name'] }}">
                    {{ Str::limit($menu['name'], 60, '...') }}
                </a>
            </li>
        @endif

    @endforeach
</ul>

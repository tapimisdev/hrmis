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
                    @foreach ($menu['submenus'] as $submenu)
                        <li>
                            <a 
                                class="dropdown-item {{ $submenu['active'] ?? '' }}" 
                                href="{{ $submenu['route'] }}"
                                title="{{ $submenu['name'] }}"
                            >
                                {{ $submenu['name'] }}
                            </a>
                        </li>
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

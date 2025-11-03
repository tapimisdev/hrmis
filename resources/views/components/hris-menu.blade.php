<ul class="nav nav-pills mb-3 d-block pb-4 sticky-top z-3" style="top: 80px;" id="pills-tab" role="tablist">
    @foreach ($menus as $menu)
        <li class="nav-item" role="presentation">
            <a href="{{ $menu['route'] }}" 
               class="px-5 py-2 text-uppercase fw-bold nav-link {{ $menu['active'] }} nav-ellipsis" 
               title="{{ $menu['name'] }}">
                {{ \Illuminate\Support\Str::limit($menu['name'], 60, '...') }}
            </a>
        </li>
    @endforeach
</ul>

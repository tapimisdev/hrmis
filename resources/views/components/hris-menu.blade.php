<ul class="nav nav-pills mb-3 d-flex justify-content-center gap-3 pb-4" id="pills-tab" role="tablist">
    @foreach ($menus as $menu)
        <li class="nav-item" role="presentation">
            <a href="{{$menu['route']}}" class="px-4 py-2 text-uppercase fw-bold nav-link {{$menu['active']}}">{{$menu['name']}}</a>
        </li>
    @endforeach
</ul>
<hr class="mt-4 mb-5">

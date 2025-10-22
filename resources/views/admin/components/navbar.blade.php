<nav class="navbar admin-nav navbar-expand-md navbar-light sticky-top" style="height: 50px">
    <div class="container d-flex justify-content-md-between">
        <div></div>
        <div class="dropdown">
            <a class="nav-link text-white text-capitalize d-flex align-items-center gap-2" href="" role="button">
                <img class="mini-logo" src="{{ asset('img/dost-tapi.png') }}" alt="">

                {{ config('app.client') }}
            </a>
        </div>

         <ul class="navbar-nav ms-auto">
            @guest
                @if (Route::has('login'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                    </li>
                @endif
            @else
                <li class="nav-item d-none d-lg-block dropdown">
                    <a id="navbarDropdown" class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                        {{ Auth::user()->name }}
                    </a>

                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-modern" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="{{ route('logout') }}"
                            onclick="event.preventDefault();
                                            document.getElementById('logout-form').submit();">
                            <i class="fa-solid fa-right-from-bracket"></i> {{ __('Logout') }}
                        </a>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </li>
            @endguest
        </ul>

        <!-- Hamburger Links -->
        <div class="hamburger d-lg-none">
            <input class="checkbox" type="checkbox" id="toggleSidebar" />
            <div class="hamburger-lines">
                <span class="line line1 bg-light"></span>
                <span class="line line2 bg-light"></span>
                <span class="line line3 bg-light"></span>
            </div>
        </div>
    </div>
</nav>

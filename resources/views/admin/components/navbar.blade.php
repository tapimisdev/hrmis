<nav class="navbar navbar-expand-md navbar-dark bg-primary sticky-top shadow" style="height: 50px">
    <div class="container d-flex justify-content-md-between">
        <div></div>
        <div class="dropdown d-none d-lg-block">
            <a class="nav-link text-white text-capitalize" href="" role="button">
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
                <li class="nav-item dropdown">
                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                        {{ Auth::user()->name }}
                    </a>

                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="{{ route('logout') }}"
                            onclick="event.preventDefault();
                                            document.getElementById('logout-form').submit();">
                            {{ __('Logout') }}
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

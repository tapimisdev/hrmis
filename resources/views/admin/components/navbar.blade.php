<nav class="navbar admin-nav navbar-expand-md bg-body-secondary navbar-light border-bottom  sticky-top" style="height: 50px">
    <div class="container-fluid d-flex justify-content-md-between">
        <div></div>
        <div class="dropdown">
            <a class="nav-link text-capitalize d-flex align-items-center gap-2" href="" role="button">
                <img class="mini-logo" src="{{ asset('img/dost-tapi.png') }}" alt="">

                {{ config('app.client') }}
            </a>
        </div>

         <ul class="navbar-nav ms-auto">
            <li>
                <div class="toggle-container">
                    <button class="theme-toggle" id="theme-toggle" title="Toggles light & dark" aria-label="light" aria-live="polite">
                        <div class="toggle-icon sun">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="5" fill="#FFD700" stroke="#FFD700"/>
                                <line x1="12" y1="1" x2="12" y2="3" stroke="#FFD700"/>
                                <line x1="12" y1="21" x2="12" y2="23" stroke="#FFD700"/>
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" stroke="#FFD700"/>
                                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" stroke="#FFD700"/>
                                <line x1="1" y1="12" x2="3" y2="12" stroke="#FFD700"/>
                                <line x1="21" y1="12" x2="23" y2="12" stroke="#FFD700"/>
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" stroke="#FFD700"/>
                                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" stroke="#FFD700"/>
                            </svg>
                        </div>
                        <div class="toggle-icon moon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" fill="#93C5FD" stroke="#93C5FD"/>
                            </svg>
                        </div>
                    </button>
                    <div class="tooltip">
                        <span class="tooltip-text"></span>
                    </div>
                </div>
            </li>
            <li class="nav-item d-none d-lg-block dropdown">
                <a id="navbarDropdown" class="nav-link dropdown-toggle " href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
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
        </ul>

        <!-- Hamburger Links -->
        <div class="hamburger d-lg-none">
            <input class="checkbox" type="checkbox" id="toggleSidebar" />
            <div class="hamburger-lines">
                <span class="line line1 "></span>
                <span class="line line2 "></span>
                <span class="line line3 "></span>
            </div>
        </div>
    </div>
</nav>

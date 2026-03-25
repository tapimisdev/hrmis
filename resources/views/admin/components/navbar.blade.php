<nav class="navbar admin-nav navbar-expand-md bg-body-secondary navbar-light border-bottom  sticky-top">
    <div class="container-fluid d-flex justify-content-md-between">
        <div class="dropdown">
            <!-- <a class="nav-link text-capitalize d-flex align-items-center gap-3" style="font-size: 14px;" href="" role="button">
                <img class="mini-logo" src="{{ asset('img/dost-logo.png') }}" alt="">

                {{ config('app.client') }}
            </a> -->
            <img src="{{ asset('img/logo-horizontal.png') }}" alt="DOST Logo" style="width: 30%" class="d-none d-md-block" id="header-con">
        </div>
        
        <ul class="py-2 navbar-nav ms-auto d-flex justify-content-between gap-4 align-items-center">
            <!-- Hamburger Links -->
            <li class="nav-item d-lg-none">
                <div class="hamburger">
                    <input class="checkbox" type="checkbox" id="toggleSidebar" />
                    <div class="hamburger-lines">
                        <span class="line line1 "></span>
                        <span class="line line2 "></span>
                        <span class="line line3 "></span>
                    </div>
                </div>
            </li>
            <admin-header :username='@json(Auth::user()->name)' :user-role="'admin'" :user-id='@json(Auth::id())'>
        </ul>
    </div>
</nav>

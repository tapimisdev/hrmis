<nav class="navbar admin-nav navbar-expand-md bg-body-secondary navbar-light border-bottom  sticky-top">
    <div class="container-fluid d-flex justify-content-md-between">
        <div class="dropdown">
            <a class="nav-link text-capitalize d-flex align-items-center gap-3" style="font-size: 14px;" href="" role="button">
                <img class="mini-logo" src="{{ asset('img/dost-logo.png') }}" alt="">

                {{ config('app.client') }}
            </a>
        </div>
        
        <ul class="py-2 navbar-nav ms-auto d-flex justify-content-between gap-4 align-items-center">
            <admin-header :username='@json(Auth::user()->name)' :user-role="'admin'" :user-id='@json(Auth::id())'>
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

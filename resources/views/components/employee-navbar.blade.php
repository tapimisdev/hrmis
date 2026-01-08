<navbar class="d-flex justify-content-between align-items-center py-3 pb-4 mt-3">
    <div class="d-flex align-items-center gap-3">
        <img src="{{ asset('img/orbit_circle.png') }}" alt="DOST Logo" height="36" class="d-none d-md-block" id="header-con">
        <div>
            <h5 class="mb-0 text-light text-uppercase " style="letter-spacing: 3px;">
                {{ $title ?? config('app.name') }}
            </h5>
        </div>
    </div>
    {{ $slot }}
</navbar>
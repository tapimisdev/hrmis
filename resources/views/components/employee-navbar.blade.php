<navbar class="d-flex justify-content-between align-items-center py-3 pb-4 mt-3">
    <div class="d-flex align-items-center gap-3">
        <img src="{{ asset('img/dost.png') }}" alt="DOST Logo" height="32" class="d-none d-md-block">
        <div>
            <h5 class="fw-bold mb-0 text-light" style="letter-spacing: -0.3px;">
                {{ $title ?? config('app.name') }}
            </h5>
        </div>
    </div>
    {{ $slot }}
</navbar>
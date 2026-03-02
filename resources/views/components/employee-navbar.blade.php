<navbar class="d-flex justify-content-between align-items-center py-3 pb-4 mt-3">
    <div class="d-flex align-items-center gap-3">
        <img src="{{ asset('img/logo-horizontal.png') }}" alt="DOST Logo"  class="d-none d-md-block" id="header-con">
    </div>
    {{ $slot }}
</navbar>
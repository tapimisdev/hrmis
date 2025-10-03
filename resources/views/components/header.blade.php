<div class="d-flex flex-wrap justify-content-between align-items-center mb-2 mt-3">
    <div>
        <h3 class="fw-bold text-uppercase mb-1">{{ $title }}</h3>
        @if($subtitle)
            <p class="text-muted text-uppercase fw-medium mb-0">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="d-flex gap-3">
        {{ $slot }}
    </div>
</div>
<hr class="mt-3 mb-4">

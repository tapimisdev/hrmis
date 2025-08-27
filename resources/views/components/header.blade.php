<div class="d-flex mb-3 justify-content-between align-items-center pb-2 mt-4 pt-4">
    <div>
        <h1 class="text-xl fw-bold text-uppercase">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-gray-600 text-uppercase fw-medium">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="d-flex gap-2">
        {{ $slot }}
    </div>
</div>
<hr class="mt-4 mb-5">
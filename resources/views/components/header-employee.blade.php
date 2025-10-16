<div class="position-relative mb-4 mx-1">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="flex-grow-1">
            <h4 class="fw-bold mb-0" >
                {{ $title }}
            </h4>
            @if($subtitle)
                <p class="mb-0 text-muted" style="font-size: 0.938rem; font-weight: 500;">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            {{ $slot }}
        </div>
    </div>
</div>
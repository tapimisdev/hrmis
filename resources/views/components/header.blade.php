<div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-4 mt-4">
    <div class="flex-grow-1">
        <h2 class="fw-bold mb-2" style="font-size: 1.75rem; letter-spacing: -0.02em; color: #1a1a1a;">
            {{ $title }}
        </h2>
        @if($subtitle)
            <p class="mb-0" style="font-size: 0.95rem; color: #6b7280; font-weight: 500;">
                {{ $subtitle }}
            </p>
        @endif
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        {{ $slot }}
    </div>
</div>
<div class="mb-4" style="height: 1px; background: linear-gradient(to right, #e5e7eb 0%, #d1d5db 50%, #e5e7eb 100%);"></div>
<div class="position-relative mb-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-4">
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 mb-1">
                @php
                    // Use the passed breadcrumbs if provided, otherwise fallback
                    $breadcrumbs = $breadcrumbs ?? ['Pages', $title];
                @endphp

                @foreach($breadcrumbs as $index => $crumb)
                    @if($index < count($breadcrumbs) - 1)
                        <span class="text-light text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 500;">
                            {{ $crumb }}
                        </span>
                        <span class="text-light">/</span>
                    @else
                        <h1 class="mb-0 fs-3 fw-bold text-warning" style="letter-spacing: -0.5px;">
                            {{ $crumb }}
                        </h1>
                    @endif
                @endforeach
            </div>

            @if($subtitle)
                <p class="mb-0 text-light" style="font-size: 0.875rem; line-height: 1.5;">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        <div class="d-flex gap-2 flex-shrink-0 align-items-center">
            {{ $slot }}
        </div>
    </div>
</div>
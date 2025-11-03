<div class="position-relative mb-4 mx-1">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="flex-grow-1">
            <div class="page-name fs-4 mb-1 fw-bolder text-uppercase text-stroke" >
               <span>Pages / </span> {{ $title }}
            </div>
            @if($subtitle)
                <p class="mb-0 text-light" style="font-size: 0.738rem;">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            {{ $slot }}
        </div>
    </div>
</div>
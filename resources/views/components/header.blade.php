<header class="d-flex mb-3 justify-content-between align-items-center pb-2 pt-4 border-bottom">
    <div>
        <h1 class="text-xl font-bold">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-gray-600">{{ $subtitle }}</p>
        @endif
    </div>
    <div>
        {{ $slot }}
    </div>
</header>

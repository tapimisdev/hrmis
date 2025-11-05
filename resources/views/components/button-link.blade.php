@props([
    'href' => '#',
    'icon' => null,
    'text' => '',
    'variant' => 'secondary',
])

<a href="{{ $href }}"
   {{ $attributes->merge(['class' => " btn btn-$variant btn-modern"]) }}>
    @if ($icon)
        <i class="{{ $icon }} me-2"></i>
    @endif
    {{ $text ?: $slot }}
</a>
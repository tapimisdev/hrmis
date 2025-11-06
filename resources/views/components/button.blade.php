@props([
    'type' => 'button', 
    'variant' => 'primary',
    'id' => ''
])

<button type="{{ $type }}" id="{{ $id }}" class="btn btn-primary" {{ $attributes->merge(['class' => "btn btn-$variant"]) }}>
    {{ $slot }}
</button>

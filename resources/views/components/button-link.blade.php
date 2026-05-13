@props([
    'href' => '#',
    'icon' => null,
    'text' => '',
    'variant' => 'secondary',
])

@php
    $employeeIndexFilters = array_filter(
        request()->only(['account_status', 'division', 'unit', 'employment_type']),
        fn ($value) => filled($value)
    );

    if ($href === route('hris.employee.index') && ! empty($employeeIndexFilters)) {
        $href = route('hris.employee.index', $employeeIndexFilters);
    }
@endphp

<a href="{{ $href }}"
   {{ $attributes->merge(['class' => " btn btn-$variant btn-modern"]) }}>
    @if ($icon)
        <i class="{{ $icon }} me-2"></i>
    @endif
    {{ $text ?: $slot }}
</a>

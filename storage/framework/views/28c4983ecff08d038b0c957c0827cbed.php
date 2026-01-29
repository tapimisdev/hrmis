<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'href' => '#',
    'icon' => null,
    'text' => '',
    'variant' => 'secondary',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'href' => '#',
    'icon' => null,
    'text' => '',
    'variant' => 'secondary',
]); ?>
<?php foreach (array_filter(([
    'href' => '#',
    'icon' => null,
    'text' => '',
    'variant' => 'secondary',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<a href="<?php echo e($href); ?>"
   <?php echo e($attributes->merge(['class' => " btn btn-$variant btn-modern"])); ?>>
    <?php if($icon): ?>
        <i class="<?php echo e($icon); ?> me-2"></i>
    <?php endif; ?>
    <?php echo e($text ?: $slot); ?>

</a><?php /**PATH /var/www/html/resources/views/components/button-link.blade.php ENDPATH**/ ?>
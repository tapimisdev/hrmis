<?php $__env->startSection('content'); ?>
<div class="container-fluid min-vh-100">
    <?php if (isset($component)) { $__componentOriginal63abf539d940fbd177f362419b9fb810 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal63abf539d940fbd177f362419b9fb810 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.employee-navbar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('employee-navbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
        <header-vue title="Dashboard"></header-vue>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal63abf539d940fbd177f362419b9fb810)): ?>
<?php $attributes = $__attributesOriginal63abf539d940fbd177f362419b9fb810; ?>
<?php unset($__attributesOriginal63abf539d940fbd177f362419b9fb810); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal63abf539d940fbd177f362419b9fb810)): ?>
<?php $component = $__componentOriginal63abf539d940fbd177f362419b9fb810; ?>
<?php unset($__componentOriginal63abf539d940fbd177f362419b9fb810); ?>
<?php endif; ?>

    <?php
        $isRegular = Auth::user()->employment_type_id == \App\Enums\EmploymentTypesEnum::REGULAR->value;
    ?>
    
    <dashboard-index
        :is-regular='<?php echo json_encode($isRegular, 15, 512) ?>'
        name="<?php echo e($name); ?>"
    ></dashboard-index>



</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('employee.layout.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/employee/pages/dashboard/index.blade.php ENDPATH**/ ?>
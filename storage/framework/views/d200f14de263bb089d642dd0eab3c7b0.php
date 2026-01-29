<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <?php if (isset($component)) { $__componentOriginal2a2e454b2e62574a80c8110e5f128b60 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2a2e454b2e62574a80c8110e5f128b60 = $attributes; } ?>
<?php $component = App\View\Components\Header::resolve(['title' => 'Employee List','subtitle' => 'Manage employee\'s informations in this module'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(App\View\Components\Header::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle"
                            type="button" 
                            id="employeeActionsDropdown" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false">
                        <i class="fa-solid fa-gear me-2"></i> Actions 
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end w-100 dropdown-menu-modern" aria-labelledby="employeeActionsDropdown">
                        <li>
                            <a class="dropdown-item fw-bold text-uppercase d-flex align-items-center" href="<?php echo e(route('hris.import.index')); ?>">
                                <i class="fa-solid fa-file-import me-2"></i> Import
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item fw-bold text-uppercase d-flex align-items-center" href="<?php echo e(route('hris.employee.salary')); ?>">
                                <i class="fa-solid fa-peso-sign me-2"></i> Update Salary
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item fw-bold text-uppercase d-flex align-items-center" href="<?php echo e(route('hris.employee.transfer')); ?>">
                                <i class="fa-solid fa-right-left me-2"></i> Transfer Unit
                            </a>
                        </li>
                    </ul>
                </div>
                <?php if (isset($component)) { $__componentOriginale21d90f41251e682846d7af3e3cbbb3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale21d90f41251e682846d7af3e3cbbb3b = $attributes; } ?>
<?php $component = App\View\Components\ButtonLink::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('button-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(App\View\Components\ButtonLink::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('hris.employee.information')),'icon' => 'fa-solid fa-plus','text' => 'Add Employee','variant' => 'primary']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale21d90f41251e682846d7af3e3cbbb3b)): ?>
<?php $attributes = $__attributesOriginale21d90f41251e682846d7af3e3cbbb3b; ?>
<?php unset($__attributesOriginale21d90f41251e682846d7af3e3cbbb3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale21d90f41251e682846d7af3e3cbbb3b)): ?>
<?php $component = $__componentOriginale21d90f41251e682846d7af3e3cbbb3b; ?>
<?php unset($__componentOriginale21d90f41251e682846d7af3e3cbbb3b); ?>
<?php endif; ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2a2e454b2e62574a80c8110e5f128b60)): ?>
<?php $attributes = $__attributesOriginal2a2e454b2e62574a80c8110e5f128b60; ?>
<?php unset($__attributesOriginal2a2e454b2e62574a80c8110e5f128b60); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2a2e454b2e62574a80c8110e5f128b60)): ?>
<?php $component = $__componentOriginal2a2e454b2e62574a80c8110e5f128b60; ?>
<?php unset($__componentOriginal2a2e454b2e62574a80c8110e5f128b60); ?>
<?php endif; ?>
        <hris-index url="<?php echo e(route('hris.employee.index')); ?>"/>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/pages/hris/index.blade.php ENDPATH**/ ?>
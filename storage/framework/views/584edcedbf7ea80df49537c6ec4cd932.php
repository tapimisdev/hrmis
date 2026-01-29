<!-- Modal Component -->
<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'tcrModal','title' => 'TCR Details']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'tcrModal','title' => 'TCR Details']); ?>
    <div class="" style="font-family: Arial, sans-serif;">

        <form id="correctionForm" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" id="correction-id">

            <!-- Tabs for Timelog / Remarks & Attachment -->
            <ul class="nav nav-tabs" id="tcrTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="timelog-tab" data-bs-toggle="tab" data-bs-target="#timelog-tab-pane" type="button" role="tab">Timelog</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="remarks-tab" data-bs-toggle="tab" data-bs-target="#remarks-tab-pane" type="button" role="tab">Remarks & Attachment</button>
                </li>
            </ul>

            <div class="tab-content mt-3">

                <!-- Editable Timelog -->
                <div class="tab-pane fade show active" id="timelog-tab-pane" role="tabpanel">

                    <div class="row">
                        <div class="col-md-4">
                            <!-- Reference No (view-only) -->
                            <div class="mb-3">
                                <label class="form-label">Reference No</label>
                                <input type="text" id="reference_no" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <!-- Employee Name (view-only) -->
                            <div class="mb-3">
                                <label class="form-label">Employee Name</label>
                                <input type="text" id="employee-name" class="form-control" disabled>
                            </div>

                        </div>
                        <div class="col-md-4">
                            <!-- Date -->
                            <div class="mb-3">
                                <label class="form-label">Date <span class="text-danger">(required)</span></label>
                                <input type="date" name="date" id="date" class="form-control" disabled>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <!-- Time In -->
                            <div class="mb-3">
                                <label class="form-label">Time In <span class="text-danger">(required)</span></label>
                                <input type="time" name="time_in" id="time-in" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <!-- Break Out -->
                            <div class="mb-3">
                                <label class="form-label">Break Out</label>
                                <input type="time" name="break_out" id="break-out" class="form-control" disabled>
                            </div>

                        </div>
                        <div class="col-md-3">
                            <!-- Break In -->
                            <div class="mb-3">
                                <label class="form-label">Break In</label>
                                <input type="time" name="break_in" id="break-in" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <!-- Time Out -->
                            <div class="mb-3">
                                <label class="form-label">Time Out <span class="text-danger">(required)</span></label>
                                <input type="time" name="time_out" id="time-out" class="form-control" required disabled>
                            </div>
                        </div>

                    </div>

                    <!-- Overtime In/Out -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Overtime In</label>
                            <input type="time" name="overtime_in" id="overtime-in" class="form-control" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Overtime Out</label>
                            <input type="time" name="overtime_out" id="overtime-out" class="form-control" disabled>
                        </div>
                    </div>
                </div>

                <!-- Remarks & Attachment (view-only) -->
                <div class="tab-pane fade" id="remarks-tab-pane" role="tabpanel">
                    <!-- Remarks -->
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <p id="remarks" style="white-space: pre-wrap; padding: 0.5rem; border-radius: 5px;"></p>
                    </div>

                    <!-- Attachment -->
                    <div class="mb-3">
                        <!-- PDF -->
                        <iframe id="attachment-pdf" src="" style="width: 100%; height: 400px;" frameborder="0" class="d-none"></iframe>

                        <!-- Image -->
                        <img id="attachment-img" src="" alt="Attachment" class="img-fluid d-none mb-3" />

                        <!-- Fallback link -->
                        <a href="#" id="attachment-link" target="_blank" class="d-none">View Attachment</a>
                    </div>
                </div>

            </div>
        </form>

    </div>

     <?php $__env->slot('footer', null, []); ?> 
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-primary px-4 py-2 approve-button">
                <i class="me-2 fas fa-check"></i> Approved
            </button>
            <button type="button" class="btn btn-danger px-4 py-2 reject-button">
                <i class="me-2 fas fa-xmark"></i> Reject
            </button>
        </div>
     <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/admin/pages/timekeeping/timelog-correction/modal.blade.php ENDPATH**/ ?>
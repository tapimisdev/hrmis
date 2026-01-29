<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
'icon' => 'fa-solid fa-eye',
'id' => 'myModal',
'title' => 'View',
'size' => 'modal-lg',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
'icon' => 'fa-solid fa-eye',
'id' => 'myModal',
'title' => 'View',
'size' => 'modal-lg',
]); ?>
<?php foreach (array_filter(([
'icon' => 'fa-solid fa-eye',
'id' => 'myModal',
'title' => 'View',
'size' => 'modal-lg',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>
<div class="modal fade" id="<?php echo e($id ?? 'myModal'); ?>" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable <?php echo e($size ?? ''); ?>">
    <div class="modal-content modern-modal">

      
      <div class="modal-header modern-header">
        <div class="header-content border-bottom pb-2">
          <div class="icon-wrapper">
            <i class="<?php echo e($icon); ?> text-light"></i>
          </div>
          <div class="header-text text-uppercase">
            <h5 class="modal-title"><?php echo e($title); ?></h5>
          </div>
        </div>

        <button
          type="button"
          class="btn-close btn-close-white"
          data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>

      
      <div class="modal-body">
        <?php echo e($slot); ?>

      </div>

      
      <?php if(isset($footer)): ?>
      <div class="modal-footer">
        <?php echo e($footer); ?>

      </div>
      <?php endif; ?>

    </div>
  </div>
</div><?php /**PATH /var/www/html/resources/views/components/modal.blade.php ENDPATH**/ ?>
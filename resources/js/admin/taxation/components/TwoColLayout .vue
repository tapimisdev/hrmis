<template>
  <div class="row">
    <!-- TABLE SIDE -->
    <div class="col-md-8">
      <slot name="left"></slot>
    </div>

    <!-- RIGHT SIDE -->
    <div class="col-md-4">
      <slot name="right"></slot>
    </div>
  </div>
</template>

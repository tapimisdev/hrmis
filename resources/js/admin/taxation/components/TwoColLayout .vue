<template>
    <div class="row">
        <div class="col-md-12">
            <slot name="top"></slot>
        </div>

        <!-- TABLE SIDE -->
        <div class="col-sm-6 col-md-8">
            <slot name="left"></slot>
        </div>

        <!-- RIGHT SIDE -->
        <div class="col-sm-6 col-md-4">
            <slot name="right"></slot>
        </div>
    </div>
</template>

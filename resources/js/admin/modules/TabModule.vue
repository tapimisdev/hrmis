<template>
  <div>
    <ul class="nav nav-tabs">
      <li class="nav-item"v-for="(tab, index) in tabs" :key="tab.id">
        <a 
            class="nav-link" 
            :class="tab_name === tab.tab_slug ? 'active' : ''"
            @click=""
            aria-current="page" 
            href="#">
          {{ tab.tab_name }}
        </a>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { defineProps, toRef } from 'vue'

const props = defineProps({
  tabs: {
    type: Array,
    required: true
  },
  tab_name: {
    type: String,
    required: true
  },
})

const tabs = toRef(props, 'tabs')
</script>

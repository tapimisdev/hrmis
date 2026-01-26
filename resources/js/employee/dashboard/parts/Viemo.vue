<script setup>
import { onMounted } from 'vue'

onMounted(() => {
  const script = document.createElement('script')
  script.src = 'https://player.vimeo.com/api/player.js'
  script.async = true
  document.body.appendChild(script)
})
</script>

<template>
  <div>
    <iframe 
      src="https://player.vimeo.com/video/1158227042"
      width="100%"
      height=""
      frameborder="0"
      allow="autoplay; fullscreen"
      allowfullscreen>
    </iframe>
  </div>
</template>

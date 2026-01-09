<template>
  <Welcome :isRegular="isRegular" :name="name" />
  <Card :isRegular="isRegular"/>
  <Announcement :isRegular="isRegular"/>
</template>

<script>
import Announcement from './parts/Announcement.vue';
import Card from './parts/Card.vue';
import Welcome from './parts/Welcome.vue';

export default {
  name: 'Dashboard Index',
  components: { Welcome, Card, Announcement },
  props: {
    name: {
      type: String,
      required: true
    },
    isRegular: {
      type: Boolean,
      required: true
    }
  },
  data() {
    return {
      
    }
  },
}
</script>
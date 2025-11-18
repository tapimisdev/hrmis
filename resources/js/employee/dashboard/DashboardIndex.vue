<template>
  <Welcome :name="name" />
  <Card/>
  <Announcement/>
</template>

<script>
import Announcement from './parts/Announcement.vue';
import Card from './parts/Card.vue';
import Welcome from './parts/Welcome.vue';

export default {
  name: 'Dashboard Index',
  components: { Welcome, Card, Announcement },
  props: {
    name: String
  },
  data() {
    return {
      
    }
  },
}
</script>
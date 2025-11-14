<template>
  <div class="d-flex justify-content-start">
    <div class="container-c">
      <div class="row g-4">
        <div class="col-12 col-md-8">
          <Content :data="data.announcement" :tags="data.tags" :posted_by="data.posted_by" />
          <div class="row border-sa-top">
            <div class="col-md-8">
             <Attachments :data="data.attachments" />
            </div>
            <div class="col-md-4">
              <Seeners :data="data.seeners" />
            </div>
          </div>
        </div>
        <!-- Sidebar -->
        <div class="col-12 col-md-4">
          <SideEvents :data="data.random_announcements"/>
        </div>
      </div>
    </div>
   </div>
</template>

<script>
import Seeners from './show-parts/Seeners.vue';
import Attachments from './show-parts/Attachments.vue';
import Content from './show-parts/Content.vue';
import SideEvents from './show-parts/SideEvents.vue';

export default {
  name: "ShowAnnouncements",
  components: { Seeners, Attachments, Content, SideEvents },
  props: {
    data: Object
  }
};
</script>

<style lang="scss" scoped>
.container-c {
  max-width: 1200px;
}
</style>
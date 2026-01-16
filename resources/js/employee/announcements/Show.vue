<template>
    <div class="container-fluid overflow-hidden">
        <div class="row g-4">
            <div class="col-12 col-md-8" id="main-event-content">
                <Content
                    :data="data.announcement"
                    :tags="data.tags"
                    :posted_by="data.posted_by"
                />
                <div class="row border-sa-top">
                    <div class="col-md-8">
                        <Attachments :data="data.attachments" />
                    </div>
                    <div class="col-md-4">
                        <Seeners :data="data.seeners" />
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4" id="sub-event-content">
                <SideEvents :data="data.random_announcements" />
            </div>
        </div>
    </div>
</template>

<script>
import Seeners from "./show-parts/Seeners.vue";
import Attachments from "./show-parts/Attachments.vue";
import Content from "./show-parts/Content.vue";
import SideEvents from "./show-parts/SideEvents.vue";

export default {
    name: "ShowAnnouncements",
    components: { Seeners, Attachments, Content, SideEvents },
    props: {
        data: Object,
    },
};
</script>

<style lang="scss" scoped>
.container-c {
    max-width: 1200px;
}
</style>

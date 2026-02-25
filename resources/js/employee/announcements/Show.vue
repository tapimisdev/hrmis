<template>
    <div class="container-fluid">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading announcement...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="text-center py-5">
            <p class="text-danger">{{ error }}</p>
            <button class="btn btn-primary" @click="fetchAnnouncement">Retry</button>
        </div>

        <!-- Main Content (when loaded) -->
        <div v-else class="row g-4">
            <div class="col-12 col-md-8" id="main-event-content">
                <Content
                    :data="announcementData.announcement"
                    :tags="announcementData.tags"
                    :posted_by="announcementData.posted_by"
                />
                <div class="row border-sa-top">
                    <div class="col-md-8">
                        <Attachments :data="announcementData.attachments" />
                    </div>
                    <div class="col-md-4">
                        <Seeners :announcement="announcementData.announcement" :data="announcementData.seeners" />
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4" id="sub-event-content">
                <SideEvents :data="announcementData.random_announcements" :loading="loading" />
            </div>
        </div>
    </div>
</template>

<script>
import Seeners from "./show-parts/Seeners.vue";
import Attachments from "./show-parts/Attachments.vue";
import Content from "./show-parts/Content.vue";
import SideEvents from "./show-parts/SideEvents.vue";
import axios from "axios";

export default {
    name: "ShowAnnouncements",
    components: { Seeners, Attachments, Content, SideEvents },
    props: {
        slug: {
            type: String,
            required: true,
        },
    },
    data() {
        const token = localStorage.getItem('auth_token');
        return {
            token,
            announcementData: {
                announcement: {},
                tags: [],
                posted_by: {},
                attachments: [],
                seeners: [],
                random_announcements: [],
            },
            loading: false,
            error: null,
        };
    },
    mounted() {
        this.fetchAnnouncement();
    },
    methods: {
        async fetchAnnouncement() {
            this.loading = true;
            this.error = null;
            try {
                const response = await axios.get(`/api/employee/announcements/${this.slug}`, {
                    headers: { Authorization: `Bearer ${this.token}` }, 
                });
                this.announcementData = response.data.data;
                await this.$nextTick();
            } catch (err) {
                console.error("Error fetching announcement:", err);
                this.error = "Failed to load announcement. Please try again.";
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style scoped>

</style>
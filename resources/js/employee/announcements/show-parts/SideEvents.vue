<template>
    <div class="other" 5>
        <h5 class="sidebar-title">Others</h5>

        <!-- If there is data -->
        <template v-if="data && data.length > 0">
            <AnnouncementCard
                class="mb-3"
                style="width: 100%"
                v-for="announcement in data"
                :key="announcement.id"
                :announcement="announcement"
            />
        </template>

        <!-- If no data -->
        <div v-else class="event-card text-center p-3">
            <div class="event-card-content p-0">
                <p class="mb-0 text-muted text-uppercase fw-medium" style="font-size: 12px;">
                    No other events or announcements found
                </p>
            </div>
        </div>
    </div>
</template>

<script>
import AnnouncementCard from "../AnnouncementCard.vue";
export default {
    components: { AnnouncementCard },
    props: {
        data: Array,
    },
    mounted() {
        if (this.data.length <= 0) {
        }
    },
};
</script>

<style scoped lang="scss">
.other {
    position: sticky;
    top: 30px;
}
.multiline-ellipsis {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
    -webkit-line-clamp: 2;
    text-overflow: ellipsis;
}
.multiline-ellipsis-title {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
    -webkit-line-clamp: 1;
    text-overflow: ellipsis;
}
// Sidebar
.sidebar-title {
    font-size: 1.1rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--bs-warning);
    margin-bottom: 1rem;
    letter-spacing: 0.5px;
}

.event-card {
    background: var(--bs-secondary-bg);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    margin-bottom: 24px;
}

.event-card-image {
    overflow: hidden;

    img {
        width: 100%;
        height: 180px;
        object-fit: cover;
        display: block;
    }
}

.event-card-content {
    padding: 1rem 1rem 0.7rem 1rem;
}

.see-more {
    display: flex;
    justify-content: end;
    padding: 0.1rem 1rem 0.8rem 1rem;
}
.event-card-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--bs-body-color);
    margin-bottom: 0.75rem;
    line-height: 1.4;
}

.event-card-description {
    font-size: 0.875rem;
    line-height: 1.6;
    color: #6c757d;
}

// Responsive
@media (max-width: 767px) {
    .announcement-title {
        font-size: 1.5rem;
    }

    .image-container img {
        max-height: 280px;
    }

    .viewers-section {
        justify-content: center;
    }
}
</style>

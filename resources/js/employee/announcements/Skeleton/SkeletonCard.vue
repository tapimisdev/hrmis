<template>
    <div class="skeleton-card shadow">
        <div>
            <div class="card-image">
                <div class="skeleton skeleton-image"></div>
                <div class="card-overlay">
                    <div class="skeleton skeleton-badge"></div>
                </div>
            </div>

            <div class="card-content">
                <div class="card-tags">
                    <div class="skeleton skeleton-tag"></div>
                    <div class="skeleton skeleton-tag"></div>
                </div>

                <div class="skeleton skeleton-title"></div>
                <div class="card-description">
                    <div class="skeleton skeleton-text"></div>
                    <div class="skeleton skeleton-text"></div>
                </div>

                <div class="card-footer">
                    <div class="seeners">
                        <div class="seeners-avatars">
                            <div class="skeleton skeleton-avatar"></div>
                            <div class="skeleton skeleton-avatar"></div>
                            <div class="skeleton skeleton-avatar"></div>
                        </div>
                        <div class="skeleton skeleton-count"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "SkeletonAnnouncementCard",
};
</script>

<style scoped>
.skeleton-card {
    border: 1px solid #e0e0e013;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    /* Mimic the announcement-card styles if any */
}

.card-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.card-image {
    position: relative;
    overflow: hidden;
}

.card-overlay {
    position: absolute;
    top: 10px;
    right: 10px;
}

.card-content {
    padding: 1rem;
}

.card-tags {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.card-description {
    margin-bottom: 1rem;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.seeners {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.seeners-avatars {
    display: flex;
    position: relative;
}

.seeners-avatars .skeleton-avatar:not(:first-child) {
    margin-left: -10px; /* Overlap like real avatars */
}

.skeleton {
    background: #e0e0e0;
    border-radius: 4px;
    animation: pulse 1.5s ease-in-out infinite;
}

.skeleton-image {
    height: 200px; /* Adjust based on your actual image height */
    width: 100%;
}

.skeleton-badge {
    height: 20px;
    width: 80px; /* For "Read More" badge */
}

.skeleton-tag {
    height: 20px;
    width: 60px; /* For tag spans */
}

.skeleton-title {
    height: 24px; /* For h6 title */
    width: 80%;
    margin-bottom: 10px;
}

.skeleton-text {
    height: 22px; /* For p description lines */
    width: 100%;
    margin-bottom: 8px;
}

.skeleton-avatar {
    height: 32px; /* Circular avatar size */
    width: 32px;
    border-radius: 50%;
}

.skeleton-count {
    height: 16px;
    width: 60px; /* For "X+ viewed" count */
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
</style>
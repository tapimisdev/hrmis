<template>
    <div class="px-3 pt-3">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="d-flex align-items-center gap-3">
                
                <!-- Profile Image -->
                <img
                    :src="profileSrc"
                    class="profile-img"
                    alt="Employee Profile"
                />

                <!-- Employee Info -->
                <div>
                    <div class="fw-semibold text-body breakdown-name">
                        {{ row.full_name || "-" }}
                    </div>

                    <div class="small text-muted breakdown-sub">
                        {{ row.position || "-" }}
                    </div>

                    <div class="small text-muted breakdown-sub">
                        {{ row.division || "-" }}
                        <span v-if="row.unit && row.unit !== row.division">
                            ( {{ row.unit }} )
                        </span>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="text-end">
                <span
                    class="badge bg-primary-subtle text-primary border border-primary-subtle"
                >
                    ACTIVE
                </span>
                <div class="small text-muted mt-1">Forecast</div>
            </div>
        </div>

        <hr class="old-divider" />
    </div>
</template>

<script>
export default {
    props: {
        row: { type: Object, required: true },
    },

    computed: {
        profileSrc() {
            if (this.row.avatar) {
                return this.row.avatar;
            }
            return "/img/default-avatar.png";
        },
    },
};
</script>

<style scoped>
.profile-img {
    width: 46px;
    height: 46px;
    border-radius: 4px;
    object-fit: cover;
    background-color: var(--bs-secondary-bg);
    border: 2px solid var(--bs-border-color);
}
</style>
<template>
    <div>
        <!-- LOADING STATE -->
        <div
            v-if="loading"
            class="text-center d-flex align-items-center justify-content-center gap-2 py-4"
        >
            <div
                class="spinner-border text-body text-opacity-25"
                role="status"
                style="height: 12px; width: 12px"
            >
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-2 fw-semibold text-body text-opacity-25">
                Loading ...
            </div>
        </div>

        <div v-else>
            <h5 class="border-bottom pb-3 text-uppercase fw-bolder">
                Birthdays for this month
            </h5>
            <div class="row g-2" v-if="people.length">
                <div
                    class="col-md-4"
                    v-for="(person, index) in people"
                    :key="index"
                >
                    <a
                        target="_blank"
                        :href="`/admin/hris/employee/information/${person.employee_no}`"
                        class="border rounded-2 px-3 py-2 d-flex align-items-center gap-3 text-decoration-none"
                        style="color: inherit !important;"
                    >
                        <div>
                          <img
                              :src="person.image"
                              alt="Profile Picture"
                              class="profile-picture"
                          />
                        </div>
                        <div>
                            <div class="text-clamp-1">{{ person.name }}</div>
                            <div class="text-clamp-1">{{ formatBirthday(person.birthday) }}</div>
                        </div>
                      </a>
                </div>
            </div>

            <div class="alert alert-info text-center" v-if="!people.length">
                No birthdays for this month 🎂
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ProfileList",
    props: {
        people: {
            type: Array,
            required: true,
            validator(value) {
                return value.every(
                    (p) => "name" in p && "birthday" in p && "image" in p,
                );
            },
        },
        loading: {
            type: Boolean,
            default: true,
        },
    },
    methods: {
        formatBirthday(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "long",
                day: "numeric",
            });
        },
    },
};
</script>

<style lang="scss" scoped>
@import "./../../../../sass/variables";

.profile-picture {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--bs-primary);
}
</style>

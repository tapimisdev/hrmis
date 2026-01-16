<template>
  <div>
    <h5 class="border-bottom pb-3 text-uppercase fw-bolder">
      Today's Birthday
    </h5>

    <!-- LOADING STATE -->
    <div v-if="loading" class="text-center d-flex align-items-center justify-content-center gap-2 py-4">
      <div class="spinner-border text-body text-opacity-25" role="status" style="height: 12px; width: 12px;">
        <span class="visually-hidden">Loading...</span>
      </div>
      <div class="mt-2 fw-semibold text-body text-opacity-25">Loading ...</div>
    </div>

    <!-- TABLE -->
    <table v-else class="table table-transparent w-100 h-100 align-middle">
      <thead>
        <tr>
          <th class="pb-2" scope="col">Profile</th>
          <th class="pb-2" scope="col">Name</th>
          <th class="pb-2" scope="col">Birthday</th>
        </tr>
      </thead>

      <tbody v-if="people.length">
        <tr v-for="(person, index) in people" :key="index">
          <td>
            <img
              :src="person.image"
              alt="Profile Picture"
              class="profile-picture"
              
            />
          </td>
          <td>{{ person.name }}</td>
          <td>{{ formatBirthday(person.birthday) }}</td>
        </tr>
      </tbody>

      <!-- EMPTY STATE -->
      <tbody v-else>
        <tr>
          <td colspan="3" class="text-center text-muted py-4">
            No birthdays today 🎂
          </td>
        </tr>
      </tbody>
    </table>
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
          (p) => "name" in p && "birthday" in p && "image" in p
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

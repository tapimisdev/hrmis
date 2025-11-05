<template>
  <div class="cardiness">
    <h5 class="border-bottom pb-3 text-uppercase fw-bolder">Today's Birthday</h5>
    <table class="table table-sm table-sm align-middle text-center">
      <thead>
        <tr>
          <th scope="col">Profile</th>
          <th scope="col">Name</th>
          <th scope="col">Birthday</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(person, index) in people" :key="index">
          <td>
            <img :src="person.image" alt="Profile Picture" class="profile-picture" />
          </td>
          <td>{{ person.name }}</td>
          <td>{{ formatBirthday(person.birthday) }}</td>
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

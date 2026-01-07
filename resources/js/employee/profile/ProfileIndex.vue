<template>
  <employee-index>
    <ChangePassword/>
  </employee-index>
</template>

<script>
import EmployeeIndex from './EmployeeInformation/EmployeeIndex.vue';
import ChangePassword from './ChangePassword.vue';
export default {
  components: {
    ChangePassword,
    EmployeeIndex
  },
}
</script>


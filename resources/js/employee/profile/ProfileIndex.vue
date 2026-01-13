<template>
  <employee-index>
    <ChangePassword title='Change Password'/>
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


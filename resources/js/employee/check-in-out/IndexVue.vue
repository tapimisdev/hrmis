<template lang="">
    <div>
        <check-in-out-vue 
          :is-allowed="isAllowed" 
          @submit-log="handleSubmit">
        </check-in-out-vue>
        <employee-timelog ref="employeeTimelog" 
          :employee-number='employeeNumber' 
          :supervisor='supervisor'
        /> 
    </div>
</template>

<script>
    import CheckInOutVue from './CheckInOutVue.vue';
    import EmployeeTimelog from './EmployeeTimelog.vue';
    export default {
        name: 'IndexVue',
        components: {
            CheckInOutVue,
            EmployeeTimelog,
        },  
        props: {
            isAllowed: { type: Boolean, required: true },
            employeeNumber: { type: String, required: true },
            supervisor: { type: String, required: true },
        },
        methods: {
            handleSubmit() {
                this.$refs.employeeTimelog.loadTimelogs();
            },
        },
    };
</script>
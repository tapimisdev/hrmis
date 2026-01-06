<template lang="">
    <div>
        <check-in-out-vue @submit-log="handleSubmit"></check-in-out-vue>
        <employee-timelog ref="employeeTimelog" :employee-number='employeeNumber' /> 
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
            employeeNumber: { type: String, required: true },
        },
        methods: {
            handleSubmit() {
                this.$refs.employeeTimelog.loadTimelogs();
            },
        },
    };
</script>
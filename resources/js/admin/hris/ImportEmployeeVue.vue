<template>
    <div class="uploading">
        <FormEmployeeVue 
            v-show="upload"
            @handle-employee="handleEmployeeFromUpload"
            @request-units="getUnits"
            :employmentTypes="employmentTypes"
            :divisions="divisions"
            :units="units"
            :shifts="shifts"
            :schedules="schedules"
            :tranches="tranches"
            />
        <ViewEmployeeVue 
            v-show="!upload" 
            @back-to-form="backtoFormFromView"
            @remove-employee="removeEmployee"

            :employees="employees_data.employees"
            :details="employees_data.details"            
            :employmentTypes="employmentTypes"

            :divisions="divisions"
            :units="units"
            :shifts="shifts"
            :schedules="schedules"
            />
    </div>
</template>

<script>
const token = localStorage.getItem('auth_token');
import ViewEmployeeVue from './ViewEmployeeVue.vue';
import FormEmployeeVue from './FormEmployeeVue.vue';
export default {
    components: {
        FormEmployeeVue,
        ViewEmployeeVue
    },
    data() {
        return {
            upload: true,
            employees_data: [],
            employmentTypes: [],
            divisions: [],
            units: [],
            shifts: [],
            schedules: [],
            tranches: [],
        };
    },
    mounted() {
        this.fetchData('divisions', '/api/divisions');
        this.fetchData('employmentTypes', '/api/employment-types', true);
        this.fetchData('shifts', '/api/shifts', true);
        this.fetchData('schedules', '/api/work-schedules', true);
        this.fetchData('tranches', '/api/tranches', true);
    },
    methods: {
        fetchData(stateKey, url, useDataWrapper = false) {
            axios.get(url, {
                headers: { Authorization: `Bearer ${token}` }
            }).then(response => {
                this[stateKey] = useDataWrapper ? response.data.data : response.data;
            });
        },
        getUnits(divisionId) {
            axios.get(`/api/units/${divisionId}`, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            }).then(response => {
                this.units = response.data;
            })
        },
        handleEmployeeFromUpload(inf) {
            this.employees_data = inf;
            this.upload = false;
        },
        backtoFormFromView() {
            this.upload = true;
        },
        removeEmployee(index) {
          this.employees_data.employees.splice(index, 1)
        }
    },
};
</script>

<style scoped>
</style>

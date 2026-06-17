import '../bootstrap';
import { createApp } from 'vue';
import ApplicantJobs from './ApplicantJobs.vue';
import ApplicantDashboard from './ApplicantDashboard.vue';
import ApplicantRegister from './ApplicantRegister.vue';

createApp({ components: { ApplicantJobs, ApplicantDashboard, ApplicantRegister } }).mount('#applicant-app');

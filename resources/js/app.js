import './bootstrap';
import { createApp } from 'vue';
import CheckInOutVue from './employee/check-in-out/CheckInOutVue.vue';

const authApp = createApp({
  components: {
    CheckInOutVue,
  },
});

authApp.mount('#app');

$('#toggleSidebar').on('change', function() {
  var sidebar = $('.sidebar');
  sidebar.toggleClass('show', this.checked);
});
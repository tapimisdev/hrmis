import { createApp } from 'vue';
import CheckInOutVue from './employee/check-in-out/CheckInOutVue.vue';

import { post, put } from './action';
import { 
    confirmAction, alert, pushQuery, redirectToTab
} from './helper';

window.post = post;
window.put = put;

redirectToTab();

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

$(document).on('click', '#btn-delete', function() {
    const url = $(this).data('target');

    confirmAction(
        'Delete Record?',
        'This action cannot be undone!',
        'Yes, delete it!',
        () => {
            axios.delete(url)
                .then(response => {
                    const res = response.data;
                    alert(res.status, res.message, res.redirect);
                })
                .catch(err => {
                    alert(res.status, res.message, res.redirect);
                });
        }
    );
});

$(document).on('click', '#btn-restore', function() {
    const url = $(this).data('target');

    confirmAction(
        'Restore Account?',
        'This action cannot be undone!',
        'Yes, restore it!',
        () => {
            axios.delete(url)
                .then(response => {
                    const res = response.data;
                    alert(res.status, res.message, res.redirect);
                })
                .catch(err => {
                    alert(res.status, res.message, res.redirect);
                });
        }
    );
});

$(document).on('click', '.push-state-query', function() {
    let tabName = $(this).data('id');
    pushQuery('tab', tabName);
});

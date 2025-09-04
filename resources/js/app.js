import { createApp } from 'vue';
import CheckInOutVue from './employee/check-in-out/CheckInOutVue.vue';
import './bootstrap';

import { post, put } from './action';
import { 
    confirmAction, alert, pushQuery, redirectToTab
} from './helper';
import lightGallery from 'lightgallery';
import lgThumbnail from 'lightgallery/plugins/thumbnail'
import lgZoom from 'lightgallery/plugins/zoom'

window.post = post;
window.put = put;
window.lightGallery = lightGallery;
window.lgThumbnail = lgThumbnail;
window.lgZoom = lgZoom;

redirectToTab();

const authApp = createApp({
  components: {
    CheckInOutVue,
  },
});

window.SuccesToast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    background: "#00af00",
    icon: "success",
    color: "#F6F5F5",
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    }
});
window.ErrorToast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    background: "#e03b3b",
    icon: "error",
    color: "#F6F5F5", 
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    }
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

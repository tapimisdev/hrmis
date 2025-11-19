import './bootstrap';
import './laravel-echo';
import './vue';
import './dark-mode';

import axios from 'axios';
import { post, put } from './action';
import { 
    confirmAction, alert, pushQuery, redirectToTab, loadCountries
} from './helper';
import lightGallery from 'lightgallery';
import lgThumbnail from 'lightgallery/plugins/thumbnail'
import lgZoom from 'lightgallery/plugins/zoom'
import { initCalendar, setEvents, generateEventsWithAvailability } from './calendar';

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const token = localStorage.getItem('auth_token');

if (csrf) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;
    $.ajaxSetup({
        headers: {
            'Authorization': 'Bearer ' + token
        }
    });
} else {
  console.error('CSRF token not found!');
}


window.post = post;
window.put = put;
window.lightGallery = lightGallery;
window.lgThumbnail = lgThumbnail;
window.lgZoom = lgZoom;
window.loadCountries = loadCountries;
window.alert = alert;
window.initCalendar = initCalendar;
window.setEvents = setEvents;
window.confirmAction = confirmAction;
window.generateEventsWithAvailability = generateEventsWithAvailability;

redirectToTab();

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
                    alert(err.status, err.message, err.redirect);
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

$('.select2').select2({
    allowClear: true,
    width: '100%',
    closeOnSelect: false
});


if ($('.datepicker').length) {
    $('.datepicker').daterangepicker();
}

if ($('.ckeditor').length) {
    ClassicEditor
        .create(document.querySelector('.ckeditor'), {
            toolbar: [
                'heading', '|',
                'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|',
                'blockQuote', 'undo', 'redo'
            ],
            removePlugins: [
                'CKFinder',
                'CKFinderUploadAdapter',
                'EasyImage',
                'Image',
                'ImageCaption',
                'ImageStyle',
                'ImageToolbar',
                'ImageUpload',
                'MediaEmbed',
                'MediaEmbedToolbar'
            ]
        })
        .then(editor => {
            editor.ui.view.editable.element.style.height = '300px';
        })
        .catch(error => {
            console.error(error);
        });
}

 $(document).on('click', '.open-document', function() {

    const src = $(this).data('src'); 

    const galleryContainer = document.getElementById('galleryContainer');
    if (galleryContainer.lightGalleryInstance) {
        galleryContainer.lightGalleryInstance.destroy();
    }

    const gallery = lightGallery(galleryContainer, {
        dynamic: true,
        dynamicEl: [
            {
                src: src,
                iframe: true
            }
        ],
        plugins: [lgThumbnail, lgZoom],
        licenseKey: '0000-0000-000-0000',
        speed: 500
    });

    galleryContainer.lightGalleryInstance = gallery;

    gallery.openGallery();
});

const $toggles = $('.sidebar-link[data-bs-toggle="collapse"]');

// Restore collapse states from localStorage
$toggles.each(function () {
    const targetSelector = $(this).data('bs-target');
    const savedState = localStorage.getItem(targetSelector);

    if (savedState === 'open') {
        $(targetSelector).addClass('show');
        $(this).attr('aria-expanded', 'true').removeClass('collapsed');
    } else {
        $(targetSelector).removeClass('show');
        $(this).attr('aria-expanded', 'false').addClass('collapsed');
    }
});

// Listen to Bootstrap collapse events on each target
$toggles.each(function () {
    const targetSelector = $(this).data('bs-target');
    const $target = $(targetSelector);

    $target.on('show.bs.collapse', function () {
        // When this one is about to open, close all others
        $toggles.each(function () {
            const otherTargetSelector = $(this).data('bs-target');
            if (otherTargetSelector !== targetSelector) {
                const $otherTarget = $(otherTargetSelector);
                if ($otherTarget.hasClass('show')) {
                    $otherTarget.collapse('hide');
                }
            }
        });
    });

    $target.on('shown.bs.collapse', function () {
        localStorage.setItem(targetSelector, 'open');
        $(this).prev('.sidebar-link').attr('aria-expanded', 'true').removeClass('collapsed');
    });

    $target.on('hidden.bs.collapse', function () {
        localStorage.setItem(targetSelector, 'closed');
        $(this).prev('.sidebar-link').attr('aria-expanded', 'false').addClass('collapsed');
    });
});
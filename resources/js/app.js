import './bootstrap';
import './vue';

import { post, put } from './action';
import { 
    confirmAction, alert, pushQuery, redirectToTab, loadCountries
} from './helper';
import lightGallery from 'lightgallery';
import lgThumbnail from 'lightgallery/plugins/thumbnail'
import lgZoom from 'lightgallery/plugins/zoom'

window.post = post;
window.put = put;
window.lightGallery = lightGallery;
window.lgThumbnail = lgThumbnail;
window.lgZoom = lgZoom;
window.loadCountries = loadCountries;
window.alert = alert;

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

$('.select2').select2({
    placeholder: " - CHOOSE - ",
    allowClear: true,
    width: '100%',
    dropdownParent: $('body'),
    closeOnSelect: false 
});

$('.datepicker').daterangepicker();

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
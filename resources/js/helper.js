import * as bootstrap from 'bootstrap';

export function alert(type, message, redirect = '') {
    const config = {
        success: { title: "Yey!", icon: "success" },
        error: { title: "Oops!", icon: "error" },
        info: { title: "Please be informed!", icon: "info" }
    };

    if (!config[type]) return;

    if (type == 'success') {
        $('.form-control, .form-select').removeClass('is-invalid');
        $('.error-field').text('');

        $('table').each(function() {
            if ($.fn.DataTable.isDataTable(this)) {
                $(this).DataTable().ajax.reload(null, false);
            }
        });
    }

    Swal.fire({
        title: config[type].title,
        text: message,
        icon: config[type].icon,
        confirmButtonText: 'Got It'
    }).then(() => {
        if (redirect && redirect !== '_self') {
            window.location.href = redirect;
        } else if (redirect === '_self') {
            $('form').trigger('reset');
        }
    });
}

export function fieldError(error) {
    if (error.response && error.response.status === 422) {
        $('.form-control, .form-select').removeClass('is-invalid');
        $('.error-field').text('');

        let firstErrorField = null; 

        $.each(error.response.data.errors, function(fieldName, errorMessage) {
            const $field = $(`[id="${fieldName}"]`);
            $field.addClass('is-invalid');
            $field.closest('.mb-3').find('.error-field').text(errorMessage[0]);
            if (!firstErrorField) {
                firstErrorField = $field;
            }
        });

        if (firstErrorField && firstErrorField.length) {
            
            setTimeout(() => {
                $('html, body').animate({
                    scrollTop: firstErrorField.offset().top - 500 
                }, 600, 'swing');
            }, 100);
            

            firstErrorField.focus({ preventScroll: true });
        }       
    } else {
        Swal.fire({
            title: "Oops!",
            text: error.response?.data?.message || "Something went wrong.",
            icon: "error"
        });
    }
}

export function confirmAction(title, text, confirmText = 'Yes, proceed!', callback) {
    Swal.fire({
        title: title || 'Are you sure?',
        text: text || "You won't be able to undo this!",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmText
    }).then((result) => {
        if (result.isConfirmed) {
            callback(); 
        }
    });
}

export function pushQuery(param, value) {
    let url = new URL(window.location);

    if (value !== null && value !== undefined && value !== "") {
        url.searchParams.set(param, value);
    } else {
        url.searchParams.delete(param);
    }

    window.history.pushState({}, '', url);
}

export function redirectToTab() {
    const tabParam = new URLSearchParams(window.location.search).get('tab');

    if (!tabParam) return;

    const selector = `[data-bs-toggle="tab"][data-bs-target="#${tabParam}"], 
                      [data-bs-toggle="tab"][href="#${tabParam}"]`;

    const tabTrigger = document.querySelector(selector.trim());

    if (tabTrigger) {
        new bootstrap.Tab(tabTrigger).show();
    }
}

export function loadCountries() {
    return $.ajax({
        url: '/api/countries',
        method: 'GET',
        dataType: 'json'
    });
}

export function createWatch(getter, callback) {

}

export function onQueryParam(key, value, callback) {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get(key) === value && typeof callback === 'function') {
        callback();
    }
}

export function alert(type, message, redirect = '') {
    const config = {
        success: { title: "Yey!", icon: "success" },
        error: { title: "Oops!", icon: "error" }
    };

    if (!config[type]) return;

    if (type === 'success') {
        $('.form-control').removeClass('is-invalid');
        $('.error-field').text('');

        if ($.fn.DataTable.isDataTable('table')) {
            $('table').DataTable().ajax.reload(null, false);
        }
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
            $('#form').trigger('reset');
        }
    });
}

export function fieldError(error) {
    if (error.response && error.response.status === 422) {
        $('.form-control').removeClass('is-invalid');
        $('.error-field').text('');

        let firstErrorField = null; 

        $.each(error.response.data.errors, function(fieldName, errorMessage) {
            const $field = $(`[id="${fieldName}"]`);
            $field.addClass('is-invalid');
            $field.closest('.mb-3').find('.error-field').text(errorMessage[0]);
            console.log(fieldName)
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
    const url = new URL(window.location);
    const tab = url.searchParams.get('tab');

    if (tab) {
        const triggerEl = document.querySelector(
            `[data-bs-toggle="tab"][data-bs-target="#${tab}"], 
             [data-bs-toggle="tab"][href="#${tab}"]`
        );

        if (triggerEl) {
            const tabObj = new bootstrap.Tab(triggerEl);
            tabObj.show();
        }
    }
}

export function loadCountries() {
    return $.ajax({
        url: '/api/countries',
        method: 'GET',
        dataType: 'json'
    });
}

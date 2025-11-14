import { alert, fieldError, confirmAction } from './helper';

function handleFormSubmit(method, url, hasRemarks = false, formSelector) {

    $(formSelector).on('submit', function (e) {
        e.preventDefault();

        const form = this;

        const processRequest = (remarks = '') => {
            let formData = new FormData(form);
            formData.append('_method', method);
            if (hasRemarks) formData.append('remarks', remarks);

            const $btn = $(form).find('button[type="submit"]');
            const originalLabel = $btn.html(); // store original HTML

            $btn.prop('disabled', true).html('Please Wait...'); // can be plain text or HTML

            axios.post(url, formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then((response) => {
                const res = response.data;
                alert(res.status, res.message, res.redirect);
            })
            .catch(error => {
                fieldError(error);
            })
            .finally(() => {
                $btn.prop('disabled', false).html(originalLabel); // restore original HTML
            });
        };

        // === WITH REMARKS ===
        if (hasRemarks) {
            Swal.fire({
                title: 'Are you sure to continue?',
                text: 'Please provide remarks before proceeding.',
                input: 'textarea',
                inputPlaceholder: 'Enter your remarks here...',
                showCancelButton: true,
                confirmButtonText: 'Submit',
                cancelButtonText: 'Cancel',
                inputValidator: value => !value && 'Remarks are required.'
            }).then(result => {
                if (result.isConfirmed) processRequest(result.value);
            });
        }

        // === WITHOUT REMARKS ===
        else {
            confirmAction(
                'Are you sure to continue?',
                'Once performed, this action is permanent and cannot be undone',
                'Yes, proceed!',
                () => processRequest()
            );
        }
    });
}

export function post(url, hasRemarks = false, formSelector = '[id^="form"]') {
    handleFormSubmit('POST', url, hasRemarks, formSelector);
}

export function put(url, hasRemarks = false, formSelector = '[id^="form"]') {
    handleFormSubmit('PUT', url, hasRemarks, formSelector);
}

export function remove(url) {
    confirmAction(
        'Delete Record?',
        'This action cannot be undone!',
        'Yes, delete it!',
        () => {
            axios.delete(url)
                .then(res => alert('success', 'Record deleted successfully'))
                .catch(err => alert('error', 'Failed to delete record'));
        }
    );
}

import { alert, fieldError, confirmAction } from './helper';

/**
 * Handles form submission for POST/PUT requests with optional remarks.
 *
 * @param {string} method - The HTTP method (POST or PUT).
 * @param {string} url - The endpoint to submit to.
 * @param {boolean} hasRemarks - Whether remarks input is required.
 * @param {string} formSelector - The target form selector.
 */
function handleFormSubmit(method, url, hasRemarks = false, formSelector) {
    console.log(method, url, hasRemarks, formSelector);

    // Bind submit event to selected form(s)
    $(formSelector).on('submit', function (e) {
        e.preventDefault();

        const form = this;

        /**
         * Processes the actual request after optional remarks are confirmed.
         *
         * @param {string} remarks - Remarks from user (if required).
         */
        const processRequest = (remarks = '') => {
            let formData = new FormData(form);

            // Append method override (for PUT)
            formData.append('_method', method);

            // Append remarks when required
            if (hasRemarks) formData.append('remarks', remarks);

            // Handle submit button state
            const $btn = $(form).find('button[type="submit"]').last();
            const originalLabel = $btn.html();
            $btn.prop('disabled', true).html('Please Wait...');

            // Execute axios request
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
                // Display field-level validation errors
                fieldError(error);
            })
            .finally(() => {
                // Restore submit button
                $btn.prop('disabled', false).html(originalLabel);
            });
        };

        // =============================
        //     FORMS THAT REQUIRE REMARKS
        // =============================
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

        // =============================
        //    STANDARD CONFIRM DIALOG
        // =============================
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

/**
 * Exported POST helper
 */
export function post(url, hasRemarks = false, formSelector = '[id^="form"]') {
    handleFormSubmit('POST', url, hasRemarks, formSelector);
}

/**
 * Exported PUT helper
 */
export function put(url, hasRemarks = false, formSelector = '[id^="form"]') {
    handleFormSubmit('PUT', url, hasRemarks, formSelector);
}

/**
 * Handles delete confirmation and request
 */
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

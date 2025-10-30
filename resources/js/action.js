import { alert, fieldError, confirmAction} from './helper';

function handleFormSubmit(method, url, hasRemarks = false) {
    $('[id^="form"]').on('submit', function (e) {
        e.preventDefault();

        const form = this;

        const processRequest = (remarks = '') => {
            let formData = new FormData(form);
            formData.append('_method', method);
            if (hasRemarks) formData.append('remarks', remarks);

            const $btn = $('#btn-submit');
            $btn.prop('disabled', true).text('Please Wait...');

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
                $btn.prop('disabled', false).text('Save');
            });
        };

        if (hasRemarks) {
            Swal.fire({
                title: 'Are you sure to continue?',
                text: 'Please provide remarks before proceeding.',
                input: 'textarea',
                inputPlaceholder: 'Enter your remarks here...',
                showCancelButton: true,
                confirmButtonText: 'Submit',
                cancelButtonText: 'Cancel',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Remarks are required.';
                    }
                }
            }).then(result => {
                if (result.isConfirmed) {
                    processRequest(result.value);
                }
            });
        } else {
            confirmAction(
                'Are you sure to continue?',
                'Once performed, this action is permanent and cannot be undone',
                'Yes, proceed!',
                () => processRequest()
            );
        }
    });
}

export function post(url, hasRemarks = false) {
    handleFormSubmit('POST', url, hasRemarks);
}

export function put(url, hasRemarks = false) {
    handleFormSubmit('PUT', url, hasRemarks);
}

export function remove(url) {
    const id = $(this).data('id');
    confirmAction(
            'Delete Record?',
            'This action cannot be undone!',
            'Yes, delete it!',
            () => {
                axios.delete(url)
                    .then(res => {
                        alert('success', 'Record deleted successfully');
                    })
                    .catch(err => {
                        alert('error', 'Failed to delete record');
                    });
            }
        );
}
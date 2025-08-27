import { alert, fieldError} from './helper';

export function post(url) {
    $('#form').on('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this);
        formData.append('_method', 'POST');

        const $btn = $('#btn-submit');
        $btn.prop('disabled', true).text('Please Wait...'); 
        
        axios.post(url, 
            formData, {
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
    });
}


export function put(url) {
    $('#form').on('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this);
        formData.append('_method', 'PUT');

        const $btn = $('#btn-submit');
        $btn.prop('disabled', true).text('Please Wait...'); 
        
        axios.post(url, 
            formData, {
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
    });
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
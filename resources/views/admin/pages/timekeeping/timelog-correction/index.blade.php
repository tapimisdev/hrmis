@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
@include('admin.pages.timekeeping.timelog-correction.modal')
<div class="container-fluid">
    <x-header title="Timelog Correction Request" subtitle="Manage shift scheduling in this module">

    </x-header>

    <div class="row mb-3">
        <div class="col-md-3">
            <label for="filter-month" class="form-label">Month</label>
            <select id="filter-month" class="form-select">
                @foreach(range(1,12) as $m)
                <option value="{{ $m }}" {{ $m == date('n') ? 'selected' : '' }}>
                    {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label for="filter-year" class="form-label">Year</label>
            <select id="filter-year" class="form-select">
                @foreach(range(date('Y'), date('Y') - 5) as $y)
                <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>
                    {{ $y }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="filter-status" class="form-label">Status</label>
            <select id="filter-status" class="form-select">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
    </div>

    <x-table id="myTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Reference No</th>
                <th>Name</th>
                <th>Date</th>
                <th>Status</th>
                <th style="width: 120px">Action</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </x-table>
</div>
@endsection

@section('scripts')
<script>
    $(function() {

        let DataTable = $('#myTable').DataTable({
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: {
                url: '{{ route('timelogs-correction.index') }}',
                data: function(d) {

                    const urlParams = new URLSearchParams(window.location.search);
                    const viewID = urlParams.get('id');

                    d.view_id = viewID; 
                    d.month = $('#filter-month').val();
                    d.year = $('#filter-year').val();
                    d.status = $('#filter-status').val();
                }
            },
            columns: [
                { data: "id", name: 'id', visible: false },
                { data: "reference_no", name: 'reference_no' },
                { data: "name", name: 'name' },
                { data: "date", name: 'date' },
                { data: "status", name: 'status' },
                {
                    data: "actions",
                    name: 'actions',
                    orderable: false,
                    searchable: true
                },
            ],
            columnDefs: [
                {
                    targets: [1,2,3,4,5],
                    className: 'min-table-width'
                }
            ],
            scrollX: true,
            autoWidth: false
        });

        const urlParams = new URLSearchParams(window.location.search);
        const targetID = urlParams.get('id');

        let triggered = false;

        DataTable.on('draw', function () {

            if (!triggered && targetID) {

                const button = $(`.show-button[data-id="${targetID}"]`);

                if (button.length) {
                    triggered = true;
                    button.trigger('click');
                }

            }

        });

        const tcrModal = $('#tcrModal');

        $('#filter-month, #filter-year, #filter-status').on('change', function() {
            DataTable.ajax.reload();
        });

        let id;

        $(document).on('click', '.show-button', function() {
            id = $(this).data('id');

            $('.approve-button, .reject-button').attr('data-id', id);

            axios.get(`/admin/timekeeping/timelogs-correction/${id}/edit`)
                .then(response => {
                    const data = response.data;

                    $('#correction-id').val(data.id);

                    $('#reference_no').val(data.reference_no);
                    $('#employee-name').val(`${data.firstname} ${data.middlename || ''} ${data.lastname}`.trim());

                    $('#date').val(data.date);
                    $('#time-in').val(data.time_in ? data.time_in.split(' ')[1] : '');
                    $('#break-out').val(data.break_out ? data.break_out.split(' ')[1] : '');
                    $('#break-in').val(data.break_in ? data.break_in.split(' ')[1] : '');
                    $('#time-out').val(data.time_out ? data.time_out.split(' ')[1] : '');
                    $('#overtime-in').val(data.overtime_in ? data.overtime_in.split(' ')[1] : '');
                    $('#overtime-out').val(data.overtime_out ? data.overtime_out.split(' ')[1] : '');

                    if (data.concern) {
                        const safeId = data.concern.replace(/[^A-Za-z0-9_-]/g, "_"); // replace spaces/special chars
                        const $checkbox = $('#' + safeId);
                        if ($checkbox.length) $checkbox.prop('checked', true);
                    }

                    $('#remarks').text(data.remarks || '---');

                    $('#attachment-pdf, #attachment-img, #attachment-link').addClass('d-none');
                    if (data.attachment) {
                        const ext = data.attachment.split('.').pop().toLowerCase();
                        if (ext === 'pdf') {
                            $('#attachment-pdf').attr('src', data.attachment).removeClass('d-none');
                        } else if (['jpg','jpeg','png','gif'].includes(ext)) {
                            $('#attachment-img').attr('src', data.attachment).removeClass('d-none');
                        } else {
                            $('#attachment-link').attr('href', data.attachment).removeClass('d-none');
                        }
                    } else {
                        $('#attachment-link').attr('href','#').text('No Attachment').removeClass('d-none');
                    }

                    $('#tcrModal').modal('show');

                    if(data.status != 'pending') {
                        $('#tcrModal .modal-footer').remove();
                    }

                })
                .catch(error => {
                    Swal.fire('Oops!', error.response?.data?.message || error.message, 'error');
                });
        });

        $(document).on('click', '.approve-button', function() {
            handleTimelogAction('approve');
        });

        $(document).on('click', '.reject-button', function() {
            handleTimelogAction('reject');
        });

        function handleTimelogAction(action) {
            const actionText = action === 'approve' ? 'approve' : 'reject';

            $('#tcrModal').modal('hide');

            const swalConfig = {
                title: `Are you sure you want to ${actionText} this application?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: `Yes, ${actionText}`,
                cancelButtonText: 'Cancel',
                allowOutsideClick: false
            };

            if (action === 'reject') {
                swalConfig.input = 'textarea';
                swalConfig.inputPlaceholder = 'Enter remarks...';
                swalConfig.inputAttributes = { 'aria-label': 'Remarks', autocapitalize: 'off', rows: 4 };
                swalConfig.preConfirm = (remarks) => {
                    if (!remarks || !remarks.trim()) {
                        Swal.showValidationMessage('Please enter remarks for rejection');
                    }
                    return remarks;
                };
                swalConfig.didOpen = () => {
                    const textarea = Swal.getInput();
                    if (textarea) textarea.focus();
                };
            }
            Swal.fire(swalConfig).then(result => {
                if (!result.isConfirmed) return;

                const payload = { _token: $('input[name="_token"]').val() };
                if (action === 'reject') payload.remarks = result.value;

                axios.post(`/admin/timekeeping/timelogs-correction/${id}/${action}`, payload)
                    .then(response => {
                        Swal.fire({
                            icon: 'success',
                            title: action === 'approve' ? 'Approved' : 'Rejected',
                            text: `Timelog correction has been ${action === 'approve' ? 'approved' : 'rejected'}.`
                        });
                        DataTable.ajax.reload();
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.response?.data?.message || 'Something went wrong!'
                        });
                    });
            });
        }

        $(document).on('click', '.btn-close-action', function () {
            let DataTable = $('#myTable').DataTable();

            const url = new URL(window.location);
            const hasId = url.searchParams.has('id');

            if (hasId) {
                DataTable.search('').draw();
                url.searchParams.delete('id');
                window.history.replaceState({}, document.title, url.toString());
            }
        });
    });
</script>
@endsection
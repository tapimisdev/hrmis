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
                <th>#</th>
                <th>Reference No</th>
                <th>Employee No</th>
                <th>Name</th>
                <th>Date</th>
                <th>status</th>
                <th>Applied at</th>
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
            ajax: {
                url: '{{ route('timelogs-correction.index') }}',
                data: function(d) {
                    d.month = $('#filter-month').val(); // defaults to current month
                    d.year = $('#filter-year').val();   // defaults to current year
                    d.status = $('#filter-status').val(); // optional
                }
            },
            columns: [{
                    data: "DT_RowIndex",
                    name: 'index'
                },
                {
                    data: "reference_no",
                    name: 'reference_no'
                },
                {
                    data: "employee_no",
                    name: 'employee_no'
                },
                {
                    data: "name",
                    name: 'name'
                },
                {
                    data: "date",
                    name: 'date'
                },
                {
                    data: "status",
                    name: 'status'
                },
                {
                    data: "applied_at",
                    name: 'applied_at'
                },
                {
                    data: "actions",
                    name: 'actions',
                    orderable: false,
                    searchable: false
                },
            ],
            scrollX: true,
            autoWidth: false
        });

        const tcrModal = $('#tcrModal');

        $('#filter-month, #filter-year, #filter-status').on('change', function() {
            DataTable.ajax.reload();
        });

        let id;

        $(document).on('click', '.show-button', function() {
            id = $(this).data('id');

            axios.get(`/admin/timekeeping/timelogs-correction/${id}/edit`)
                .then(response => {
                    const data = response.data;

                    // Hidden ID
                    $('#correction-id').val(data.id);

                    // View-only fields
                    $('#reference_no').val(data.reference_no);
                    $('#employee-name').val(`${data.firstname} ${data.middlename || ''} ${data.lastname}`.trim());

                    // Editable fields
                    $('#date').val(data.date);
                    $('#time-in').val(data.time_in ? data.time_in.split(' ')[1] : '');
                    $('#break-out').val(data.break_out ? data.break_out.split(' ')[1] : '');
                    $('#break-in').val(data.break_in ? data.break_in.split(' ')[1] : '');
                    $('#time-out').val(data.time_out ? data.time_out.split(' ')[1] : '');
                    $('#overtime-in').val(data.overtime_in ? data.overtime_in.split(' ')[1] : '');
                    $('#overtime-out').val(data.overtime_out ? data.overtime_out.split(' ')[1] : '');
                    $('#status').val(data.status);

                    // Remarks
                    $('#remarks').text(data.remarks || '---');

                    // Attachment
                    if (data.attachment) {
                        const ext = data.attachment.split('.').pop().toLowerCase();

                        $('#attachment-pdf, #attachment-img, #attachment-link').addClass('d-none');

                        if (ext === 'pdf') {
                            $('#attachment-pdf').attr('src', data.attachment).removeClass('d-none');
                        } else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                            $('#attachment-img').attr('src', data.attachment).removeClass('d-none');
                        } else {
                            $('#attachment-link').attr('href', data.attachment).removeClass('d-none');
                        }
                    } else {
                        $('#attachment-link').attr('href', '#').text('No Attachment').removeClass('d-none');
                    }

                    // Show modal
                    $('#tcrModal').modal('show');
                })
                .catch(error => {
                    Swal.fire('Oops!', error.response?.data?.message || error.message, 'error');
                });
        });


        // Approve function
        $('.approve-button').click(function() {
            axios.post(`/admin/timekeeping/timelogs-correction/${id}/approve`, {
                _token: $('input[name="_token"]').val()
            })
            .then(response => {
                Swal.fire({
                    icon: 'success',
                    title: 'Approved',
                    text: 'Timelog correction has been approved.',
                });
                $('#tcrModal').modal('hide');
                // optionally reload your datatable or list
                DataTable.ajax.reload();
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.response.data.message || 'Something went wrong!',
                });
            });
        });


        // Approve function
        $('.reject-button').click(function() {
            axios.post(`/admin/timekeeping/timelogs-correction/${id}/reject`, {
                _token: $('input[name="_token"]').val()
            })
            .then(response => {
                Swal.fire({
                    icon: 'success',
                    title: 'Approved',
                    text: 'Timelog correction has been rejected.',
                });
                $('#tcrModal').modal('hide');
                // optionally reload your datatable or list
                DataTable.ajax.reload();
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.response.data.message || 'Something went wrong!',
                });
            });
        });




    });
</script>
@endsection
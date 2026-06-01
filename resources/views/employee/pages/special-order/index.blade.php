@extends('employee.layout.app')

@section('content')
@include('employee.pages.special-order.show')

<div class="container-fluid min-vh-100">
    <x-employee-navbar>
        <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
    </x-employee-navbar>

    <x-header-employee title="Special Order" subtitle="Manage special order applications in this module">
        @can('emp.special_order_application.apply')
            <a href="{{ route('special-order.create') }}" class="btn btn-warning py-3 px-4">
                <i class="fa-solid fa-paper-plane me-2"></i> Apply
            </a>
        @endcan
    </x-header-employee>

    <div class="card rounded-4 p-3">
        <table class="table table-sm table-striped" id="myTable">
            <thead class="text-uppercase">
                <tr>
                    <th>ID</th>
                    <th>SO No.</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function() {
        let DataTable = $('#myTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('special-order.index') }}',
            columns: [
                { data: 'id', name: 'id', visible: false },
                { data: 'so_no', name: 'so_no' },
                { data: 'date', name: 'date' },
                { data: 'status_badge', name: 'status_badge' },
                { data: 'actions', name: 'actions', orderable: false },
            ],
            columnDefs: [{
                targets: '_all',
                className: 'min-table-width',
                render: function(data) {
                    return data ?? '';
                }
            }],
            scrollX: true,
            autoWidth: false
        });

        $(document).on('click', '.cancel-button', function() {
            const id = $(this).attr('data-id');

            Swal.fire({
                title: 'Are you sure?',
                text: 'This special order application will be cancelled.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, cancel it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/employee/special-order/${id}`)
                        .then(() => {
                            DataTable.ajax.reload();
                            Swal.fire('Cancelled!', 'Your special order application has been cancelled.', 'success');
                        })
                        .catch((error) => {
                            Swal.fire('Oops!', error.response?.data?.message || 'Something went wrong.', 'error');
                        });
                }
            });
        });

        $(document).on('click', '.show-button', function () {
            const id = $(this).attr('data-id');
            $('.modal-title').html('Special Order Application');

            axios.get(`/employee/special-order/${id}`)
                .then((response) => {
                    const data = response.data.data;

                    $('#doc-id').text(data.so_no ?? 'N/A');
                    $('#employee-no').text(data.employee_no ?? 'N/A');
                    $('#employee-name').text(data.employee_name ?? data.name ?? 'N/A');
                    $('#within-metro-manila').text(data.within_metro_manila ? 'Yes' : 'No');
                    $('#is-hazardous').text(data.isHazardous ? 'Yes' : 'No');
                    $('#remarks-text').text(data.remarks || '-');

                    if (Array.isArray(data.details) && data.details.length > 0) {
                        const listItems = data.details.map(d => {
                            const dayName = moment(d.date).format('dddd');
                            const dateFormatted = moment(d.date).format('MMM DD, YYYY');
                            const shift = d.shift ?? 'N/A';
                            return `<li>${dateFormatted} - (${dayName}) - [ ${shift} ]</li>`;
                        }).join('');

                        $('#selectedDates').html(`<ul class="mb-0">${listItems}</ul>`);
                    } else {
                        $('#selectedDates').html('<ul><li>N/A</li></ul>');
                    }

                    let statusClass = 'bg-secondary';
                    if (data.status === 'pending') statusClass = 'bg-warning';
                    else if (data.status === 'approved') statusClass = 'bg-success';
                    else if (data.status === 'rejected') statusClass = 'bg-danger';
                    else if (data.status === 'cancelled') statusClass = 'bg-dark';

                    $('#status')
                        .attr('class', 'badge ' + statusClass)
                        .text(data.status.charAt(0).toUpperCase() + data.status.slice(1));

                    $('#created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));
                    $('#attachments ul').empty();

                    if (data.attachments && data.attachments.length > 0) {
                        data.attachments.forEach(file => {
                            $('#attachments ul').append(
                                `<li><a download href="/storage/${file.file_path}" target="_blank" rel="noopener noreferrer">${file.file_name}</a></li>`
                            );
                        });
                    } else {
                        $('#attachments ul').append('<li><em>No attachments</em></li>');
                    }

                    $('#myModal').modal('show');
                })
                .catch((error) => {
                    Swal.fire('Oops!', error.message, 'error');
                });
        });
    });
</script>
@endsection

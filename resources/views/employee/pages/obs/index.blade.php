@extends('employee.layout.app')

@section('content')
@include('employee.pages.obs.show') {{-- modal partial for viewing details --}}

<div class="container-fluid min-vh-100">

    <x-employee-navbar>
        <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
    </x-employee-navbar>

    <x-header-employee title="Pass Slip" subtitle="Manage pass slip applications in this module">
        @can('emp.pass_slip_application.apply')
            <a href="{{ route('obs.create') }}" class="btn btn-warning py-3 px-4">
                <i class="fa-solid fa-paper-plane me-2"></i> Apply
            </a>
        @endcan
    </x-header-employee>

    <div class="card rounded-4 p-3">
        <table class="table table-sm table-striped" id="myTable">
            <thead class="text-uppercase">
                <tr>
                    <th>ID</th>
                    <th>File No.</th>
                    <th>Dates</th>
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
    $(document).ready(function() {
        let DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('obs.index') }}',
            "columns": [
                { data: "id", name: 'id', visible: false },
                { data: "application_no", name: 'application_no' },
                { data: "date", name: 'date' },
                { data: "status", name: 'status', orderable: false },
                { data: "actions", name: 'actions', orderable: false },
            ],
            "columnDefs": [
                {
                    targets: "_all",
                    className: "min-table-width",
                    render: function(data, type, row, meta) {
                        return data ?? "";
                    }
                }
            ],
            "scrollX": true,
            "autoWidth": false
        });

        const urlParams = new URLSearchParams(window.location.search);
        const show = urlParams.get('show');
        const id = urlParams.get('id');

        let triggered = false; 

        DataTable.on('draw', function() {
            if (!triggered && show === 'true' && id) {
                triggered = true;

                DataTable.search(id).draw();

                $('#myTable_filter input').val('');

                DataTable.one('draw', function() {
                    const button = $(`.show-button[data-id="${id}"]`);
                    if (button.length) {
                        button.trigger('click');
                    }
                });
            }
        });

        // Cancel OBS
        $(document).on('click', '.cancel-button', function() {
            let id = $(this).attr('data-id');
            Swal.fire({
                title: "Are you sure?",
                text: "This OBS will be cancelled.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, cancel it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/employee/official-business-slip/${id}`)
                        .then(response => {
                            DataTable.ajax.reload();
                            Swal.fire({
                                title: "Cancelled!",
                                text: "Your official business slip has been cancelled.",
                                icon: "success"
                            });
                        })
                        .catch(error => {
                            Swal.fire({
                                title: "Oops!",
                                text: "Something went wrong, try again later!",
                                icon: "error"
                            });
                        })
                }
            });
        });

        // Show modal
        const myModal = $('#myModal');

        $(document).on('click', '.show-button', function () {
            let id = $(this).data('id');
            $('.modal-title').html('Pass Slip');

            axios.get(`/employee/pass-slip/${id}`)
                .then((response) => {
                    const data = response.data.data;

                    $('#doc-id').text(data.application_no);
                    $('#employee-no').text(data.employee_no ?? 'N/A');
                    $('#employee-name').text(data.employee_name ?? 'N/A');
                    $('#remarks').text(data.remarks ?? '—');

                     // Format and display dates
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

                    $('#reason').text(data.reason);

                    if ((data.status ?? '').toLowerCase().trim() !== 'approved') {
                        $('.extended').removeClass('d-none');
                    } else {
                        $('.extended').addClass('d-none');
                    }

                    $('#remarks').text(data.remarks);

                    let statusClass = 'bg-secondary';
                    if (data.status === 'pending') statusClass = 'bg-warning';
                    else if (data.status === 'approved') statusClass = 'bg-success';
                    else if (data.status === 'rejected') statusClass = 'bg-danger';
                    else if (data.status === 'cancelled') statusClass = 'bg-dark';

                    $('#status')
                        .attr('class', 'badge ' + statusClass)
                        .text(data.status.charAt(0).toUpperCase() + data.status.slice(1));

                    $('#created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));

                    const levelApprovals = data.level_approvals ?? {};
                    const sortedLevels = Object.keys(levelApprovals).map(Number).sort((a, b) => a - b);
                    $('#approval-breadcrumbs').empty();

                    let breadcrumbHTML = `
                        <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
                            <ol class="breadcrumb">
                    `;

                    const statusClasses = {
                        approved: 'text-success fw-bold',
                        rejected: 'text-danger fw-bold',
                        pending: 'text-warning fw-bold'
                    };

                    sortedLevels.forEach(level => {
                        const status = levelApprovals[level]?.toLowerCase() || 'unknown';
                        const colorClass = statusClasses[status] ?? 'text-muted';

                        breadcrumbHTML += `
                            <li class="breadcrumb-item ${colorClass}">
                                Level ${level} - ${status.charAt(0).toUpperCase() + status.slice(1)}
                            </li>
                        `;
                    });

                    breadcrumbHTML += `
                            </ol>
                        </nav>
                    `;

                    $('#approval-breadcrumbs').html(breadcrumbHTML);

                    // === Approvers by Level (Accordion) ===
                    $('#approvers-by-level').empty();
                    const approvals = data.approvals;

                    if (approvals && Object.keys(approvals).length > 0) {
                        let accordionHTML = `<div class="accordion" id="obsApproversAccordion">`;

                        Object.keys(approvals).sort((a, b) => a - b).forEach((level, index) => {
                            const approverArray = approvals[level];
                            const levelStatus = levelApprovals[level]
                                ? levelApprovals[level].charAt(0).toUpperCase() + levelApprovals[level].slice(1)
                                : 'Unknown';

                             const approverList = approverArray.length > 0
                                ? approverArray.map(a => {
                                    let statusLabel = '';

                                    switch (a.status) {
                                        case 'approved':
                                        case 'rejected': 
                                            statusLabel = '<i class="fa-solid fa-circle-check text-primary"></i>';
                                            break;
                                    }

                                    return `<li><code>${a.firstname} ${a.lastname} (${a.employee_no})</code> <span class="ms-1 text-primary">${statusLabel}</span></li>`;
                                }).join('')
                                : '<li><em>None</em></li>';

                            accordionHTML += `
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-${level}">
                                        <button class="accordion-button fw-bold text-uppercase ${index !== 0 ? 'collapsed' : ''}"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#collapse-${level}"
                                            aria-expanded="${index === 0 ? 'true' : 'false'}"
                                            aria-controls="collapse-${level}">
                                            Level ${level} Approvers - ${levelStatus}
                                        </button>
                                    </h2>
                                    <div id="collapse-${level}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}"
                                        aria-labelledby="heading-${level}"
                                        data-bs-parent="#obsApproversAccordion">
                                        <div class="accordion-body">
                                            <ul class="mb-0">
                                                ${approverList}
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });

                        accordionHTML += `</div>`;
                        $('#approvers-by-level').append(accordionHTML);
                    } else {
                        $('#approvers-by-level').append('<div><em>No approvals yet</em></div>');
                    }

                    // === Attachments ===
                    $('#attachments ul').empty();
                    const attachments = data.attachments;

                    if (attachments && attachments.length > 0) {
                        attachments.forEach(file => {
                            let fileUrl = `/storage/${file.file_path}`;
                            let fileName = file.file_name;

                            $('#attachments ul').append(
                                `<li><a download href="${fileUrl}" target="_blank" rel="noopener noreferrer">${fileName}</a></li>`
                            );
                        });
                    } else {
                        $('#attachments ul').append('<li><em>No attachments</em></li>');
                    }

                    // === Show Modal ===
                    myModal.modal('show');
                })
                .catch(error => {
                    Swal.fire({
                        title: "Oops!",
                        text: error.message,
                        icon: "error"
                    });
                });
        });

        $(document).on('click', '.btn-close-action', function() {
            let DataTable = $('#myTable').DataTable();
            DataTable.search('').draw(); 

            const url = new URL(window.location);
            url.searchParams.delete('id');
            url.searchParams.delete('show');
            window.history.replaceState({}, document.title, url.toString());
        });

    });
</script>
@endsection

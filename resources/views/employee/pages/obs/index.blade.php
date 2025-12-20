@extends('employee.layout.app')

@section('content')
@include('employee.pages.obs.show') {{-- modal partial for viewing details --}}

<div class="container-fluid min-vh-100">

    <x-employee-navbar>
        <header-vue title="DOST TAPI"></header-vue>
    </x-employee-navbar>

    <x-header-employee title="Pass Slip" subtitle="Manage pass slip in this module">
        <a href="{{ route('obs.create') }}" class="btn btn-warning py-3 px-4">
            <i class="fa-solid fa-paper-plane me-2"></i> Apply
        </a>
    </x-header-employee>

    <x-table-employee id="myTable">
        <thead>
            <tr>
                <th>File No.</th>
                <th>Name</th>
                <th>Dates</th>
                <th>Destination</th>
                <th>Status</th>
                <th style="width: 120px">Action</th>
            </tr>
        </thead>
        <tbody></tbody>
    </x-table-employee>
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
                { data: "application_no", name: 'application_no' },
                { data: "name", name: 'name' },
                { data: "date_range", name: 'date_range' },
                { data: "destination", name: 'destination' },
                { data: "status", name: 'status', orderable: false, searchable: false },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
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

                    // === Basic Details ===
                    $('#obs-doc-id').text(data.application_no ?? '—');
                    $('#obs-destination').text(data.destination ?? '—');
                    $('#obs-purpose').text(data.purpose ?? '—');
                    $('#obs-time-out').text(data.time_out ?? '—');
                    $('#obs-time-in').text(data.time_in ?? '—');
                    $('#obs-transport').text(data.mode_of_transport ?? '—');
                    $('#obs-expense').text(data.estimated_expense ? `₱${parseFloat(data.estimated_expense).toFixed(2)}` : '—');
                    $('#obs-charge-to').text(data.charge_to ?? '—');
                    $('#obs-remarks').text(data.remarks ?? '—');

                    // === Dates ===
                    $('#obs-date-from').text(moment(data.date_from).format('MMMM D, YYYY'));
                    $('#obs-date-to').text(moment(data.date_to).format('MMMM D, YYYY'));

                    // === Status Badge ===
                    let statusClass = 'bg-secondary';
                    if (data.status === 'pending') statusClass = 'bg-warning';
                    else if (data.status === 'approved') statusClass = 'bg-success';
                    else if (data.status === 'rejected') statusClass = 'bg-danger';
                    else if (data.status === 'cancelled') statusClass = 'bg-dark';

                    $('#obs-status')
                        .attr('class', 'badge ' + statusClass)
                        .text(data.status.charAt(0).toUpperCase() + data.status.slice(1));

                    $('#obs-created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));
                    $('#obs-approved-at').text(data.approved_at ? moment(data.approved_at).format('MMMM D, YYYY h:mm A') : '—');
                    $('#obs-approver').text(data.approver ?? 'Not Yet Assigned');

                    // === Approval Breadcrumbs ===
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
                    $('#obs-attachments ul').empty();
                    const attachments = data.attachments;

                    if (attachments && attachments.length > 0) {
                        attachments.forEach(file => {
                            let fileUrl = `/storage/${file.file_path}`;
                            let fileName = file.file_name;

                            $('#obs-attachments ul').append(
                                `<li><a download href="${fileUrl}" target="_blank" rel="noopener noreferrer">${fileName}</a></li>`
                            );
                        });
                    } else {
                        $('#obs-attachments ul').append('<li><em>No attachments</em></li>');
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



    });
</script>
@endsection

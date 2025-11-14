@extends('employee.layout.app')

@section('content')
@include('employee.pages.atro.show')
    <div class="container-fluid">
        <x-employee-navbar>
            <header-vue title="DOST TAPI"></header-vue>
        </x-employee-navbar>

        <x-header-employee title="Overtime" subtitle="Manage overtime in this module" >
            <a href="{{ route('overtime.create') }}" class="btn btn-warning py-3 px-4">
                <i class="fa-solid fa-paper-plane me-2"></i> File Overtime
            </a>
        </x-header-employee>

        <x-table-employee id="myTable">
            <thead>
                <tr>
                    <th>File No.</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Total Hours</th>
                    <th>Status</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </x-table-employee>
    </div>
@endsection

@section('scripts')

<script>
    $(document).ready(function() {
        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('overtime.index') }}',
            "columns": [
                { data: "application_no", name: 'application_no' },
                { data: "name", name: 'name' },
                { data: "date", name: 'date' },
                { data: "total_hours", name: 'total_hours' },
                { data: "status", name: 'status' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });

        $(document).on('click', '.cancel-button', function() {
            id = $(this).attr('data-id');
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, cancel it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/employee/overtime/${id}`)
                    .then(response => {
                        DataTable.ajax.reload();
                        Swal.fire({
                            title: "Cancelled!",
                            text: "Your data has been deleted.",
                            icon: "success"
                        });
                    })
                    .catch(error => {
                       Swal.fire({
                            title: "Oops!",
                            text: error.response.data.message,
                            icon: "error"
                        });
                    })
                }
            }); // swal end
        });

        const myModal = $('#myModal');
        $(document).on('click', '.show-button', function () {
            let id = $(this).attr('data-id');
            $('.modal-title').html('Overtime Application');

            axios.get(`/employee/overtime/${id}`)
                .then((response) => {
                    const data = response.data.data;

                    // Fill in modal fields
                    $('#doc-id').text(data.application_no);
                    $('#date').text(moment(data.date).format('MMMM D, YYYY'));
                    $('#start-time').text(moment(data.start_time, 'HH:mm:ss').format('h:mm A'));
                    $('#end-time').text(moment(data.end_time, 'HH:mm:ss').format('h:mm A'));
                    $('#total-hours').text(data.total_hours);
                    $('#reason').text(data.reason);

                    // Status badge
                    let statusClass = 'bg-secondary';
                    if (data.status === 'pending') statusClass = 'bg-warning';
                    else if (data.status === 'approved') statusClass = 'bg-success';
                    else if (data.status === 'rejected') statusClass = 'bg-danger';

                    $('#status')
                        .attr('class', 'badge ' + statusClass)
                        .text(data.status.charAt(0).toUpperCase() + data.status.slice(1));

                    $('#created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));
                    $('#approver').text(data.approver_id ?? 'Not Yet Assigned');
                    $('#approved-at').text(data.approved_at
                        ? moment(data.approved_at).format('MMMM D, YYYY h:mm A')
                        : '---');

                    // === Approval Breadcrumbs ===
                    const levelApprovals = data.level_approvals ?? {};
                    const sortedLevels = Object.keys(levelApprovals).map(Number).sort((a, b) => a - b);

                    $('#approval-breadcrumbs').empty();

                    let breadcrumbHTML = `
                        <nav style="--bs-breadcrumb-divider: url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%278%27 height=%278%27%3E%3Cpath d=%27M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z%27 fill=%276c757d%27/%3E%3C/svg%3E');" aria-label="breadcrumb">
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
                        let accordionHTML = `<div class="accordion" id="approversAccordion">`;

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
                                        <button style="font-size: 12px;" class="accordion-button fw-bold text-uppercase d-flex justify-content-between ${index !== 0 ? 'collapsed' : ''}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${level}" aria-expanded="${index === 0 ? 'true' : 'false'}" aria-controls="collapse-${level}">
                                            Level ${level} Approvers
                                        </button>
                                    </h2>
                                    <div id="collapse-${level}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" aria-labelledby="heading-${level}" data-bs-parent="#approversAccordion">
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

                    // Attachments
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

                    // Show modal
                    $('#myModal').modal('show');
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
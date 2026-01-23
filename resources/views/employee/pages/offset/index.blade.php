@extends('employee.layout.app')

@section('content')
@include('employee.pages.offset.show')
    <div class="container-fluid min-vh-100">
        
        <x-employee-navbar>
            <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
        </x-employee-navbar>

        <x-header-employee title="Offset Applications" subtitle="Manage offset applications in this module" >
            <a href="{{ route('offset.create') }}" class="btn btn-warning py-3 px-4">
                <i class="fa-solid fa-paper-plane me-2"></i> Apply
            </a>
        </x-header-employee>

        <div class="card rounded-4 p-3">
            <table class="table table-sm table-striped" id="myTable">
                <thead class="text-uppercase">
                    <tr>
                        <th>File No.</th>
                        <th>Name</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th style="width: 120px">Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {
        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('offset.index') }}',
            "columns": [
                { data: "application_no", name: 'application_no' },
                { data: "name", name: 'name' },
                { data: "date", name: 'date' },
                { data: "status", name: 'status' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],  
            columnDefs: [
                {
                    targets: "_all",
                    className: "min-table-width",
                    render: function(data, type, row, meta) {
                        return data ?? "";
                    }
                }
            ],
            scrollX: true,
            autoWidth: false
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
                    axios.delete(`/employee/offset/${id}`)
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
            $('.modal-title').html('Offset Application');

            axios.get(`/employee/offset/${id}`)
                .then((response) => {
                    const data = response.data.data;

                    // Fill in modal fields
                    $('#doc-id').text(data.application_no);
                    $('#employee-no').text(data.employee_no ?? 'N/A');
                    $('#leave-type').text(data.leave_name);

                    // Format and display dates
                    if (Array.isArray(data.dates) && data.dates.length > 0) {
                        const formattedDates = data.dates
                            .map(date => moment(date).format('MMM D'))
                            .join(', ');
                        const year = moment(data.dates[0]).format('YYYY');
                        $('#selectedDates').text(`${formattedDates} ${year}`);
                    } else {
                        $('#selectedDates').text('N/A');
                    }

                    $('#days').text(data.days);
                    $('#reason').text(data.reason);

                    if (data.status === 'rejected') {
                        $('.extended').removeClass('d-none');
                    }

                    $('#remarks').text(data.remarks);

                    // Status badge
                    let statusClass = 'bg-secondary';
                    if (data.status === 'pending') statusClass = 'bg-warning';
                    else if (data.status === 'approved') statusClass = 'bg-success';
                    else if (data.status === 'rejected') statusClass = 'bg-danger';

                    $('#status')
                        .attr('class', 'badge ' + statusClass)
                        .text(data.status.charAt(0).toUpperCase() + data.status.slice(1));

                    $('#created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));

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

                    // Approved at
                    $('#approved-at').text(data.approved_at
                        ? moment(data.approved_at).format('MMMM D, YYYY h:mm A')
                        : '---');

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
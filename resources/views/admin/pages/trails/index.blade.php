@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <x-header title="Log Trails" subtitle="View actions and trails made in HRIS">
        <x-button-link 
            :href="route('hris.employee.index')" 
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
        />
    </x-header>

    {{-- Main Table Card --}}
    <div class="card">
        <div class="card-body">
            <table id="trailsTable" class="table table-striped table-bordered w-100">
                <thead>
                    <tr>
                        {{-- Expand / Collapse Control Column --}}
                        <th style="width: 20px;"></th>

                        {{-- Primary Visible Columns --}}
                        <th>Actioned By ID</th>
                        <th>Actioned By Name</th>
                        <th>Method</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {

    /**
     * Format function for expandable child row
     * Displays additional audit trail details in a structured layout
     *
     * @param {Object} row - Row data from DataTable
     * @returns {String} HTML content for child row
     */
    function format(row) {
        let payload = '';

        // Attempt to safely parse JSON payload
        try {
            payload = row.payload 
                ? JSON.stringify(JSON.parse(row.payload), null, 2)
                : '';
        } catch (e) {
            payload = row.payload ?? '';
        }

        return `
            <div class="p-3 bg-light">
                <table class="table table-sm table-bordered mb-0">
                    <tr>
                        <td width="180"><strong>Log ID</strong></td>
                        <td>${row.id ?? ''}</td>
                    </tr>
                    <tr>
                        <td><strong>Controller</strong></td>
                        <td>${row.controller ?? ''}</td>
                    </tr>
                    <tr>
                        <td><strong>Payload</strong></td>
                        <td>
                            <pre class="mb-0" style="white-space: pre-wrap;">${payload}</pre>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>IP Address</strong></td>
                        <td>${row.ip_address ?? ''}</td>
                    </tr>
                    <tr>
                        <td><strong>User Agent</strong></td>
                        <td style="word-break: break-all;">${row.user_agent ?? ''}</td>
                    </tr>
                    <tr>
                        <td><strong>Created At</strong></td>
                        <td>${row.created_at ?? ''}</td>
                    </tr>
                </table>
            </div>
        `;
    }

    /**
     * Initialize DataTable with server-side processing
     */
    let table = $('#trailsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("trails.index") }}',
        pageLength: 25,
        lengthMenu: [
            [25, 50, 100, 200],
            [25, 50, 100, 200]
        ],
        columns: [
            {
                // Expand / Collapse trigger column
                className: 'dt-control text-center',
                orderable: false,
                searchable: false,
                data: null,
                defaultContent: ''
            },
            { data: 'actioned_by_id', name: 'actioned_by_id' },
            { data: 'actioned_by_name', name: 'actioned_by_name' },
            { 
                data: 'method', 
                name: 'method',

                /**
                 * Render HTTP method with color-coded badge
                 */
                render: function(data) {
                    let color = 'secondary';

                    if (data === 'POST') color = 'success';
                    else if (data === 'PUT' || data === 'PATCH') color = 'warning';
                    else if (data === 'DELETE') color = 'danger';
                    else if (data === 'GET') color = 'primary';

                    return `<span class="badge bg-${color}">${data}</span>`;
                }
            },
            { data: 'description', name: 'description' },
        ],

        // Default sorting by Actioned By ID (descending)
        order: [[1, 'desc']],

        // Ensure null values render as empty strings
        columnDefs: [
            {
                targets: "_all",
                render: function(data) {
                    return data ?? "";
                }
            }
        ],

        responsive: false,
        autoWidth: false
    });

    /**
     * Handle row expand/collapse behavior
     * Implements accordion-style interaction:
     * - Only one row can be expanded at a time
     * - Opening a row automatically closes others
     */
    $('#trailsTable tbody').on('click', 'td.dt-control', function () {
        let tr = $(this).closest('tr');
        let row = table.row(tr);
        let icon = $(this).find('i');

        // If row is already open → close it
        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');

        } else {

            // Close any other open rows (accordion behavior)
            table.rows().every(function () {
                if (this.child.isShown()) {
                    $(this.node()).removeClass('shown');
                    $(this.node()).find('td.dt-control i')
                        .removeClass('fa-chevron-up text-danger')
                        .addClass('fa-chevron-down text-primary');
                    this.child.hide();
                }
            });

            // Open the selected row
            row.child(format(row.data())).show();
            tr.addClass('shown');
        }
    });

});
</script>
@endsection
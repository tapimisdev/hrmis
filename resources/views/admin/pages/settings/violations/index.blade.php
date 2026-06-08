@extends('admin.layouts.app')

@section('content')
@include('admin.pages.settings.violations.show')
    <div class="container-fluid">
        <x-header title="Violations" subtitle="Manage attendance violation sanction rules in this module">
            <x-button-link
                :href="route('settings.violations.create')"
                icon="fa-solid fa-plus"
                text="Add Violation"
                variant="primary"
            />
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>Behavioral Type</th>
                    <th>Threshold</th>
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
        $('#myTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('settings.violations.index') }}',
            columns: [
                { data: 'violation_type', name: 'violation_type' },
                { data: 'threshold', name: 'threshold' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false },
            ],
            columnDefs: [
                {
                    targets: '_all',
                    className: 'min-table-width',
                    render: function(data) {
                        return data ?? '';
                    }
                }
            ],
            scrollX: true,
            autoWidth: false
        });

        const myModal = $('#myModal');

        $(document).on('click', '.show-button', function() {
            const id = $(this).attr('data-id');
            $('.modal-title').html('Violation Details');

            axios.get(`violations/${id}`)
                .then((response) => {
                    const data = response.data.violation;

                    $('#violation-id').text(data.id);
                    $('#violation-type').text(data.violation_type);
                    $('#violation-rule-trigger').text(data.rule_trigger);
                    $('#violation-evaluation-period').text(data.evaluation_period);
                    $('#violation-action-name').text(data.action_name);
                    $('#violation-threshold').text(data.threshold);
                    $('#violation-created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));

                    myModal.modal('show');
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Oops!',
                        text: error.response?.data?.message || error.message,
                        icon: 'error'
                    });
                });
        });
    });
</script>
@endsection

@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Payroll Group" subtitle="Manage Employee Groups">
            <x-button-link 
                :href="route('payroll.group.create')" 
                icon="fa-solid fa-plus" 
                text="Create " 
                variant="primary"
            />
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Employee Count</th>
                    <th>Employment Type</th>
                    <th>Remarks</th>
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

        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('payroll.group.index') }}',
            "autoWidth": false,
            "scrollX": true,

            "columns": [
                { data: "DT_RowIndex", name: 'index', width: "5%" },
                { data: "name", name: 'name', width: "20%" },
                { data: "employee_count", name: 'employee_count', width: "10%" },
                { data: "employment_type_name", name: 'employment_type_name', width: "20%" },
                { data: "remarks", name: 'remarks', width: "25%" },
                { data: "actions", name: 'actions', orderable: false, searchable: false, width: "20%" }
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
        });

        $(document).on('click', '.delete-button', function() {
            id = $(this).attr('data-id');
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/admin/payroll/groups/${id}`)
                    .then(response => {
                        DataTable.ajax.reload();
                        Swal.fire({
                            title: "Deleted!",
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

    });
</script>
@endsection



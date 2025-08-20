@extends('employee.layout.app')

@section('content')
    <div class="container">
        <x-header title="Leave Applications" subtitle="Manage Leave Applications in this module" >
            <a href="javascript:history.back()" class="btn btn-outline-danger py-3 px-4">
                <i class="fa-solid fa-arrow-left me-2"></i> Back
            </a>
            <a href="{{ route('leaves.create') }}" class="btn btn-primary py-3 px-4">
                <i class="fa-solid fa-paper-plane me-2"></i> Apply
            </a>
        </x-header>

        <div class="table-responsive card p-3">
            <table class="table table-hover" id="myTable">
                <thead>
                    <tr>
                        <th style="width: 10px">#</th>
                        <th>Leave Type</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Number of Days</th>
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
    $(document).ready(function() {
        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('leaves.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "leave_type", name: 'leave_type' },
                { data: "date", name: 'date' },
                { data: "status", name: 'status' },
                { data: "days", name: 'days' },
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
                    axios.delete(`/employee/leaves/${id}`)
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
                            text: "Something went wrong, try again later!",
                            icon: "error"
                        });
                    })
                }
            }); // swal end
        });
    });
</script>
@endsection
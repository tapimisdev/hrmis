@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
@include('admin.pages.taxes.salary-taxes.create')
    <div class="container-fluid">
        <x-header title="Salary Taxes" subtitle="Manage shift schedule in this module">
            <x-button 
                id="create-btn"
                variant="primary"
            >
            <i class="fa-solid fa-plus me-1"></i>
             Add Year
            </x-button>
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th style="width: 24px;">#</th>
                    <th>Year</th>
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
            "ajax": '{{ route('tax.salary.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "year", name: 'year' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },

            ],
        });

        const taxSalaryModal = $('#taxSalaryModal');

        $(document).on('click', '#create-btn', function() {
            taxSalaryModal.modal('show');
        });

        const url = $('#myForm').attr('action');
        post(url);

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
                    axios.delete(`shift/${id}`)
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



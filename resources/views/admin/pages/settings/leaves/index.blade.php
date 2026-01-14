@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
@include('admin.pages.settings.leaves.show')
    <div class="container-fluid">
        <x-header title="Leaves" subtitle="Manage leave in this module">
            <x-button-link 
                :href="route('settings.leaves.create')" 
                icon="fa-solid fa-plus" 
                text="Add Leave" 
                variant="primary"
            />
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Is Cumulative</th>
                    <th>Deduction</th>
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
            "ajax": '{{ route('settings.leaves.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "name", name: 'name' },
                { data: "is_cumulative", name: 'is_cumulative' },
                { data: "credit_to_deduct", name: 'credit_to_deduct' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
            "scrollX": true,
            "autoWidth": false
        });

        const myModal = $('#myModal');

        $(document).on('click', '.show-button', function() {
            let id = $(this).attr('data-id');
            $('.modal-title').html('Deduction Details');

            axios.get(`leaves/${id}`)
                .then((response) => {
                    const data = response.data.leave; // adjust according to your API response

                    // Fill modal fields
                    $('#leave-id').text(data.id);
                    $('#leave-name').text(data.name);
                    $('#leave-is-cumulative').text(data.is_cumulative ? 'Yes' : 'No');
                    $('#leave-deduction').text(data.credit_to_deduct);
                    $('#leave-created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));

                    // Show modal
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
                    axios.delete(`leaves/${id}`)
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
                            text: error.response?.data?.message || "Something went wrong.",
                            icon: "error"
                        });
                    })
                }
            }); // swal end
        });

    });
</script>
@endsection



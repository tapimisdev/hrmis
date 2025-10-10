@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
@include('admin.pages.settings.deductions.show')
    <div class="container pt-4 px-3">
        <x-header title="Deductions" subtitle="Manage deductions in this module">
            <x-button-link 
                :href="route('deductions.create')" 
                icon="fa-solid fa-plus" 
                text="Add Deductions" 
                variant="primary"
            />
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>1st Term</th>
                    <th>2nd Term</th>
                    <th style="width: 120px">Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </x-table>
    </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script>
    $(function() {

        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('deductions.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "name", name: 'name' },
                { data: "first_term", name: 'first_term' },
                { data: "second_term", name: 'second_term' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },

            ],
        });

        const myModal = $('#myModal');

        $(document).on('click', '.show-button', function() {
            let id = $(this).attr('data-id');
            $('.modal-title').html('Deduction Details');

            axios.get(`deductions/${id}`)
                .then((response) => {
                    const data = response.data.deduction; // adjust according to your API response
                    console.log(data);

                    // Fill modal fields
                    $('#deduction-id').text(data.id);
                    $('#deduction-name').text(data.name);
                    $('#deduction-first-term').text(data.first_term);
                    $('#deduction-second-term').text(data.second_term);
                    $('#deduction-created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));

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
                    axios.delete(`deductions/${id}`)
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



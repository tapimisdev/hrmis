@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
@include('admin.pages.settings.deductions.show')
    <div class="container-fluid">
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
       

    });
</script>
@endsection



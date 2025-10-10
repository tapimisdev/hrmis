@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
@include('admin.pages.settings.earnings.show')
    <div class="container pt-4 px-3">
        <x-header title="Earnings" subtitle="Manage earnings in this module">
            <x-button-link 
                :href="route('earnings.create')" 
                icon="fa-solid fa-plus" 
                text="Add Earning" 
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
                    <th>Taxable</th>
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
            "ajax": '{{ route('earnings.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "name", name: 'name' },
                { data: "first_term", name: 'first_term' },
                { data: "second_term", name: 'second_term' },
                { data: "is_taxable", name: 'is_taxable'},
                { data: "actions", name: 'actions', orderable: false, searchable: false },

            ],
        });

        const myModal = $('#myModal');

        $(document).on('click', '.show-button', function() {
            let id = $(this).attr('data-id');
            $('.modal-title').html('Holiday Details');

            axios.get(`earnings/${id}`)
                .then((response) => {
                    const data = response.data.earnings; // adjust according to your API response
                    console.log(data);

                    // Fill modal fields
                    $('#earning-name').text(data.id);
                    $('#earning-first-term').text(data.first_term);
                    $('#earning-second-term').text(data.second_term);
                    $('#earning-is-taxable').attr('class', 'badge ' + (data.is_taxable ? 'bg-success' : 'bg-secondary'))
                        .text(data.is_taxable ? 'Yes' : 'No');
                    $('#earning-created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));

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

        // Helper to format holiday type
        function formatHolidayType(type) {
            switch(type) {
                case 'regular': return 'Regular Holiday';
                case 'special_working': return 'Special Working Day';
                case 'special_non_working': return 'Special Non-working Day';
                case 'company': return 'Company-declared Holiday';
                default: return type;
            }
        }

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
                    axios.delete(`earnings/${id}`)
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



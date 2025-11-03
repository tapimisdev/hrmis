@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
@include('admin.pages.settings.holiday.show')
    <div class="container-fluid">
        <x-header title="Holidays" subtitle="Manage Holiday in this module">
            <x-button-link 
                :href="route('holiday.create')" 
                icon="fa-solid fa-plus" 
                text="Add Holiday" 
                variant="primary"
            />
        </x-header>

        <x-table id="myTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Repeating</th>
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
            "ajax": '{{ route('holiday.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "name", name: 'name' },
                { data: "date", name: 'date' },
                { data: "type", name: 'type' },
                { data: "is_repeating", name: 'is_repeating'},
                { data: "actions", name: 'actions', orderable: false, searchable: false },

            ],
        });

        const holidayModal = $('#holidayModal');

        $(document).on('click', '.show-button', function() {
            let id = $(this).attr('data-id');
            $('.modal-title').html('Holiday Details');

            axios.get(`holiday/${id}`)
                .then((response) => {
                    const data = response.data.holiday; 

                    $('#holiday-id').text(data.id);
                    $('#holiday-name').text(data.name);
                    $('#holiday-date').text(data.date ? moment(data.date).format('MMMM D, YYYY') : '---');
                    $('#holiday-type').text(data.type ? formatHolidayType(data.type) : '---');
                    $('#holiday-no-work-percent').text(data.no_work_rate ? (data.no_work_rate * 100) + '%' : '---');
                    $('#holiday-work-percent').text(data.work_rate ? (data.work_rate * 100) + '%' : '---');
                    $('#holiday-overtime-percent').text(data.overtime_rate ? (data.overtime_rate * 100) + '%' : '---');
                    $('#holiday-is-repeating').attr('class', 'badge ' + (data.is_repeating ? 'bg-success' : 'bg-secondary'))
                        .text(data.is_repeating ? 'Yes' : 'No');
                    $('#holiday-created-at').text(moment(data.created_at).format('MMMM D, YYYY h:mm A'));

                    // Show modal
                    holidayModal.modal('show');
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
                    axios.delete(`holiday/${id}`)
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



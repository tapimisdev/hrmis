@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="Employee List" subtitle="Manage employee's informations in this module" >
            <div class="d-flex flex-wrap gap-3 justify-content-end">
                <!-- Dropdown Button -->
                <div class="dropdown">
                    <button class="btn-modern ghost text-uppercase fw-bold px-5 py-3" 
                            type="button" 
                            id="employeeActionsDropdown" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false">
                        <i class="fa-solid fa-gear me-2"></i> Actions 
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end w-100" aria-labelledby="employeeActionsDropdown">
                        <li>
                            <a class="dropdown-item fw-bold text-uppercase" href="{{ route('hris.import.index') }}">
                                Import
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item fw-bold text-uppercase" href="{{ route('hris.employee.salary') }}">
                                Update Salary
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item fw-bold text-uppercase" href="{{ route('hris.employee.transfer') }}">
                                Transfer Unit
                            </a>
                        </li>
                    </ul>
                </div>
                
                <x-button-link 
                    :href="route('hris.employee.information')" 
                    icon="fa-solid fa-plus" 
                    text="Add Employee" 
                    variant="primary"
                />
            </div>
        </x-header>
        <div class="row mb-3">
            <div class="col-12 col-md-4 mb-3">
                <label for="division" class="mb-3">Filter By Divisions</label>
                <select id="division" class="form-select text-uppercase">
                    <option value=""> - CHOOSE -</option>
                </select>
            </div>
            <div class="col-12 col-md-4 mb-3">
                <label for="units" class="mb-3">Filter By Units</label>
                <select id="units" class="form-select text-uppercase">
                    <option value=""> - CHOOSE -</option>
                </select>
            </div>
             <div class="col-12 col-md-3 mb-3">
                <label for="account_status" class="mb-3">Filter By Account Status</label>
                <select id="account_status" class="form-select text-uppercase">
                    @foreach (['active', 'inactive', 'archived'] as $menu)
                        <option value="{{$menu}}">
                            {{ strtoupper($menu) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card p-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100 pb-3" id="myTable">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Employee No</th>
                                <th>Name</th>
                                <th>Date Hired</th>
                                <th style="width: 120px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {
        let DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                url: '{{ route('hris.employee.index') }}',
                data: function (d) {
                    d.account_status = $('#account_status').val();
                    d.division = $('#division').val();
                    d.unit = $('#units').val();
                }
            },
            "columns": [
                { data: "profile", name: 'profile' },
                { data: "employee_no", name: 'employee_no' },
                { data: "name", name: 'name' },
                { data: "date_hired", name: 'date_hired' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });

        const selectedDivision = "{{ $division_id ?? '' }}";
        const selectedUnit = "{{ $unit_id ?? '' }}";

        const token = localStorage.getItem('auth_token'); // token from local storage

        $.ajax({
            url: '/api/divisions',
            type: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,  // <-- add this
                'Accept': 'application/json'
            },
            success: function (data) {
                $('#division').append(
                    data.map(d =>
                        `<option value="${d.id}" ${d.id == selectedDivision ? 'selected' : ''}>
                            ${d.name.toUpperCase()}
                        </option>`
                    )
                );

                if (selectedDivision) {
                    loadUnits(selectedDivision, selectedUnit);
                }
            }
        });

        $('#division').on('change', function () {
            const divisionId = $(this).val();
            $('#units').empty().append('<option value=""> - CHOOSE UNIT - </option>');

            if (divisionId) {
                loadUnits(divisionId, '');
            }

            DataTable.ajax.reload();
        });

        $('#units').on('change', function () {
            DataTable.ajax.reload();
        });

        $('#account_status').on('change', function () {
            DataTable.ajax.reload();
        });

        function loadUnits(divisionId, selectedUnit = '') {
            $.ajax({
                url: '/api/units/' + divisionId,
                type: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,  // <-- add this
                    'Accept': 'application/json'
                },
                success: function (data) {
                    $('#units').empty().append('<option value=""> - CHOOSE UNIT - </option>');
                    $('#units').append(
                        data.map(u =>
                            `<option value="${u.id}" ${u.id == selectedUnit ? 'selected' : ''}>
                                ${u.name.toUpperCase()}
                            </option>`
                        )
                    );
                }
            });
        }
    });

</script>
@endsection



@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="All Approvers" subtitle="Manage approvers in this module">
            <x-button-link 
                :href="route('settings.approvers.view')" 
                icon="fa-solid fa-eye" 
                text="View Approvers" 
                variant="secondary"
            />
            <x-button-link 
                :href="route('settings.approvers.create')" 
                icon="fa-solid fa-plus" 
                text="Add Approvers" 
                variant="primary"
            />
        </x-header>
        <div class="row mb-3">
            <div class="col-12 col-md-6 mb-3">
                <label for="division" class="mb-3">Choose By Divisions</label>
                <select id="division" class="form-select text-uppercase">
                    <option value=""> - CHOOSE -</option>
                </select>
            </div>
            <div class="col-12 col-md-6 mb-3">
                <label for="units" class="mb-3">Choose By Units</label>
                <select id="units" class="form-select text-uppercase">
                    <option value=""> - CHOOSE -</option>
                </select>
            </div>
        </div>
        <x-table id="myTable">
            <thead>
                <tr>
                    <th></th>
                    <th>Type</th>
                    <th>Approver Levels</th>
                    <th>No. Of Approvers</th>
                    <th>Date Added</th>
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

        let table = $('#myTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('settings.approvers.index') }}',
                data: function(d) {
                    d.division = $('#division').val();
                    d.unit     = $('#units').val();
                }
            },
            columns: [
                {
                    className: 'dt-control',
                    orderable: false,
                    searchable: false,
                    data: null,
                    defaultContent: ''
                },
                { data: "type", name: 'type' },
                { data: "level_approvers", name: 'level_approvers' },
                { data: "no_approvers", name: 'no_approvers' },
                { data: "date_created", name: 'date_created' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
            scrollX: true,
            autoWidth: false
        });

        $('#myTable tbody').on('click', 'td.dt-control', function() {
            var tr  = $(this).closest('tr');
            var row = table.row(tr);

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
            } else {
                row.child(format(row.data())).show();
                tr.addClass('shown');
            }
        });

        function format(d) {
            var html = '<ul class="list-group">';
            if(d.unit_name && d.unit_name.length) {
                d.unit_name.forEach(function(user, key) {
                    html += `
                        <li style="font-size: 12px;" class="list-group-item">
                            ${d.type === 'payroll' ? 'Payroll' : `(${d.unit_code[key]}) | ${d.unit_name[key]}`}
                        </li>
                    `;
                });
            } else {
                html += '<li style="font-size: 12px;" class="list-group-item">No approvers</li>';
            }
            html += '</ul>';
            return html;
        
        }
        const selectedDivision = "{{ $division_id ?? '' }}";
        const selectedUnit = "{{ $unit_id ?? '' }}";

        const token = localStorage.getItem('auth_token');

        $.ajax({
            url: '/api/divisions',
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token,
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
            },
            error: function () {
                console.error('Failed to load divisions');
            }
        });


        $('#division').on('change', function () {
            const divisionId = $(this).val();

            $('#units').empty().append('<option value=""> - CHOOSE UNIT - </option>');

            if (divisionId) {
                loadUnits(divisionId);
            }

            table.ajax.reload();
        });

        $('#units').on('change', function () {
            table.ajax.reload();
        });

        function loadUnits(divisionId, selectedUnit = '') {
            $.ajax({
                url: '/api/units/' + divisionId,
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                },
                success: function (data) {
                    $('#units').empty().append('<option value=""> - CHOOSE UNIT - </option>');

                    data.forEach(function (u) {
                        const isSelected = u.id == selectedUnit ? 'selected' : '';
                        $('#units').append(
                            `<option value="${u.id}" ${isSelected}>${u.name.toUpperCase()}</option>`
                        );
                    });
                },
                error: function () {
                    console.error('Failed to load units for division ' + divisionId);
                }
            });
        }


    });
</script>
@endsection



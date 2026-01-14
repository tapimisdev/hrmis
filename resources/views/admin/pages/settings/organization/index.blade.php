@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Organization" subtitle="Manage your organization settings here">
           <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" 
                        type="button" 
                        id="addNewDropdown" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false">
                    <i class="fa-solid fa-plus me-2"></i> Add New
                </button>

                <ul class="dropdown-menu dropdown-menu-end w-100 dropdown-menu-modern" aria-labelledby="addNewDropdown">
                    <li>
                        <a class="dropdown-item fw-bold text-uppercase d-flex align-items-center" href="{{ route('organization.create', ['type' => 'division']) }}">
                            <i class="fa-solid fa-diagram-project me-2"></i> New Division
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item fw-bold text-uppercase d-flex align-items-center" href="{{ route('organization.create', ['type' => 'unit']) }}">
                            <i class="fa-solid fa-layer-group me-2"></i> New Unit
                        </a>
                    </li>
                </ul>
            </div>

            
        </x-header>
        <div class="card shadow p-3">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button data-id="agency" 
                                class="push-state-query hover-scale toggle-datatable nav-link text-uppercase fw-bold px-4 py-3 active d-flex align-items-center" 
                                id="agency-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#agency" 
                                type="button" 
                                role="tab">
                            <i class="fa-solid fa-building me-2"></i> Agency
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button data-id="division" 
                                class="push-state-query hover-scale toggle-datatable nav-link text-uppercase fw-bold px-4 py-3 d-flex align-items-center" 
                                id="division-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#division" 
                                type="button" 
                                role="tab">
                            <i class="fa-solid fa-diagram-project me-2"></i> Divisions
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button data-id="unit" 
                                class="push-state-query hover-scale toggle-datatable nav-link text-uppercase fw-bold px-4 py-3 d-flex align-items-center" 
                                id="unit-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#unit" 
                                type="button" 
                                role="tab">
                            <i class="fa-solid fa-layer-group me-2"></i> Units
                        </button>
                    </li>

                </ul>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="agency" role="tabpanel">
                        <div class="mt-4">
                            <form id="form" action="{{ route('organization.store', ['type' => 'agency'])  }}" method="post">
                                @method('POST')
                                @csrf
                                <div class="row mt-4">
                                    <div class="col-12 col-md-12 mb-3">
                                        <label for="agency_code" class="mb-2">Code <span class="text-danger">*</span></label>
                                        <input type="text" name="agency_code" id="agency_code" class="form-control" value="{{$agency->code}}">
                                    </div>
                                    <div class="col-12 col-md-12 mb-3">
                                        <label for="agency_name" class="mb-2">Name <span class="text-danger">*</span></label>
                                        <input type="text" name="agency_name" id="agency_name" class="form-control" value="{{$agency->name}}">
                                    </div>
                                    <div class="col-12 col-md-12 mb-3">
                                        <label for="agency_description" class="mb-2">Description</label>
                                        <textarea type="text" name="agency_description" id="agency_description" class="form-control" rows="10">{{$agency->description}}</textarea>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button class="btn btn-primary text-uppercase fw-bold px-5 py-3">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="division" role="tabpanel">
                        <x-table id="divisionTable">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Date Added</th>
                                    <th style="width: 120px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                            </tbody>
                        </x-table>
                    </div>
                    <div class="tab-pane fade" id="unit" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="filter" class="mb-2">Filter</label>
                                <select name="divisions" id="divisions" class="form-select">
                                    <option value=""> - CHOOSE DIVISION - </option>
                                    @foreach ($divisions as $division)
                                        <option value="{{$division->id}}">{{strtoupper($division->code . ' - ' . $division->name)}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive mt-5 mt-5">
                            <x-table id="unitTable">
                               <thead>
                                    <tr>
                                        <th></th>
                                        <th>Division</th>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Date Added</th>
                                        <th style="width: 120px">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </x-table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('scripts')
<script>
    $(function() {
        
        const tableConfigs = {
            division: [
                { data: "DT_RowIndex", name: 'index' },
                { data: "code", name: 'code' },
                { data: "name", name: 'name' },
                { data: "date_created", name: 'date_created' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
            unit: [
                { data: "DT_RowIndex", name: null, orderable: false, searchable: false },
                { data: "division", name: 'division' },
                { data: "code", name: 'code' },
                { data: "name", name: 'name' },
                { data: "date_created", name: 'date_created' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ]
        };

        function initDataTable(selector, type) {

            if ($.fn.DataTable.isDataTable(selector)) {
                $(selector).DataTable().clear().destroy();
            }

            return $(selector).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('organization.index') }}',
                    data: function (d) {
                        d.type = type;

                        if (type === 'unit') {
                            d.division_id = $('#divisions').val();
                        }

                        if (typeof extraParams !== "undefined") {
                            $.extend(d, extraParams);
                        }
                    }
                },
                columns: tableConfigs[type],
                scrollX: true,
                autoWidth: false
            });

        }


        initDataTable('#divisionTable', 'division');
        initDataTable('#unitTable', 'unit');

        $('.toggle-datatable').on('click', function() {
            const type = $(this).data('id');
            const selector = '#' + type + 'Table';
            initDataTable(selector, type);
        });

        $('#divisions').on('change', function() {
            const type = 'unit';
            const selector = '#' + type + 'Table';
            initDataTable(selector, type);
        });

        const url = $('#form').attr('action');
        post(url);
    });
</script>
@endsection



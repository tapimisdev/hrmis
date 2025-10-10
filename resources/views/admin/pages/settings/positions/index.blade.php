@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="{{strtoupper($employment_type->code . ' - ' . $employment_type->name)}} Positions" subtitle="Manage positions for this employment type">
            <x-button-link 
                :href="route('positions.create', ['employment_type_id' => $employment_type->id])" 
                icon="fa-solid fa-plus" 
                text="Add Position" 
                variant="primary"
            />
        </x-header>
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            @foreach($employment_types as $type)
                <li class="nav-item" role="presentation">
                    <a href="{{route('positions.index', ['employment_type' => $type->id])}}" class="nav-link text-uppercase fw-bold px-4 py-3 {{$employment_type->id === $type->id ? 'active' : ''}}" >
                        {{$type->name}}
                    </a>
                </li>
            @endforeach
        </ul>
        <x-table id="myTable">
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
@endsection

@section('scripts')
<script>
    $(function() {

        const employment_type_id = @json($employment_type->id)

        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('positions.index' , ['employment_type' => '__ID__']) }}'.replace('__ID__', employment_type_id),
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "code", name: 'code' },
                { data: "name", name: 'name' },
                { data: "date_created", name: 'date_created' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });
        
    });
</script>
@endsection



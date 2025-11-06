@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
<div class="container p-4">
    <x-header title="Edit Role: {{ $role->name }}" subtitle="Manage {{ $role->name }} in this module">
        <x-button-link 
            :href="route('role-and-permission.index')" 
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
        />
    </x-header>
    <form action="{{ route('role-and-permission.update', $role->id) }}" class="bg-body-secondary rounded-3 border" method="POST">
        @csrf
        @method('PUT')
        @php $row = 1; @endphp
        @foreach($grouped as $module => $actions)
            <div class="row border-bottom mx-1 py-3 px-2">
                <div class="col-md-6 d-flex gap-2">
                    <div class="text-body-secondary fw-bold">#{{ $row++ }}</div>
                    <div>{{ ucfirst(str_replace('_', ' ', $module)) }}</div>
                </div>
                <div class="col-md-4">
                    @foreach($actions as $action) 
                        <ul class="p-0 m-0">
                            <li class="d-flex gap-2 mb-1">
                                <input type="checkbox" 
                                    name="permissions[]" 
                                    value="{{ $action['name'] }}"
                                    {{ $role->permissions->contains('name', $action['name']) ? 'checked' : '' }}>
                                <label for="">{{ ucfirst($action['short_name']) }}</label>
                            </li>
                        </ul>
                    @endforeach
                </div>
            </div>
        @endforeach
        <div class="d-flex justify-content-end my-4 mx-3">
            <x-button type="submit">
                <i class="fa-duotone fa-solid fa-floppy-disk"></i>
                Save
            </x-button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        let = DataTable = $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": '{{ route('role-and-permission.index') }}',
            "columns": [
                { data: "DT_RowIndex", name: 'index' },
                { data: "name", name: 'name' },
                { data: "actions", name: 'actions', orderable: false, searchable: false },
            ],
        });
    });
</script>
@endsection
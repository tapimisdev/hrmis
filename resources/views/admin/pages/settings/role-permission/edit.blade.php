@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
<div class="container p-4">
    <x-header title="Edit Role: {{ $role->name }}" subtitle="Manage {{ $role->name }} in this module">
        <a href="{{ route('role-and-permission.index') }}" class="btn btn-danger py-3 px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </x-header>
    <form action="{{ route('role-and-permission.update', $role->id) }}" method="POST">
        <div class="card">
            <div class="card-body p-4">
                @csrf
                @method('PUT')
                <table class="table table-striped p-3 pb-5">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Module</th>
                            <th class="text-center">Create</th>
                            <th class="text-center">Read</th>
                            <th class="text-center">Edit</th>
                            <th class="text-center">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $row = 1; @endphp
                        @foreach($grouped as $module => $actions)
                            <tr>
                                <td>{{ $row++ }}</td>
                                <td>{{ ucfirst($module) }}</td>
                                <td class="text-center">
                                    @if(isset($actions['create']))
                                        <input type="checkbox" 
                                            name="permissions[]" 
                                            value="{{ $actions['create']['name'] }}"
                                            {{ $role->permissions->contains('name', $actions['create']['name']) ? 'checked' : '' }}>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(isset($actions['view']) || isset($actions['read']))
                                        @php $perm = $actions['view'] ?? $actions['read']; @endphp
                                        <input type="checkbox" 
                                            name="permissions[]" 
                                            value="{{ $perm['name'] }}"
                                            {{ $role->permissions->contains('name', $perm['name']) ? 'checked' : '' }}>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(isset($actions['edit']))
                                        <input type="checkbox" 
                                            name="permissions[]" 
                                            value="{{ $actions['edit']['name'] }}"
                                            {{ $role->permissions->contains('name', $actions['edit']['name']) ? 'checked' : '' }}>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(isset($actions['delete']))
                                        <input type="checkbox" 
                                            name="permissions[]" 
                                            value="{{ $actions['delete']['name'] }}"
                                            {{ $role->permissions->contains('name', $actions['delete']['name']) ? 'checked' : '' }}>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
            <div class="card-footer d-flex justify-content-end bg-transparent border-0 pb-4">
                <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">Update</button>
            </div>
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
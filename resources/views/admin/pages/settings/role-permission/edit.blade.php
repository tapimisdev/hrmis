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
                            <th>Permission Name</th>
                            <th width="100">Assign</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions as $index => $permission)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $permission->name }}</td>
                                <td class="text-center">
                                    <input type="checkbox" 
                                        name="permissions[]" 
                                        value="{{ $permission->id }}"
                                        id="perm_{{ $permission->id }}"
                                        {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}>
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
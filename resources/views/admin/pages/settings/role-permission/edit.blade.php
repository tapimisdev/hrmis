@extends('admin.layouts.app')

@section('content')
<div class="container py-4">

    {{-- Header --}}
    <x-header title="Edit Role: {{ $role->name }}" subtitle="Manage permissions for this role">
        <x-button-link 
            :href="route('role-and-permission.index')" 
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
        />
    </x-header>

    {{-- Search Bar --}}
    <div class="d-flex justify-content-end align-items-center">
        <div class="search-bar d-flex align-items-center gap-2 border">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="search-module" placeholder="Search module..." class="form-control form-control-sm">
        </div>
    </div>

    {{-- Role Permission Form --}}
    <form id="rolePermissionForm" class="p-3 rounded-3">
        @csrf
        @php $row = 1; @endphp

        @foreach($grouped as $module => $actions)
            {{-- Added data-module and module-item class --}}
            <div 
            class="row mx-1 py-3 px-2 gap-3 module-item 
                {{ $loop->first ? 'border-top' : '' }} 
                {{ !$loop->last ? 'border-bottom py-0 pt-3' : '' }}"
                data-module="{{ $module }}">
                
                <div class="col-xs-6 col-sm-6 col-md-6 d-flex gap-2">
                    <div class="text-body-secondary fw-bold">#{{ $row++ }}</div>
                    <div class="module-name">{{ ucfirst(str_replace('_', ' ', $module)) }}</div>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-4">
                    @foreach($actions as $action)
                        <ul class="p-0 m-0">
                            <li class="d-flex gap-2 mb-1">
                                <input type="checkbox"
                                    name="permissions[]"
                                    value="{{ $action['name'] }}"
                                    {{ $role->permissions->contains('name', $action['name']) ? 'checked' : '' }}>
                                <label>{{ ucfirst($action['short_name']) }}</label>
                            </li>
                        </ul>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-end mt-4 btn-element border-top">
            <x-button type="button" id="update-button">
                <i class="fa-solid fa-floppy-disk me-2"></i> Save
            </x-button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    // Working client-side search
    $('#search-module').on('input', function () {
        let searchValue = $(this).val().toLowerCase().trim();

        $('.module-item').each(function () {
            let moduleName = $(this).data('module');
            if (moduleName.includes(searchValue)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Save permissions via Axios
    $(document).on("click", "#update-button", function (e) {
        e.preventDefault();
        
        const $btn = $(this);

        $btn.prop("disabled", true).html('  <i class="fa fa-spinner fa-spin me-2"></i> Saving...');

        const roleId = "{{ $role->id }}";
        const permissions = $('input[name="permissions[]"]:checked').map(function() {
            return $(this).val();
        }).get();

        const formData = {
            _token: $('input[name="_token"]').val(),
            _method: 'PUT',
            permissions: permissions,
        };

        axios.post(`/admin/maintenance/role-and-permission/${roleId}`, formData)
            .then(response => {
               Swal.fire({
                    toast: true,
                    position: 'top',
                    icon: 'success',
                    title: response.data.message ?? 'Permissions updated successfully!',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            })
            .catch(() => {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: "Something went wrong!",
                });
            }).finally (() => {
                $btn.prop("disabled", false).html('<i class="fa-solid fa-floppy-disk me-2"></i> Save');
            });
    });
});
</script>
@endsection

@section('styles')
<style>
    .module-card {
        background-color: var(--bs-body-bg);
        border-radius: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: all 0.2s ease-in-out;
    }
    .module-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .module-header {
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 1rem;
    }
    .search-bar {
        position: sticky;
        top: 0;
        background-color: var(--bs-secondary-bg);
        padding: 0 1rem;
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        max-width: 320px;
        width: 100%;
    }
    .search-bar input {
        border: none;
        background: var(--bs-secondary-bg);
        outline: none;
        width: 100%;
    }
    .search-bar input:focus {
        border: none;
        background: var(--bs-secondary-bg);
        outline: none;
        box-shadow: none;
        width: 100%;
    }
    .search-icon {
        color: #6c757d;
    }
    .btn-element {
        position: fixed;
        bottom: 0;
        left: 0;
        padding: .8rem 16px;
        width: 100%;
        background-color: var(--bs-tertiary-bg);
    }
</style>
@endsection
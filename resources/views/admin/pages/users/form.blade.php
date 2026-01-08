@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">

        {{-- Header --}}
        <x-header
            :title="$isEdit ? 'Update User' : 'Add New User'"
            :subtitle="$isEdit ? 'Update user information' : 'Create a new user account'"
        >
            <x-button-link
                :href="route('users.index')"
                icon="fa-solid fa-arrow-left me-2"
                text="Back"
                variant="danger"
            />
        </x-header>

        {{-- Form --}}
        <form
            id="form"
            method="POST"
            action="{{ $isEdit ? route('users.update', $user->id) : route('users.store') }}"
        >
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control"
                                value="{{ $isEdit ? $user->name : old('name') }}"
                            >
                            <div class="error-field"></div>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                value="{{ $isEdit ? $user->email : old('email') }}"
                            >
                            <div class="error-field"></div>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">
                                Role
                                <span class="text-danger">*</span>
                            </label>
                            <select name="role" id="role" class="form-select">
                                <option value=""> - CHOOSE - </option>
                                @foreach($roles as $role)
                                    <option 
                                        value="{{ $role->id }}" 
                                        {{ $isEdit ? ($user->roles->first() && $user->roles->first()->id == $role->id) ? 'selected' : '' : '' }}
                                    >
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">
                                Password
                                @if(!$isEdit)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control"
                                placeholder="{{ $isEdit ? 'Leave blank to keep current password' : '' }}"
                            >
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-semibold">
                                Confirm Password
                                @if(!$isEdit)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            <input
                                type="password"
                                name="password_confirmation"
                                id="password_confirmation"
                                class="form-control"
                            >
                            <div class="error-field"></div>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="card-footer bg-transparent border-0 d-flex justify-content-end pb-5">
                    <button
                        type="submit"
                        class="btn btn-primary px-5 py-3 text-uppercase fw-bold"
                    >
                        {{ $isEdit ? 'Update' : 'Save' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    $(function () {
        const isEdit = @json($isEdit);
        const url = $('#form').attr('action');

        if (!isEdit) {
            post(url);
        } else {
            put(url);
        }
    });
</script>
@endsection
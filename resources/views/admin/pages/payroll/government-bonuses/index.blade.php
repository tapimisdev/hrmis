@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Government Bonuses" subtitle="Generate and manage government bonus payrolls in this module">
            <x-button-link
                :href="route('government-bonuses.create')"
                icon="fa-solid fa-plus"
                text="Create"
                variant="primary"
            />
        </x-header>

        <government-bonus-index/>
    </div>
@endsection

@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <x-header title="Salary Payroll" subtitle="View salary payroll in this module">

            <x-button-link 
                :href="route('salary.create')" 
                icon="fa-solid fa-plus" 
                text="Create Payroll" 
                variant="primary"
            />
        </x-header>

        <payroll-index/>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {

    })
</script>
@endsection
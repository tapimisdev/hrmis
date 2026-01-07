@extends('employee.layout.app')

@section('content')
    <div class="container-fluid min-vh-100">
        
        <x-employee-navbar>
            <header-vue title="DOST TAPI"></header-vue>
        </x-employee-navbar>

        <x-header-employee title="Leave Credits" subtitle="View leave credits in this module" >
           
        </x-header-employee>

        <div class="card rounded-4 p-3">
            
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {
        
    });
</script>
@endsection
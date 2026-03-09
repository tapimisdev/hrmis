@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
     <dtr-view-vue 
        :employee_no="{{ json_encode($employee_no) }}"
        :employee_id="{{ json_encode($employee_id) }}"
        :supervisor="{{ json_encode($supervisor) }}">
    </dtr-view-vue>
@endsection

@section('scripts')
<script>
</script>
@endsection



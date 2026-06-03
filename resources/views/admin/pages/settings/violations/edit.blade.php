@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Violations" subtitle="Edit violation sanction rule in this module">
            <x-button-link
                :href="route('settings.violations.index')"
                icon="fa-solid fa-arrow-left me-2"
                text="Back"
                variant="danger"
            />
        </x-header>

        <form id="form" action="{{ route('settings.violations.update', ['violation' => $violation->id]) }}" method="post">
            @csrf
            @include('admin.pages.settings.violations.form', ['violation' => $violation, 'submitLabel' => 'Update'])
        </form>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {
        put($('#form').attr('action'));
    });
</script>
@endsection

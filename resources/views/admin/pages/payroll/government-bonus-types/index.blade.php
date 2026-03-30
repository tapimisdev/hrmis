@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Government Bonus Rules" subtitle="Configure each government bonus type and the rules used for generation">
        </x-header>

        <government-bonus-type-index
            fetch-url="{{ route('government-bonus-types.index') }}"
            store-url="{{ route('government-bonus-types.store') }}"
            update-url="{{ route('government-bonus-types.update', ['government_bonus_type' => '__ID__']) }}"
            delete-url="{{ route('government-bonus-types.destroy', ['government_bonus_type' => '__ID__']) }}"
        />
    </div>
@endsection

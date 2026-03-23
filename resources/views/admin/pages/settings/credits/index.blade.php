@extends('admin.layouts.app')

@section('styles')
    <style>
        .nav-tabs .nav-link {
            text-transform: uppercase;
            font-weight: 600;
            cursor: pointer;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <x-header title="Import Credits" subtitle="Upload and setup all employees' credits by type.">
        <x-button-link 
            :href="route('hris.employee.index')" 
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
        />
    </x-header>

    <div class="alert alert-info text-uppercase fw-bold">
        <div>
            This upload is intended only for a one-time migration of all employees’ credits to initialize their balances. It should not be used to update existing credits, individually or in bulk.
        </div>
        <hr>
        <div>
            Using this for updates may cause data inconsistencies or loss of previous records. For adjustments or corrections, use the system’s designated update modules to ensure accuracy and maintain credit history integrity.
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item" role="presentation">
            <a href="{{ route('settings.credits.index', ['type' => 'leave']) }}" 
               class="nav-link {{ $type === 'leave' ? 'active' : '' }}">
                Leave
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a href="{{ route('settings.credits.index', ['type' => 'offset']) }}" 
               class="nav-link {{ $type === 'offset' ? 'active' : '' }}">
                Offset
            </a>
        </li>
    </ul>

    @if($type === 'leave')
        <import-credits 
            :type='@json($type)'
            :leave-types='@json($leave_types)' 
            save-url="{{ route('settings.credits.import', ['type' => 'leave']) }}"
        ></import-credits>
    @endif

    @if($type === 'offset')
        <import-credits 
            :type='@json($type)'
            save-url="{{ route('settings.credits.import', ['type' => 'offset']) }}"
        ></import-credits>
    @endif

</div>
@endsection

@section('scripts')
@endsection

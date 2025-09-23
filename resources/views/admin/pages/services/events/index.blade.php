@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4 pb-5">
        <div class="pb-5">
            <x-header title="All Events" subtitle="Manage all created events and announcements in this module">
                <a href="{{ route('services.events.create') }}" class="btn btn-secondary py-3 px-4 text-uppercase fw-medium">
                    <i class="fa-solid fa-plus me-2"></i> Add Events
                </a>
            </x-header>
        </div>
        <div class="row">
            @forelse ($data as $item)
                <div class="col-12 col-md-6 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $item->title }}</h5>
                            <p class="card-text text-truncate">{{ $item->description }}</p>
                            <p class="card-text"><small class="text-muted">Posted on: {{ $item->posted_on ? $item->posted_on->format('F d, Y') : 'N/A' }}</small></p>
                            <div class="mt-auto">
                                <a href="{{ route('services.events.show', $item->id) }}" class="btn btn-primary btn-sm">View Details</a>
                                <a href="{{ route('services.events.edit', $item->id) }}" class="btn btn-secondary btn-sm">Edit</a>          
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-danger text-uppercase text-center fw-bold">No Events or Announcements Found</div>
                </div>
            @endforelse
        </div>
    </div>
@endsection

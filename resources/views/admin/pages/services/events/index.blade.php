@extends('admin.layouts.app')

@section('styles')
    <style>
        .card {
            min-height: 500px;
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
            filter: blur(0.5px);
        }
    </style>
@endsection

@section('content')
    <div class="container pt-4 px-3">
        <div class="pb-5">
            <x-header title="All Events" subtitle="Manage all created events and announcements in this module">
                <a href="{{ route('services.events.create') }}" class="btn btn-outline-primary py-3 px-4 text-uppercase fw-medium">
                    <i class="fa-solid fa-plus me-2"></i> Add Events
                </a>
            </x-header>
        </div>
        <div class="row">
            @forelse ($data as $item)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('services.events.show', ['event' => $item->slug]) }}" class="text-decoration-none text-dark mb-4 d-block">
                        <div class="card shadow">
                            <div class="position-relative">
                                <img src="{{ Storage::url('events/attachments/' . $item->banner) }}" 
                                    class="card-img-top" 
                                    alt="{{ $item->title }}">
                                
                                <div class="position-absolute top-0 end-0 bg-danger text-white rounded-circle d-flex flex-column align-items-center justify-content-center p-2 me-3 mt-4" style="width:60px; height:60px;">
                                    <small class="fw-bold">{{ \Carbon\Carbon::parse($item->posted_on)->format('d') }}</small>
                                    <small class="fw-bold text-uppercase">{{ \Carbon\Carbon::parse($item->posted_on)->format('M') }}</small>
                                </div>
                            </div>

                            <div class="card-body">
                                <h5 class="card-title mb-2 text-uppercase fw-medium">{{ $item->title }}</h5>
                                @php
                                    $maxTags = 5;
                                    $tagCount = $item->tags->count();
                                @endphp

                                @foreach ($item->tags->take($maxTags) as $tag)
                                    <span class="badge bg-primary text-uppercase m-1 px-3 py-2" style="font-size: 10px">{{ $tag->name ?? $tag }}</span>
                                @endforeach

                                @if ($tagCount > $maxTags)
                                    <span class="badge bg-secondary text-uppercase m-1 px-3 py-2">
                                        +{{ $tagCount - $maxTags }}
                                    </span>
                                @endif

                                <h6 class="card-text text-muted mt-3">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($item->description), 300, '...') }}
                                </h6>

                                <div class="text-muted text-end small">
                                    (click to view)
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-danger text-uppercase text-center fw-bold">
                        No Events or Announcements Found
                    </div>
                </div>
            @endforelse
            {{ $data->links() }}
        </div>
    </div>
@endsection

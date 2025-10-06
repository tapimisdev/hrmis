@extends('admin.layouts.app')

@section('styles')
    <style>
        .banner {
            width: 100%;
            height: 400px;
            border-top-left-radius: 30px;
            border-top-right-radius: 30px;
        }

        .banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: left;
            border-radius: 30px;
            border: 1px solid #ddd;
        }

        .description {
            margin-top: 20px;
            text-indent: 50px;
        }

        .horn {
            display: inline-block;
            transform: rotate(-20deg);
            position: relative;
            top: -5px;
        }

        .attachment-item {
            height: 100%;
        }

        .file-bubble {
            text-align: left;
            overflow: hidden;
        }
    </style>
@endsection

@section('content')
    <div class="container p-4 pb-5">
        <div class="d-flex justify-content-start gap-2 mt-3 pb-4">
            <a href="{{route('services.events.index')}}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
                <i class="fa-solid fa-arrow-left me-2"></i>Go Back
            </a>
            <a href="{{route('services.events.edit', ['event' => $data['slug']])}}" class="btn btn-secondary py-3 px-4 text-uppercase fw-medium">
                <i class="fa-solid fa-pen-to-square"></i> Update
            </a>
        </div>
        <div class="row">
            <div class="col-12 col-md-8">
                <div class="banner">
                    <img src="{{ Storage::url('events/attachments/' . $data['banner']) }}" alt="" srcset="">
                </div>
                <div class="title mt-4">
                    <h1 class="text-uppercase fw-bold mb-2">
                        <span class="horn">📢</span>
                        {{ $data['title'] }}
                    </h1>
                    <div class="text-uppercase text-muted fw-bold" style="font-size: 11px;">
                        Posted On
                        <u>{{ \Carbon\Carbon::parse($data['posted_on'])->format('F d, Y') }}</u>
                        by <u>{{ implode(', ', array_column($data['posted_by'], 'name')) }}</u>
                    </div>
                    <div class="mt-2">
                        @foreach ($data['tags'] as $tag)
                            <span class="badge bg-primary text-uppercase m-1 px-3 py-2">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="description">
                    {!! $data['description'] !!}
                </div>
                @if($data['attachments'])
                    <hr>
                    <label class="mb-4">Attachments</label>
                @endif
                <div class="d-flex flex-wrap gap-3 mb-4">
                    @foreach($data['attachments'] as $attachment)
                        @php
                            $ext = pathinfo($attachment['filename'], PATHINFO_EXTENSION);
                            $isImage = in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp']);
                        @endphp

                        @if($isImage)
                            <div class="attachment-item" style="flex: 1 1 150px; max-width: 250px;">
                                <a href="{{ Storage::url('events/attachments/' . $attachment['filename']) }}" 
                                download 
                                class="text-decoration-none">
                                    <div class="card shadow-sm border-0 rounded-3 w-100 h-100" style="cursor: pointer">
                                        <div class="ratio ratio-1x1" style="height: 150px;">
                                            <img src="{{ Storage::url('events/attachments/' . $attachment['filename']) }}" 
                                                alt="{{ $attachment['title'] }}"
                                                class="img-fluid object-fit-cover rounded-3">
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="d-flex flex-wrap gap-3">
                    @foreach($data['attachments'] as $attachment)
                        @php
                            $ext = pathinfo($attachment['filename'], PATHINFO_EXTENSION);
                            $isImage = in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp']);
                        @endphp

                        @if(!$isImage)
                            <div class="attachment-item" style="width: fit-content; min-width: 100px;">
                                <a href="{{ Storage::url('events/attachments/' . $attachment['filename']) }}" 
                                download 
                                class="text-decoration-none">
                                    <div class="file-bubble bg-light text-dark rounded-3 shadow-sm py-2 px-3 d-flex flex-column justify-content-center h-100" style="cursor: pointer; width: fit-content;">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-regular fa-file-lines fs-4"></i>
                                            <div>
                                                <span class="fw-semibold d-block text-truncate" style="max-width: 200px;" 
                                                    title="{{ $attachment['title'] . '.' . $ext }}">
                                                    {{ $attachment['title'] . '.' . $ext }}
                                                </span>
                                                <small class="text-dark fw-bold">
                                                    @php
                                                        try {
                                                            echo number_format(Storage::disk('public')->size('events/attachments/' . $attachment['filename']) / 1024, 2) . ' KB';
                                                        } catch (\Exception $e) {
                                                            echo 'Unknown size';
                                                        }
                                                    @endphp
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>




            </div>
            <div class="col-12 col-md-4">
                <h5 class="fw-bold text-uppercase mb-3">Other Events</h5>
                @forelse ($others as $other)
                    <div class="card mb-3 shadow-sm border-0">
                        <a href="{{ route('services.events.show', $other->slug) }}" class="text-decoration-none text-dark">
                            @if($other->banner)
                                <img src="{{ Storage::url('events/attachments/' . $other->banner) }}" 
                                    alt="{{ $other->title }}" 
                                    class="card-img-top" 
                                    style="height: 150px; object-fit: cover;">
                            @else
                                <div class="bg-secondary d-flex align-items-center justify-content-center text-white"
                                    style="height: 150px;">
                                    No Image
                                </div>
                            @endif
                            <div class="card-body p-3">
                                <h6 class="card-title text-uppercase fw-bold text-truncate text-uppercase">
                                    {{ $other->title }}
                                </h6>
                                <p class="card-text mb-1 text-muted text-uppercase" style="font-size: 12px;">
                                    {{ \Carbon\Carbon::parse($other->posted_on)->format('F d, Y') }}
                                </p>
                                <p class="card-text" style="font-size: 13px;">
                                    {!! Str::limit(strip_tags($other->description), 120) !!} <span class="text-decoration-underline">See More...</span>
                                </p>
                            </div>
                        </a>
                    </div>
                @empty
                    <p class="text-muted">No other events available.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        @if(isset($isEdit) && $isEdit == true)
            <x-header title="Update Tranche" subtitle="update this tranche">
                <a href="{{route('settings.tranche.index')}}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
                    <i class="fa-solid fa-arrow-left me-2"></i>Go Back
                </a>
            </x-header>
        @else
            <x-header title="Add New Tranche" subtitle="create new tranche" >
                <a href="{{route('settings.tranche.index')}}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
                    <i class="fa-solid fa-arrow-left me-2"></i>Go Back
                </a>
            </x-header>
        @endif
        <form id="form" action="{{ $isEdit ? route('settings.tranche.update', ['id' => $id]) : route('settings.tranche.store') }}" method="post">
            @if($isEdit)
                @method('PUT')
            @else
                @method('POST')
            @endif
            @csrf
            <div class="card shadow p-3">
                <div class="card-body">
                    <div class="row my-3">
                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="employment_type">Employment Type <span class="text-danger">*</span></label>
                            <select id="employment_type" name="employment_type_id" class="form-control">
                                <option value="">-- Select Employment Type --</option>
                                @foreach($employment_types as $type)
                                    <option value="{{ $type->id }}" 
                                        {{ $isEdit && $data->employment_type_id == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="date">Date <span class="text-danger">*</span></label>
                            <input type="date" id="date" name="date" class="form-control" value="{{$isEdit ? $data->name : ''}}">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="name">Description</label>
                            <textarea name="description" id="description" cols="20" rows="5" class="form-control">{{$isEdit ? $data->description : ''}}</textarea>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="file">File <span class="text-danger">*</span></label>
                            <input type="file" id="file" name="file" class="form-control">
                            <div class="error-field"></div>
                        </div>
                        @if($isEdit)
                            <div class="col-12 mb-3">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>SG</th>
                                            <th>Step 1</th>
                                            <th>Step 2</th>
                                            <th>Step 3</th>
                                            <th>Step 4</th>
                                            <th>Step 5</th>
                                            <th>Step 6</th>
                                            <th>Step 7</th>
                                            <th>Step 8</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($data->items as $item)
                                            <tr>
                                                <td>{{ $item->salary_grade }}</td>
                                                <td>{{ $item->step_1 }}</td>
                                                <td>{{ $item->step_2 }}</td>
                                                <td>{{ $item->step_3 }}</td>
                                                <td>{{ $item->step_4 }}</td>
                                                <td>{{ $item->step_5 }}</td>
                                                <td>{{ $item->step_6 }}</td>
                                                <td>{{ $item->step_7 }}</td>
                                                <td>{{ $item->step_8 }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                              <td colspan="9" class="text-center">No items available</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endif

                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 d-flex justify-content-end">
                    <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">Save</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {
        const isEdit = @json($isEdit);
        const url = $('#form').attr('action');
        if(!isEdit) {
            post(url);
        } else {
            put(url);
        }
    });
</script>
@endsection



@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="Update Employee Account" subtitle="Update employee's portal account">
            <a href="{{ route('hris.employee.index') }}" class="btn btn-primary py-3 px-4 text-uppercase fw-medium">
                Go Back
            </a>
        </x-header>

        <x-hris-menu active="earnings" empno="{{ $employee_no }}" />

        <form id="form" action="{{ route('hris.employee.earnings', ['employee_no' => $employee_no]) }}" method="post">
            @method('PUT') 
            @csrf
            <div class="card shadow p-3">
                <div class="card-body">
                    <div class="accordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#flush-earnings" aria-expanded="true" aria-controls="flush-earnings">
                                    Employee's Earnings
                                </button>
                            </h2>

                            <div id="flush-earnings" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <div id="fields-container">
                                        @if ($data->isNotEmpty())
                                            @foreach ($data as $index => $value)
                                                <div class="card mb-3 grouped-field" data-index="{{ $index }}">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-end">
                                                            <button type="button" class="btn btn-danger ms-auto remove-group-btn">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </div>
                                                        <div class="row">

                                                            <!-- Earning -->
                                                            <div class="col-12 col-md-4 mb-3">
                                                                <label for="earning.{{ $index }}" class="form-label">Earning</label>
                                                                <select name="earning[{{ $index }}]" id="earning.{{ $index }}" class="form-select">
                                                                    <option value=""> - CHOOSE - </option>
                                                                    @foreach($earnings as $earning)
                                                                        <option value="{{ $earning->id }}" {{ $earning->id == $value->earning_id ? 'selected' : '' }}>
                                                                            {{ $earning->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <div class="error-field"></div>
                                                            </div>

                                                            <!-- First Term -->
                                                            <div class="col-12 col-md-4 mb-3">
                                                                <label for="first_term.{{ $index }}" class="form-label">First Term</label>
                                                                <input type="text" name="first_term[{{ $index }}]" class="form-control" id="first_term.{{ $index }}" value="{{ $value->first_term ?? 0 }}">
                                                                <div class="error-field"></div>
                                                            </div>

                                                            <!-- Second Term -->
                                                            <div class="col-12 col-md-4 mb-3">
                                                                <label for="second_term.{{ $index }}" class="form-label">Second Term</label>
                                                                <input type="text" name="second_term[{{ $index }}]" class="form-control" id="second_term.{{ $index }}" value="{{ $value->second_term ?? 0 }}">
                                                                <div class="error-field"></div>
                                                            </div>

                                                            <!-- Type -->
                                                            <div class="col-12 col-md-4 mb-3">
                                                                <label for="type.{{ $index }}" class="form-label">Type</label>
                                                                <select name="type[{{ $index }}]" id="type.{{ $index }}" class="form-select">
                                                                    <option value=""> - CHOOSE - </option>
                                                                    <option value="daily" {{ $value->type == 'daily' ? 'selected' : '' }}>Daily</option>
                                                                    <option value="monthly" {{ $value->type == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                                                    <option value="divided_by_22" {{ $value->type == 'divided_by_22' ? 'selected' : '' }}>Divided by 22</option>
                                                                </select>
                                                                <div class="error-field"></div>
                                                            </div>

                                                            <!-- Start Date -->
                                                            <div class="col-12 col-md-4 mb-3">
                                                                <label for="start_date.{{ $index }}" class="form-label">Start Date</label>
                                                                <input type="datetime-local" name="start_date[{{ $index }}]" class="form-control" id="start_date.{{ $index }}"
                                                                    value="{{ $value->start_date ? \Carbon\Carbon::parse($value->start_date)->format('Y-m-d\TH:i') : '' }}">
                                                                <div class="error-field"></div>
                                                            </div>

                                                            <!-- End Date -->
                                                            <div class="col-12 col-md-4 mb-3">
                                                                <label for="end_date.{{ $index }}" class="form-label">End Date <span class="text-muted">(optional)</span></label>
                                                                <input type="datetime-local" name="end_date[{{ $index }}]" class="form-control" id="end_date.{{ $index }}"
                                                                    value="{{ $value->end_date ? \Carbon\Carbon::parse($value->end_date)->format('Y-m-d\TH:i') : '' }}">
                                                                <div class="error-field"></div>
                                                            </div>

                                                            <!-- Is Taxable -->
                                                            <div class="col-12 col-md-4 mb-3 d-flex align-items-center">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="is_taxable[{{ $index }}]" value="1" id="is_taxable.{{ $index }}" {{ $value->isTaxable ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="is_taxable.{{ $index }}">Is Taxable</label>
                                                                </div>
                                                                <div class="error-field ms-2"></div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            {{-- Show 1 empty group --}}
                                            <div class="card mb-3 grouped-field" data-index="0">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-end">
                                                        <button type="button" class="btn btn-danger ms-auto remove-group-btn">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </div>
                                                    <div class="row">

                                                        <div class="col-12 col-md-4 mb-3">
                                                            <label for="earning.0" class="form-label">Earning</label>
                                                            <select name="earning[0]" id="earning.0" class="form-select earning-select">
                                                                <option value=""> - CHOOSE - </option>
                                                                @foreach($earnings as $earning)
                                                                    <option value="{{ $earning->id }}">{{ $earning->name }}</option>
                                                                @endforeach
                                                            </select>
                                                            <div class="error-field"></div>
                                                        </div>

                                                        <div class="col-12 col-md-4 mb-3">
                                                            <label for="first_term.0" class="form-label">First Term</label>
                                                            <input type="text" name="first_term[0]" class="form-control" id="first_term.0" value="0">
                                                            <div class="error-field"></div>
                                                        </div>

                                                        <div class="col-12 col-md-4 mb-3">
                                                            <label for="second_term.0" class="form-label">Second Term</label>
                                                            <input type="text" name="second_term[0]" class="form-control" id="second_term.0" value="0">
                                                            <div class="error-field"></div>
                                                        </div>

                                                        <div class="col-12 col-md-4 mb-3">
                                                            <label for="type.0" class="form-label">Type</label>
                                                            <select name="type[0]" class="form-select earning-select" id="type.0">
                                                                <option value=""> - CHOOSE - </option>
                                                                <option value="daily">Daily</option>
                                                                <option value="monthly">Monthly</option>
                                                                <option value="divided_by_22">Divided by 22</option>
                                                            </select>
                                                            <div class="error-field"></div>
                                                        </div>

                                                        <div class="col-12 col-md-4 mb-3">
                                                            <label for="start_date.0" class="form-label">Start Date</label>
                                                            <input type="datetime-local" name="start_date[0]" class="form-control" id="start_date.0">
                                                            <div class="error-field"></div>
                                                        </div>

                                                        <div class="col-12 col-md-4 mb-3">
                                                            <label for="end_date.0" class="form-label">End Date <span class="text-muted">(optional)</span></label>
                                                            <input type="datetime-local" name="end_date[0]" class="form-control" id="end_date.0">
                                                            <div class="error-field"></div>
                                                        </div>

                                                        <div class="col-12 col-md-4 mb-3 d-flex align-items-center">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="is_taxable[0]" value="1" id="is_taxable.0">
                                                                <label class="form-check-label" for="is_taxable.0">Is Taxable</label>
                                                            </div>
                                                            <div class="error-field ms-2"></div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <button type="button" id="add-group-btn" class="btn btn-secondary text-uppercase fw-bold px-4 py-2">Add Items</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 d-flex justify-content-end">
                    <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                        Update <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script>
$(function() {

    const url = $('#form').attr('action');
    post(url);

    let groupIndex = {{ $data->isNotEmpty() ? count($data) : 1 }};

    $('#add-group-btn').on('click', function(e) {
        e.preventDefault();

        const newGroup = `
            <div class="card mb-3 grouped-field" data-index="${groupIndex}">
                <div class="card-body">
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-danger ms-auto remove-group-btn">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-4 mb-3">
                            <label for="earning.${groupIndex}" class="form-label">Earning</label>
                            <select name="earning[${groupIndex}]" id="earning.${groupIndex}" class="form-select earning-select">
                                <option value=""> - CHOOSE - </option>
                                @foreach($earnings as $earning)
                                    <option value="{{ $earning->id }}">{{ $earning->name }}</option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>

                        <div class="col-12 col-md-4 mb-3">
                            <label for="first_term.${groupIndex}" class="form-label">First Term</label>
                            <input type="text" name="first_term[${groupIndex}]" id="first_term.${groupIndex}" class="form-control" value="0">
                            <div class="error-field"></div>
                        </div>

                        <div class="col-12 col-md-4 mb-3">
                            <label for="second_term.${groupIndex}" class="form-label">Second Term</label>
                            <input type="text" name="second_term[${groupIndex}]" id="second_term.${groupIndex}" class="form-control" value="0">
                            <div class="error-field"></div>
                        </div>

                        <div class="col-12 col-md-4 mb-3">
                            <label for="type.${groupIndex}" class="form-label">Type</label>
                            <select name="type[${groupIndex}]" id="type.${groupIndex}" class="form-select">
                                <option value=""> - CHOOSE - </option>
                                <option value="daily">Daily</option>
                                <option value="monthly">Monthly</option>
                                <option value="divided_by_22">Divided by 22</option>
                            </select>
                            <div class="error-field"></div>
                        </div>

                        <div class="col-12 col-md-4 mb-3">
                            <label for="start_date.${groupIndex}" class="form-label">Start Date</label>
                            <input type="datetime-local" name="start_date[${groupIndex}]" id="start_date.${groupIndex}" class="form-control">
                            <div class="error-field"></div>
                        </div>

                        <div class="col-12 col-md-4 mb-3">
                            <label for="end_date.${groupIndex}" class="form-label">End Date <span class="text-muted">(optional)</span></label>
                            <input type="datetime-local" name="end_date[${groupIndex}]" id="end_date.${groupIndex}" class="form-control">
                            <div class="error-field"></div>
                        </div>

                        <div class="col-12 col-md-4 mb-3 d-flex align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_taxable[${groupIndex}]" value="1" id="is_taxable.${groupIndex}">
                                <label class="form-check-label" for="is_taxable.${groupIndex}">Is Taxable</label>
                            </div>
                            <div class="error-field ms-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#fields-container').append(newGroup);
        groupIndex++;
    });

    // Remove group handler
    $('#fields-container').on('click', '.remove-group-btn', function() {
        $(this).closest('.grouped-field').remove();
    });

    // Handle earning select change for both existing and newly added groups
    $('#fields-container').on('change', '.earning-select', function() {
        const $select = $(this);
        const earningId = $select.val();
        const groupDiv = $select.closest('.grouped-field');
        const index = groupDiv.data('index');

        if (!earningId) {
            // Clear first_term and second_term if no earning selected
            groupDiv.find(`input[name="first_term[${index}]"]`).val('0');
            groupDiv.find(`input[name="second_term[${index}]"]`).val('0');
            return;
        }

        // Fetch data from API
        $.ajax({
            url: '{{ route('hris.employee.earnings', ['employee_no' => $employee_no]) }}',
            method: 'GET',
            data: { earning_id: earningId },
            success: function(response) {
                
                // Assuming response has first_term and second_term fields
                groupDiv.find(`input[name="first_term[${index}]"]`).val(response.data.first_term || '0');
                groupDiv.find(`input[name="second_term[${index}]"]`).val(response.data.second_term || '0');
                groupDiv.find(`input[name="is_taxable[${index}]"]`).prop('checked', response.data.is_taxable == 1);

            },
            error: function() {
                // Optionally handle errors here
                alert('Failed to fetch amount data.');
            }
        });
    });

});
</script>
@endsection

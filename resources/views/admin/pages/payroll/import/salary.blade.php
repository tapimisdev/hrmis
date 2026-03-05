@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Import Salary Payroll Registry" subtitle="Upload previous payroll registry">

        </x-header>

        <div class="container">
            <div class="card">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs">
                        @foreach($options as $key => $link)
                        <li class="nav-item">
                            <a 
                                class="{{ $key == $active ? 'active' : '' }} text-uppercase fw-bold nav-link {{ request('option') == str_replace(' ', '_', $key) ? 'active' : '' }}" 
                                href="{{$link}}"
                            >
                                {{ $key }}
                            </a>
                        </li>
                    @endforeach
                    </ul>
                </div>
                <div class="card-body py-5 px-4">
                    <form id="form" enctype="multipart/form-data" action="{{route('registry.salary.store')}}" method="post">
                        @method('POST')
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-7 mb-3">
                                <label for="label" class="mb-2">Label <span class="text-danger">*</span></label>
                                <input type="text" name="label" id="label" class="form-control">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-5 mb-3">
                                <label for="employment_type" class="mb-2">Employment Type <span class="text-danger">*</span></label>
                                <select name="employment_type" id="employment_type" class="form-select">
                                    <option value=""> - CHOOSE - </option>
                                    @foreach($employment_types as $types)
                                        <option value="{{$types->id}}"> {{$types->name}} </option>
                                    @endforeach
                                </select>
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-4 mb-3">
                                <label for="date" class="mb-2">Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" id="date" class="form-control">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-4 mb-3">
                                <label for="cut_off_period" class="mb-2">Cutoff Period <span class="text-danger">*</span></label>
                                <select name="cut_off_period" id="cut_off_period" class="form-select">
                                    <option value=""> - CHOOSE - </option>
                                    <option value="first_cutoff">1st Cutoff</option>
                                    <option value="second_cutoff">2nd Cutoff</option>
                                </select>
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-4 mb-3">
                                <label for="type" class="mb-2">File <span class="text-danger">*</span></label>
                                <input type="file" name="file" id="file" class="form-control">
                                <div class="error-field"></div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" id="btn-parse" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                                Parse
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
$(function() {

    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const $form = $('#form');
    let parsedData = null;

    /*
    |--------------------------------------------------------------------------
    | FIRST SUBMIT (PARSE)
    |--------------------------------------------------------------------------
    */
    function parseForm() {

        const formData = new FormData($form[0]);
        const url = $form.attr('action');

        $('.error-field').html('');
        $('#dynamic-table, #parsed-info, #next-import-btn, #go-back-btn').remove();

        const $btn = $('#btn-parse');
        const originalBtnHtml = $btn.html();

        $.ajax({
            url: url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Parsing...'
                );
            },
            success: function(response) {

                $btn.prop('disabled', false).html(originalBtnHtml);
                $form.hide();

                if (response && response.data && response.data.length) {

                    parsedData = response;

                    displayParsedInfo(parsedData);
                    createDynamicTable(parsedData.data);

                    const buttonsHtml = `
                        <div class="mt-3 d-flex justify-content-end gap-3">
                            <button id="go-back-btn" class="btn btn-danger px-5 py-3 text-uppercase fw-bold">Go Back</button>
                            <button id="next-import-btn" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">Import</button>
                        </div>`;

                    $('.action-btn').after(buttonsHtml);

                } else {
                    alert('No data returned.');
                }
            },
            error: function(xhr) {

                $btn.prop('disabled', false).html(originalBtnHtml);

                if(xhr.status === 422 && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    for (const key in errors) {
                        $(`[name="${key}"]`).next('.error-field')
                            .html('<span class="text-danger">' + errors[key][0] + '</span>');
                    }
                } else {
                    alert('An error occurred while parsing.');
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | SECOND SUBMIT (IMPORT)
    |--------------------------------------------------------------------------
    */
    function importParsedData() {

        if (!parsedData) return alert('No data to import.');

        const url = $form.attr('action');
        const $btn = $('#next-import-btn');
        const originalBtnHtml = $btn.html();

        const updatedData = [];
        const headers = Object.keys(parsedData.data[0]);

        $('#dynamic-table tbody tr').each(function () {
            const rowData = {};
            $(this).find('td input').each(function (index) {
                rowData[headers[index]] = $(this).val();
            });
            updatedData.push(rowData);
        });

        parsedData.data = updatedData;

        $.ajax({
            url: url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: JSON.stringify({ isImport: true, data: parsedData }),
            contentType: 'application/json',
            beforeSend: function () {
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Importing...'
                );
            },
            success: function (response) {
                console.log(response);
                $btn.prop('disabled', false).html(originalBtnHtml);

                Swal.fire({
                    title: 'Import Successful!',
                    text: 'Do you want to open this payroll registry?',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, open it',
                    cancelButtonText: 'No'
                }).then((result) => {
        
                    if (result.isConfirmed) {
                        window.open(response.redirect, '_blank', 'noopener,noreferrer');
                    } 

                    clearFields();
                    resetUI()

                });
            },
            error: function () {
                $btn.prop('disabled', false).html(originalBtnHtml);
                alert('An error occurred while importing.');
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | UI HELPERS
    |--------------------------------------------------------------------------
    */

    function resetUI() {
        $('#dynamic-table, #parsed-info, #next-import-btn, #go-back-btn').remove();
        $form.show();
    }

    function displayParsedInfo(parsed) {

        const infoHtml = `
            <div id="parsed-info" class="mb-3 text-uppercase">
                <strong>Label:</strong> ${parsed.label} <br>
                <strong>Employment Type:</strong> ${parsed.employment_type == 1 ? 'Regular' : 'Contract of Service'} <br>
                <strong>Period Covered:</strong> ${parsed.period_covered} <br>
                <strong>Payroll Type:</strong> ${parsed.type}
            </div>
        `;

        $form.closest('.card-body').append(infoHtml);
    }

    function createDynamicTable(data) {

        let table = `
            <div class="table-responsive" style="height: 600px;">
                <table id="dynamic-table" class="table table-bordered mt-2" >
                <thead><tr>`;

        const headers = Object.keys(data[0]);

        headers.forEach(header => {
            table += '<th>' + header + '</th>';
        });

        table += '</tr></thead><tbody>';

        data.forEach(row => {

            table += '<tr>';

            headers.forEach(header => {

                if (header == 'Name' || header == 'Position') {
                    table += '<td style="width:300px;">' +
                                '<input type="text" class="form-control" ' +
                                'style="width:300px;"' +
                                'value="' + (row[header] ?? '0') + '">' +
                            '</td>';
                } else {
                    table += '<td>' +
                                '<input type="text" class="form-control" ' +
                                'style="width:200px;"' +
                                'value="' + (row[header] ?? '0') + '">' +
                            '</td>';
                }

            });
                
            table += '</tr>';
        });

        table += '</tbody></table></div><div class="action-btn"></div>';

        $form.closest('.card-body').append(table);
    }

    function clearFields() {
        $('#form').find('input[type="text"], input[type="number"], input[type="email"], textarea').val('');
        $('#form').find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
        $('#form').find('select').prop('selectedIndex', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | EVENTS
    |--------------------------------------------------------------------------
    */

    $form.on('submit', function(e) {
        e.preventDefault();
        parseForm();
    });

    $(document).on('click', '#next-import-btn', function() {
        importParsedData();
    });

    $(document).on('click', '#go-back-btn', function() {
        resetUI();
    });

});
</script>
@endsection
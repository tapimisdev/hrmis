@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Import Hazard Payroll Registry" subtitle="Upload previous hazard payroll registry">

        </x-header>

        <div class="container">
            <div class="card">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs">
                        @foreach($options as $key => $link)
                        <li class="nav-item">
                            <a
                                class="{{ $key == $active ? 'active' : '' }} text-uppercase fw-bold nav-link {{ request()->url() === $link ? 'active' : '' }}"
                                href="{{ $link ?: 'javascript:void(0)' }}"
                            >
                                {{ $key }}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-body py-5 px-4">
                    <form id="form" enctype="multipart/form-data" action="{{ route('registry.hazard.store') }}" method="post">
                        @method('POST')
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label for="label" class="mb-2">Label <span class="text-danger">*</span></label>
                                <input type="text" name="label" id="label" class="form-control">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-3 mb-3">
                                <label for="month" class="mb-2">Month <span class="text-danger">*</span></label>
                                <input type="month" name="month" id="month" class="form-control">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 col-md-3 mb-3">
                                <label for="file" class="mb-2">File <span class="text-danger">*</span></label>
                                <input type="file" name="file" id="file" class="form-control" accept=".xls,.xlsx,.csv">
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

@section('styles')
<style>
    .excel-table-wrapper {
        padding: 0;
        max-height: 600px;
        overflow: auto;
        border: 1px solid var(--bs-border-color);
        border-radius: 14px;
        background: var(--bs-body-bg);
    }

    #dynamic-table.excel-table {
        width: 100%;
        min-width: max-content;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 12px;
        margin-top: 0 !important;
    }

    #dynamic-table thead th {
        position: sticky;
        top: 0;
        z-index: 25;
        background: var(--bs-tertiary-bg);
        color: var(--bs-secondary-color);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    #dynamic-table th,
    #dynamic-table td {
        border-right: 1px solid var(--bs-border-color);
        border-bottom: 1px solid var(--bs-border-color);
        padding: 8px 10px;
        vertical-align: middle;
        background: var(--bs-body-bg);
    }

    #dynamic-table th:first-child,
    #dynamic-table td:first-child {
        border-left: 1px solid var(--bs-border-color);
    }

    #dynamic-table tbody tr:nth-child(even) td {
        background: color-mix(in srgb, var(--bs-tertiary-bg) 55%, transparent);
    }

    #dynamic-table .employee-name {
        font-weight: 700;
    }

    #dynamic-table .employee-position {
        color: var(--bs-secondary-color);
        font-size: 10px;
        margin-top: 2px;
        white-space: pre-wrap;
    }

    #dynamic-table input.form-control,
    #dynamic-table textarea.form-control {
        border-radius: 8px;
        font-size: 12px;
        box-shadow: none;
    }

    #parsed-info .card,
    #parsed-info .card * {
        text-transform: uppercase;
    }

    .preview-issue-link {
        font-size: 12px !important;
        display: block;
        width: fit-content;
        text-align: left;
        line-height: 1.35;
        margin-bottom: 6px;
    }

    .preview-search-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }

    .preview-search-toolbar .input-group {
        max-width: 440px;
    }

    .preview-search-count {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
    }

    #dynamic-table tbody tr.preview-search-match td {
        background: #fff3cd !important;
    }
</style>
@endsection

@section('scripts')
<script>
$(function() {

    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const $form = $('#form');
    let parsedData = null;

    function parseForm() {
        const formData = new FormData($form[0]);
        const url = $form.attr('action');

        $('.error-field').html('');
        cleanupPreviewDom();

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
                    createDynamicTable(parsedData.data, parsedData.preview_headers, parsedData.field_order, parsedData.errors);

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

                if (xhr.status === 422 && xhr.responseJSON?.error_type === 'missing_headers') {
                    showMissingHeadersError(xhr.responseJSON);
                } else if (xhr.status === 422 && xhr.responseJSON.errors) {
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

    function importParsedData() {
        if (!parsedData) return alert('No data to import.');

        const url = $form.attr('action');
        const $btn = $('#next-import-btn');
        const originalBtnHtml = $btn.html();
        const updatedData = [];
        const headers = parsedData.field_order ?? Object.keys(parsedData.data[0]);

        $('#dynamic-table tbody tr').each(function () {
            const rowData = {};
            headers.forEach((header) => {
                rowData[header] = $(this).find(`[data-field="${header}"]`).val();
            });
            updatedData.push(rowData);
        });

        parsedData.data = updatedData;

        $.ajax({
            url: url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: JSON.stringify({
                isImport: true,
                data: parsedData
            }),
            contentType: 'application/json',
            beforeSend: function () {
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Importing...'
                );
            },
            success: function (response) {
                $btn.prop('disabled', false).html(originalBtnHtml);

                Swal.fire({
                    title: 'Import Successful!',
                    text: 'Do you want to open this payroll registry?',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, open it',
                    cancelButtonText: 'No'
                }).then((result) => {
                    if (result.isConfirmed && response.redirect) {
                        window.open(response.redirect, '_blank', 'noopener,noreferrer');
                    }

                    clearFields();
                    resetUI();
                });
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html(originalBtnHtml);
                alert('An error occurred while importing.');
            }
        });
    }

    function cleanupPreviewDom() {
        $('#dynamic-table').closest('.table-responsive').remove();
        $('#parsed-info, #next-import-btn, #go-back-btn, .action-btn, .preview-search-toolbar').remove();
    }

    function resetUI() {
        cleanupPreviewDom();
        $form.show();
    }

    function showMissingHeadersError(payload) {
        const missingHeaders = payload.missing_headers ?? [];
        const headersHtml = missingHeaders.length
            ? `<div class="text-start mt-3">
                    <div class="fw-bold mb-2">Missing required headers</div>
                    <ul class="mb-0 ps-3">
                        ${missingHeaders.map((header) => `<li>${header}</li>`).join('')}
                    </ul>
               </div>`
            : '';

        Swal.fire({
            icon: 'error',
            title: payload.title ?? 'Template headers do not match',
            html: `
                <div class="text-start">
                    <div>${payload.message ?? 'The uploaded file could not be parsed because the template headers do not match the expected format.'}</div>
                    ${headersHtml}
                </div>
            `,
            confirmButtonText: 'Review File'
        });
    }

    function displayParsedInfo(parsed) {
        const errorCount = parsed.errors?.length ?? 0;
        const previewErrors = parsed.errors?.slice(0, 5) ?? [];
        const remainingErrors = parsed.errors?.slice(5) ?? [];
        const statusBadge = errorCount > 0
            ? `<span class="fs-6 badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Needs Review: ${errorCount} issue(s)</span>`
            : `<span class="fs-6 badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Ready to Import</span>`;

        const errorHtml = errorCount > 0
            ? `
                <div class="alert alert-danger mt-3 mb-0">
                    <div class="fw-bold mb-2 text-uppercase">Issues found</div>
                    <div class="d-flex flex-column align-items-start">
                        ${previewErrors.map(error => `<button type="button" class="btn btn-link p-0 mb-0 text-danger text-decoration-none preview-issue-link" data-error-name="${escapeHtml(error?.name ?? '')}">${formatIssueLabel(error)}</button>`).join('')}
                    </div>
                    ${remainingErrors.length > 0 ? `
                        <button
                            class="btn btn-sm btn-outline-danger mt-3 d-inline-block"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#more-parse-issues"
                            aria-expanded="false"
                            aria-controls="more-parse-issues"
                        >
                            View more (${remainingErrors.length})
                        </button>
                        <div class="collapse mt-3" id="more-parse-issues">
                            <div class="d-flex flex-column align-items-start">
                                ${remainingErrors.map(error => `<button type="button" class="btn btn-link p-0 mb-0 text-danger text-decoration-none preview-issue-link" data-error-name="${escapeHtml(error?.name ?? '')}">${formatIssueLabel(error)}</button>`).join('')}
                            </div>
                        </div>
                    ` : ''}
                </div>
            `
            : '';

        const infoHtml = `
            <div id="parsed-info" class="mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div>
                                <div class="text-muted text-uppercase small fw-bold">Parsed Payroll Summary</div>
                                <div class="fs-5 fw-bold mt-1">${parsed.label}</div>
                            </div>
                            <div>${statusBadge}</div>
                        </div>

                        <div class="row mt-4 g-3">
                            <div class="col-12 col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small text-uppercase fw-bold">Label</div>
                                    <div class="fw-semibold mt-1">${parsed.label}</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small text-uppercase fw-bold">Coverage</div>
                                    <div class="fw-semibold mt-1">${parsed.coverage}</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small text-uppercase fw-bold">Period Covered</div>
                                    <div class="fw-semibold mt-1">${parsed.period_covered}</div>
                                </div>
                            </div>
                        </div>

                        ${errorHtml}
                    </div>
                </div>
            </div>
        `;

        $form.closest('.card-body').append(infoHtml);
    }

    function formatIssueLabel(error) {
        return (error?.name ?? '').trim();
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function getPreviewSearchText($row) {
        return $row.find('td').map(function() {
            const $cell = $(this);
            const $field = $cell.find('input, textarea, select').first();
            return $field.length ? String($field.val() ?? '').trim() : $cell.text().trim();
        }).get().join(' ').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function applyPreviewSearch(term = '') {
        const query = String(term).trim().toLowerCase();
        let matches = 0;
        let $firstMatch = null;
        const $wrapper = $('#dynamic-table').closest('.excel-table-wrapper');

        $('#dynamic-table tbody tr').each(function() {
            const $row = $(this);
            const isMatch = query !== '' && getPreviewSearchText($row).includes(query);
            $row.toggleClass('preview-search-match', isMatch);
            if (isMatch) {
                matches++;
                if (!$firstMatch) {
                    $firstMatch = $row;
                }
            }
        });

        $('#preview-search-count').text(query ? `${matches} result${matches === 1 ? '' : 's'} highlighted` : '');

        if (query && $firstMatch && $wrapper.length) {
            const rowElement = $firstMatch.get(0);
            const wrapperElement = $wrapper.get(0);
            const targetScrollTop = rowElement.offsetTop - ((wrapperElement.clientHeight - rowElement.offsetHeight) / 2);

            $wrapper.stop(true).animate({
                scrollTop: Math.max(targetScrollTop, 0)
            }, 180);
        }
    }

    function createDynamicTable(data, previewHeaders = {}, fieldOrder = null, errors = []) {
        let table = `
            <div class="preview-search-toolbar">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input
                        type="search"
                        id="preview-search-input"
                        class="form-control"
                        placeholder="Search preview rows"
                        autocomplete="off"
                    >
                    <button type="button" class="btn btn-outline-secondary" id="preview-search-clear">Clear</button>
                </div>
                <div class="preview-search-count" id="preview-search-count"></div>
            </div>
            <div class="excel-table-wrapper table-responsive">
                <table id="dynamic-table" class="excel-table">
                <thead><tr class="header-labels">`;

        const headers = fieldOrder ?? Object.keys(data[0]);
        const issueMap = new Map();

        (errors ?? []).forEach((error) => {
            const key = (error?.name ?? '').trim().toUpperCase();
            if (!key) return;
            const reasons = issueMap.get(key) ?? [];
            reasons.push(String(error?.reason ?? '').trim());
            issueMap.set(key, reasons);
        });

        table += '<th style="min-width: 90px;">Action</th>';

        headers.forEach(header => {
            table += '<th>' + (previewHeaders[header] ?? header) + '</th>';
        });

        table += '</tr></thead><tbody>';

        data.forEach(row => {
            const rowName = String(row['Name'] ?? row['Employee'] ?? '').trim().toUpperCase();
            const rowIssues = issueMap.get(rowName) ?? [];
            table += '<tr data-row-name="' + escapeHtml(rowName) + '">';
            table += `
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-sm btn-outline-danger delete-preview-row" title="Delete row">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            `;

            headers.forEach(header => {
                const value = row[header] ?? '';
                const isMissingEmployeeNo = header === 'Employee No' && String(value).trim() === '';
                const isMissingName = header === 'Name' && String(value).trim() === '';
                const isMissingPosition = header === 'Position' && String(value).trim() === '';
                const isUnknownEmployeeNo = header === 'Employee No' && rowIssues.some((reason) => reason === 'Unknown employee no');
                const isInactiveEmployee = header === 'Name' && rowIssues.some((reason) => reason === 'Inactive employee');
                const isInvalidField = isMissingEmployeeNo || isMissingName || isMissingPosition || isUnknownEmployeeNo || isInactiveEmployee;

                if (header === 'Name') {
                    table += '<td style="width:500px;">' +
                                '<textarea class="form-control ' + (isInvalidField ? 'is-invalid' : '') + '" ' +
                                `data-field="${header}" placeholder="Edit employee name" ` +
                                'style="width:500px; min-height:72px; white-space:pre-wrap;">' + value + '</textarea>' +
                            '</td>';
                } else if (header === 'Position') {
                    table += '<td style="width:300px;">' +
                                '<input type="text" class="form-control ' + (isInvalidField ? 'is-invalid' : '') + '" ' +
                                `data-field="${header}" ` +
                                'style="width:300px;"' +
                                'value="' + value + '">' +
                            '</td>';
                } else {
                    table += '<td>' +
                                '<input type="text" class="form-control ' + (isInvalidField ? 'is-invalid' : '') + '" ' +
                                `data-field="${header}" ` +
                                'style="width:200px;"' +
                                'value="' + value + '">' +
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

    $form.on('submit', function(e) {
        e.preventDefault();
        parseForm();
    });

    $(document).on('click', '#next-import-btn', function() {
        importParsedData();
    });

    $(document).on('click', '#go-back-btn', function() {
        clearFields();
        resetUI();
    });

    $(document).on('click', '.delete-preview-row', function() {
        $(this).closest('tr').remove();
        applyPreviewSearch($('#preview-search-input').val());
    });

    $(document).on('input', '#preview-search-input', function() {
        applyPreviewSearch($(this).val());
    });

    $(document).on('click', '#preview-search-clear', function() {
        $('#preview-search-input').val('').trigger('input').trigger('focus');
    });

    $(document).on('click', '.preview-issue-link', function() {
        const rowName = String($(this).data('error-name') ?? '').trim().toUpperCase();
        if (!rowName) return;

        const $wrapper = $('#dynamic-table').closest('.excel-table-wrapper');
        const $row = $('#dynamic-table tbody tr').filter(function() {
            return String($(this).data('row-name') ?? '').trim().toUpperCase() === rowName;
        }).first();

        if (!$row.length || !$wrapper.length) return;

        const rowElement = $row.get(0);
        const wrapperElement = $wrapper.get(0);
        const targetScrollTop = rowElement.offsetTop - ((wrapperElement.clientHeight - rowElement.offsetHeight) / 2);

        $wrapper.animate({
            scrollTop: Math.max(targetScrollTop, 0)
        }, 250);

        $row.addClass('table-warning');
        setTimeout(() => $row.removeClass('table-warning'), 1800);

        const $firstInvalid = $row.find('.is-invalid').first();
        if ($firstInvalid.length) {
            $firstInvalid.trigger('focus');
        }
    });
});
</script>
@endsection

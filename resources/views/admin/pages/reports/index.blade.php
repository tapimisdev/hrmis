@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Reports" subtitle="View reports based on processed payroll">
        </x-header>
        <div class="card">
            <div class="card-body">
                <div class="mt-4 mb-4">
                    <div class="alert alert-info text-uppercase fw-bold text-center">Pull out records based on your filtered configuration</div>
                    <hr class="mt-4">
                </div>
                <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-uppercase fw-bold active" id="pills-employee-tab" data-bs-toggle="pill" data-bs-target="#pills-employee" type="button" role="tab" aria-controls="pills-employee" aria-selected="true">Employee</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-uppercase fw-bold" id="pills-payroll-tab" data-bs-toggle="pill" data-bs-target="#pills-payroll" type="button" role="tab" aria-controls="pills-payroll" aria-selected="false">Payroll</button>
                    </li>
                </ul>
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="pills-employee" role="tabpanel" aria-labelledby="pills-employee-tab" tabindex="0">
                        <h6 class="text-uppercase mb-3 mt-4">Apply Filter</h6>
                        <form id="form" action="{{ route('reports.employee') }}" method="post">
                            @csrf
                            @method('POST')
                            <div class="row">
                                <div class="col-12 col-md-3 mb-3">
                                    <label for="employment_type" class="mb-2">Employment Type</label>
                                    <select name="employment_type" id="employment_type" class="form-select">
                                        <option value=""> - ALL - </option>
                                        @forelse($employment_types as $employment_type)
                                            <option value="{{ $employment_type->id }} "> {{ $employment_type->name }} </option>
                                        @empty
                                            <option value="">No Available employment_types</option>
                                        @endforelse
                                    </select>
                                </div>
                                <div class="col-12 col-md-3 mb-3">
                                    <label for="position" class="mb-2">Position</label>
                                    <select name="position" id="position" class="form-select">
                                        <option value=""> - ALL - </option>
                                        @forelse($positions as $position)
                                            <option value="{{ $position->id }} "> {{ $position->name }} </option>
                                        @empty
                                            <option value="">No Available Positions</option>
                                        @endforelse
                                    </select>
                                </div>
                                <div class="col-12 col-md-3 mb-3">
                                    <label for="sex" class="mb-2">Sex</label>
                                    <select name="sex" id="sex" class="form-select">
                                        <option value="">All</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3 mb-3">
                                    <label for="civil_status" class="mb-2">Civil Status</label>
                                    <select name="civil_status" id="civil_status" class="form-select">
                                        <option value="">All</option>
                                        <option value="single">Single</option>
                                        <option value="married">Married</option>
                                        <option value="divorced">Divorced</option>
                                        <option value="seperated">Seperated</option>
                                        <option value="widowed">Widowed</option>
                                        <option value="annulled">Annulled</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3 mb-3">
                                    <label for="date_hired" class="mb-2">Date Hired</label>
                                    <input type="date" name="date_hired" id="date_hired" class="form-control">
                                </div>
                                <div class="col-12 col-md-3 mb-3">
                                    <label for="tranche_id" class="mb-2">Tranche</label>
                                    <select name="tranche_id" id="tranche_id" class="form-select">
                                        <option value="">All</option>
                                        @forelse($tranches as $tranche)
                                            <option value="{{ $tranche->id }}} "> {{ \Carbon\Carbon::parse($tranche->date)->format('F d, Y') }} </option>
                                        @empty
                                            <option value="">No Available Tranches</option>
                                        @endforelse
                                    </select>
                                </div>
                                <div class="col-12 col-md-3 mb-3">
                                    <label for="salary_grade" class="mb-2">Salary Grade</label>
                                    <select name="salary_grade" id="salary_grade" class="form-select">
                                        <option value="">All</option>
                                        @forelse($salary_grades as $salary_grade)
                                            <option value="{{ $salary_grade }}} "> {{ $salary_grade }} </option>
                                        @empty
                                            <option value="">No Available salary$salary_grades</option>
                                        @endforelse
                                    </select>
                                </div>
                                <div class="col-12 col-md-3 mb-3">
                                    <label for="account_status" class="mb-2">Account Status</label>
                                    <select name="account_status" id="account_status" class="form-select">
                                        <option value="">All</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3 mb-3">
                                    <label for="date_resigned" class="mb-2">Month Resignee</label>
                                    <input type="monthyear" name="date_resigned" id="date_resigned" class="form-control">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">Search</button>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade show active" id="pills-payroll" role="tabpanel" aria-labelledby="pills-payroll-tab" tabindex="0">

                    </div>
                </div>

                <!-- Modal -->
                <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="previewModalLabel">Preview Result</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="form" action="reports.employee" class="form">
                                    <div class="d-flex gap-2 justify-content-start">
                                        <button type="button" data-type="csv" class="export_file px-5 py-2 btn btn-dark text-uppercase fw-bold"><i class="fa-solid fa-download me-1"></i> CSV</button>
                                        <button type="button" data-type="excel" class="export_file px-5 py-2 btn btn-dark text-uppercase fw-bold"><i class="fa-solid fa-download me-1"></i> Excel</button>
                                    </div>
                                </div>
                                <div class="mini-dashboard my-3">
                                    <div class="row">
                                        <div class="col-12 col-md-3 mb-3">
                                            <div class="card shadow" style="min-height: 100px;">
                                                <div class="card-body">
                                                    <label for="#" class="mb-2">Filters Applied </label>
                                                    <div class="applied_filter"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-3 mb-3">
                                            <div class="card shadow" style="min-height: 100px;">
                                                <div class="card-body">
                                                    <label for="#" class="mb-2">Results Returned </label>
                                                    <h1 class="text-start ps-1">
                                                        <span class="returned_results"></span>
                                                    </h1>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-3 mb-3">
                                            <div class="card shadow" style="min-height: 100px;">
                                                <div class="card-body">
                                                    <label for="#" class="mb-2">Out Of </label>
                                                    <h1 class="text-start ps-1">
                                                        <span class="total_results"></span>
                                                    </h1>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <table id="preview-result" class="table table-striped w-100"></table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<style>
    th {
        font-size: 14px;
    }
    td {
        font-size: 12px;
    }
</style>
@section('scripts')
<script>
$(function() {

    let countdown = 0;
    let timerInterval = null;

    const token = localStorage.getItem("auth_token");

    $('.export_file').on('click', function() {
        const $btn = $(this);
        const originalLabel = $btn.html();
        const type = $(this).data('type');

        $btn.prop('disabled', true).text('Downloading...');
        const token = localStorage.getItem('auth_token'); 

        axios.post(`/api/reports/employee/download`, 
            { fileType: type }, 
            { 
                responseType: 'blob',
                headers: {
                    Authorization: `Bearer ${token}`
                },
                withCredentials: true 
             })
            .then(response => {
                const link = document.createElement('a');
                link.href = URL.createObjectURL(new Blob([response.data], { type: 'text/csv' }));
                link.download = 'report.csv';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            })
            .catch(() => alert('Failed to download file.'))
            .finally(() => {
                $btn.prop('disabled', false).html(originalLabel); 
            });
    });

    $('#form').on('submit', function(e) {
        
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');

        const url = $form.attr('action');
        const data = $form.serialize();

        // Disable button and show loading text
        $submitBtn.prop('disabled', true).text('Loading...');

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            headers: {
                'Authorization': 'Bearer ' + token ,
                'Accept': 'application/json'
            },
            success: function(response) {
                const col = response.data ?? [];
                const appliedFilters = response.applied_filters ?? {};
                const filterText = Object.keys(appliedFilters)
                    .map(key => `${key}: ${appliedFilters[key]}`)
                    .join(', ');
                const returnedResults = response.filtered_count ?? 0;
                const totalResults = response.total_count ?? 0;

                let headers = [];
                let rows = [];

                // Check if data exists
                if (col.length > 1) {
                    headers = col[0];
                    rows = col.slice(1);
                } else if (col.length === 1) {
                    headers = col[0];
                    rows = [];
                } else {
                    headers = ['No data found'];
                    rows = [[]];
                }

                headers.push('Action');

                const columns = headers.map((header, index) => ({
                    title: header,
                    data: index,
                    width: '200px',
                    orderable: header !== 'Action',
                    searchable: header !== 'Action'
                }));

                if ($.fn.DataTable.isDataTable('#preview-result')) {
                    $('#preview-result').DataTable().destroy();
                    $('#preview-result').empty();
                }

                const table = $('#preview-result').DataTable({
                    data: rows,
                    columns: columns,
                    dom: 'Bfrtip',
                    responsive: true,
                    scrollX: true,
                    autoWidth: false,
                    columnDefs: [
                        {
                            targets: -1, 
                            data: null,
                            render: function(data, type, row, meta) {
                                return (
                                    `<button class="btn btn-primary download-btn" data-id="${meta.row}">
                                        <i class="fa-solid fa-download"></i>
                                    </button>`
                                );
                            }
                        },
                        { targets: '_all', width: '200px' } 
                    ],
                    language: {
                        emptyTable: "No data found"
                    },
                    drawCallback: function() {
                        $('#preview-result').css('table-layout', 'fixed');
                    }
                });

                if (rows.length > 0) {
                    setTimeout(() => {
                        table.order([0, 'asc']).draw();
                    }, 200);
                }

                $('#previewModal').modal('show');

                $('.applied_filter').html(`<code>${filterText || 'None'}</code>`);
                $('.returned_results').text(returnedResults);
                $('.total_results').text(totalResults);

                $('#preview-result').on('click', '.download-btn', function() {
                    const rowIndex = $(this).data('id');
                    const rowData = rows[rowIndex];
                    console.log('Download clicked for row:', rowData);
                });
            },

            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('Submit');
                setExpiration();
            }
        });
    });

    function setExpiration() {
        if (countdown > 0) return; 

        countdown = 60;
        console.log('Countdown started: 60s');

        timerInterval = setInterval(() => {
            countdown--;

            if (countdown <= 0) {
                clearInterval(timerInterval);
                countdown = 0;
                $('.modal').modal('hide');
            }
        }, 1000);
    }

});
</script>
@endsection



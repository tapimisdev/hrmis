@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Update Employee Records" subtitle="Employee's personal data sheet and portal leave-credits">
            <x-button-link 
                :href="route('hris.employee.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>
        <div class="row">
            <div class="col-12 col-md-3">
                @if($isExists)
                    <x-hris-menu active="offset-credits" empno="{{ $employee_no }}" />
                @endif
            </div>
            <div class="col-12 {{ $isExists ? 'col-md-9' : '' }}">
                <div class="accordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse"
                                data-bs-target="#flush-offset-credits" aria-expanded="true" aria-controls="flush-offset-credits">
                                Offset Credits
                            </button>
                        </h2>
                        <div id="flush-offset-credits" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <div class="mb-1 d-flex justify-content-end">
                                    <button class="btn btn-primary mt-3 mb-3 text-uppercase px-4"
                                            data-bs-toggle="modal"
                                            data-bs-target="#add-modal-credits">
                                        Add Credits
                                    </button>
                                </div>
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>As Of</th>  
                                            <th>Previous</th>
                                            <th>Earned</th>
                                            <th>Deduction</th>
                                            <th>Balance</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($credits as $index => $credit)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($credit->as_of)->format('F, Y') }}</td>
                                                <td>{{ $credit->previous }}</td>
                                                <td>
                                                    {{ $credit->earned }}
                                                </td>
                                                <td>
                                                    {{ $credit->deducted }}
                                                </td>
                                                <td>
                                                    {{ $credit->balance }}
                                                </td>
                                                <td>
                                                    @php
                                                        // Split remarks by newline to count actual lines
                                                        $lines = $credit->remarks ? explode("\n", $credit->remarks) : [];
                                                        $rows = count($lines);               // number of existing lines
                                                        $rows = max(2, min($rows, 8));       // minimum 2 rows, maximum 8 rows
                                                    @endphp

                                                    <textarea 
                                                        class="form-control restricted" 
                                                        rows="{{ $rows }}" 
                                                        readonly
                                                    >{{ $credit->remarks }}</textarea>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">
                                                    No credits found
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add-modal-credits" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="form" action="{{route('hris.employee.offset-credits.save', ['employee_no' => $employee_no])}}" method="POST">
                @csrf
                @php
                    use Carbon\Carbon;

                    $as_of_value = $latestCredits
                        ? Carbon::parse($latestCredits->as_of)->format('Y-m')
                        : Carbon::now()->format('Y-m');
                @endphp
                <input type="hidden" name="_method" id="method">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-uppercase" id="modalTitle">Add Credits</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 mb-3 col-md-3">
                                <label class="form-label">Previous Balance</label>
                                <input type="number" step="0.01" class="form-control restricted" name="previous_balance" id="previous_balance" readonly value="0">
                            </div>
                            <div class="col-12 mb-3 col-md-9">
                                <label class="form-label">As Of</label>
                                <input type="month"
                                    class="form-control"
                                    name="as_of"
                                    id="as_of"
                                    value="{{ $as_of_value }}">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 mb-3 col-md-4">
                                <label class="form-label">Earned</label>
                                <input type="number" step="0.01" class="form-control" name="earned" id="earned" value="0">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 mb-3 col-md-4">
                                <label class="form-label">Deduction</label>
                                <input type="number" step="0.01" class="form-control" name="deduction" id="deduction" value="0">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 mb-3 col-md-4">
                                <label class="form-label">Balance</label>
                                <input type="number" step="0.01" class="form-control restricted" name="balance" id="balance" readonly value="0">
                                <div class="error-field"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" rows="8" name="remarks" id="remarks"></textarea>
                                <div class="error-field"></div>
                            </div>
                             <div class="col-12 mb-3">
                                <input type="hidden" name="action" id="action" value="">
                                <div class="error-field"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" data-action="delete" class="btn-action btn btn-danger px-3 text-uppercase fw-bold">
                            Delete
                        </button>
                        <button type="submit" data-action="save" class="btn-action btn btn-primary px-3 text-uppercase fw-bold">
                            Save
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    $(function() {
        const formActionURL = $('#form').attr('action');
        const fetchCreditsURL = @json(route('hris.employee.offset-credits.fetch', ['employee_no' => $employee_no]));
        put(formActionURL);

        fetchCredits($('#as_of').val());

        $('#earned, #deduction').on('input', updateBalance);

        $('#as_of').on('change', function() {
            fetchCredits($(this).val());
        });

        $('.btn-action').on('click', function() {
            $('#action').val($(this).data('action'));
        });

        function updateBalance() {
            const previous = parseFloat($('#previous_balance').val()) || 0;
            const earned = parseFloat($('#earned').val()) || 0;
            const deduction = parseFloat($('#deduction').val()) || 0;
            $('#balance').val(previous + earned - deduction);
        }
        

        function fetchCredits(as_of) {
            if (!as_of) return;

            $.ajax({
                url: fetchCreditsURL,
                method: 'GET',
                data: { as_of: as_of },
                success: function(response) {
                    const res = response.data;
                    $('#previous_balance').val(res.previous_balance || 0);
                    $('#earned').val(res.current?.earned || 0);
                    $('#deduction').val(res.current?.deducted || 0);
                    $('#balance').val(res.current?.balance || 0);
                    $('#remarks').val(res.current?.remarks || '');

                    updateBalance();

                },
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
        }

        updateBalance();
    });
</script>
@endsection


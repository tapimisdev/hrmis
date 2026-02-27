<!-- Modal Component -->
<x-modal id="tcrModal" title="TCR Details">
    <div class="" style="font-family: Arial, sans-serif;">

        <form id="correctionForm" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id" id="correction-id">

            <!-- Tabs for Timelog / Remarks & Attachment -->
            <ul class="nav nav-tabs" id="tcrTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-uppercase fw-bold active" id="timelog-tab" data-bs-toggle="tab" data-bs-target="#timelog-tab-pane" type="button" role="tab">Timelog</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-uppercase fw-bold" id="remarks-tab" data-bs-toggle="tab" data-bs-target="#remarks-tab-pane" type="button" role="tab">Explanation & Attachment</button>
                </li>
            </ul>

            <div class="tab-content mt-3">

                <!-- Editable Timelog -->
                <div class="tab-pane fade show active" id="timelog-tab-pane" role="tabpanel">

                    <div class="row">
                        <div class="col-md-4">
                            <!-- Reference No (view-only) -->
                            <div class="mb-3">
                                <label class="form-label">Reference No</label>
                                <input type="text" id="reference_no" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <!-- Employee Name (view-only) -->
                            <div class="mb-3">
                                <label class="form-label">Employee Name</label>
                                <input type="text" id="employee-name" class="form-control" disabled>
                            </div>

                        </div>
                        <div class="col-md-4">
                            <!-- Date -->
                            <div class="mb-3">
                                <label class="form-label">Date <span class="text-danger">(required)</span></label>
                                <input type="date" name="date" id="date" class="form-control" disabled>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <!-- Time In -->
                            <div class="mb-3">
                                <label class="form-label">Time In <span class="text-danger">(required)</span></label>
                                <input type="time" name="time_in" id="time-in" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <!-- Break Out -->
                            <div class="mb-3">
                                <label class="form-label">Break Out</label>
                                <input type="time" name="break_out" id="break-out" class="form-control" disabled>
                            </div>

                        </div>
                        <div class="col-md-3">
                            <!-- Break In -->
                            <div class="mb-3">
                                <label class="form-label">Break In</label>
                                <input type="time" name="break_in" id="break-in" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <!-- Time Out -->
                            <div class="mb-3">
                                <label class="form-label">Time Out <span class="text-danger">(required)</span></label>
                                <input type="time" name="time_out" id="time-out" class="form-control" required disabled>
                            </div>
                        </div>

                    </div>

                    <!-- Overtime In/Out -->
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Overtime In</label>
                            <input type="time" name="overtime_in" id="overtime-in" class="form-control" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Overtime Out</label>
                            <input type="time" name="overtime_out" id="overtime-out" class="form-control" disabled>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold text-uppercase" style="font-size: 11px">
                                Concern Type
                                <span class="text-danger fw-bold text-uppercase" style="font-size: 10px">*</span>
                            </label>

                            <div class="d-flex gap-4 @error('concern') is-invalid @enderror">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        id="system_out_of_order"
                                        name="concern"
                                        value="OO"
                                        disabled
                                    />
                                    <label class="form-check-label" style="opacity: 1 !important" for="concern_oo">
                                        OO - System Out of Order
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        id="failure_to_entry"
                                        name="concern"
                                        value="F"
                                        disabled
                                    />
                                    <label class="form-check-label" style="opacity: 1 !important" for="concern_f">
                                        F - Failure to perform actions
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        id="incorrect_entry"
                                        name="concern"
                                        value="IE"
                                        disabled
                                    />
                                    <label class="form-check-label" style="opacity: 1 !important" for="concern_ie">
                                        IE - Incorrect Entry
                                    </label>
                                </div>
                            </div>

                            @error('concern')
                                <span class="text-danger fw-bold text-uppercase" style="font-size: 10px">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>

                </div>

                <div class="tab-pane fade" id="remarks-tab-pane" role="tabpanel">
                    <div class="mb-3">
                        <label class="form-label">Explanation</label>
                        <p id="remarks" style="white-space: pre-wrap; padding: 0.5rem; border-radius: 5px;"></p>
                    </div>

                    <div class="mb-3">
                        <iframe id="attachment-pdf" src="" style="width: 100%; height: 400px;" frameborder="0" class="d-none"></iframe>

                        <img id="attachment-img" src="" alt="Attachment" class="img-fluid d-none mb-3" />

                        <a href="#" id="attachment-link" target="_blank" class="d-none">View Attachment</a>
                    </div>
                </div>

            </div>
        </form>

    </div>

    <x-slot name="footer" id="actions">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-primary px-4 py-2 approve-button">
                <i class="me-2 fas fa-check"></i> Approved
            </button>
            <button type="button" class="btn btn-danger px-4 py-2 reject-button">
                <i class="me-2 fas fa-xmark"></i> Reject
            </button>
        </div>
    </x-slot>
</x-modal>
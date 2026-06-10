<x-modal id="trainLawModal" title="Train Law" size="modal-lg">
    <form id="trainLawForm">
        @csrf
        <input type="hidden" id="trainlaw_id" value="">
        <input type="hidden" id="form_method" value="POST">

        <div>
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Year</label>
                    <input type="text" class="form-control" name="year" id="year" placeholder="e.g. 2026">
                    <small class="text-danger" id="err_year"></small>
                </div>
            </div>
        </div>
    </form>

    <x-slot name="footer">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-danger px-4 py-2" data-bs-dismiss="modal">
                <i class="fa-solid fa-xmark me-1"></i> Close
            </button>

            <button type="submit" form="trainLawForm" class="btn btn-primary px-4 py-2" id="saveBtn">
                <i class="fa-solid fa-floppy-disk me-1"></i> Save
            </button>
        </div>
    </x-slot>
</x-modal>

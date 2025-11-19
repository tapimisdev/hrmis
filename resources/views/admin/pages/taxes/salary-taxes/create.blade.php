<x-modal id="taxSalaryModal" icon="fa-solid fa-plus" title="Add Year" size="modal-lg">
    <form id="myForm" action="{{ route('tax.salary.store') }}" method="post">
        @csrf
        @method('POST')
         <div class="row">
            <div class="col-12 col-md-12 mb-3">
                <label class="mb-2" for="year">Year <span class="text-danger">*</span></label>
                <input type="text" id="year" name="year" class="form-control">
                <div class="year_error error-field"></div>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-danger px-4 py-2" data-bs-dismiss="modal">
                <i class="fa-solid fa-xmark me-1"></i> Close
            </button>
            <button type="submit" id="add-btn" class="btn btn-primary px-4 pt-2">
                <i class="fa-solid fa-plus me-1"></i> Add
            </button>
        </div>
    </form>
</x-modal>
<!-- Modal Component -->
<x-modal id="myModal" title="Modal" size="modal-lg">

    <form id="myForm" >
        @csrf
        <div class="modal-body">
            <div class="mb-2">
                <label for="name" class="form-label">Name</label>
                <input type="text" name="name" class="form-control form-control-sm" id="name" placeholder="Enter name here...">
                <span id="name_error" class="text-danger d-none"></span>
            </div>
        </div>
    </form>

    <x-slot name="footer">
        <div class="d-flex justify-content-end gap-2 mt-3">
            <button id="update-button" type="button" class="btn btn-secondary px-4 py-2">
                <i class="fa-solid fa-pen-to-square me-1"></i> Update
            </button>
            <button id="submit-button" type="button" class="btn btn-primary px-4 py-2">
                <i class="fa-solid fa-plus me-1"></i> Add
            </button>
            <button type="button" class="btn btn-danger px-4 py-2" data-bs-dismiss="modal">
                <i class="fa-solid fa-xmark me-1"></i> Close
            </button>
        </div>
    </x-slot>
</x-modal>
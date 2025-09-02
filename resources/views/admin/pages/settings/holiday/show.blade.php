<!-- Modal Component -->
<x-modal id="holidayModal" title="Holiday Details" size="modal-lg">
    <div class="p-4" style="font-family: Arial, sans-serif;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Holiday Details</h3>
            <small class="text-muted">Holiday ID: <span id="holiday-id"></span></small>
        </div>

        <table class="table table-bordered">
            <tr>
                <th>Name:</th>
                <td id="holiday-name"></td>
            </tr>
            <tr>
                <th>Date:</th>
                <td id="holiday-date"></td>
            </tr>
            <tr>
                <th>Type:</th>
                <td id="holiday-type"></td>
            </tr>
            <tr>
                <th>Repeats Yearly:</th>
                <td><span id="holiday-is-repeating" class="badge"></span></td>
            </tr>
            <tr>
                <th>Created At:</th>
                <td id="holiday-created-at"></td>
            </tr>
        </table>
    </div>

    <x-slot name="footer">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-danger px-4 py-2" data-bs-dismiss="modal">
                <i class="fa-solid fa-xmark me-1"></i> Close
            </button>
        </div>
    </x-slot>
</x-modal>

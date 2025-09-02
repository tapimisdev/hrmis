<!-- Modal Component -->
<x-modal id="myModal" title="Deduction Details" size="modal-lg">
    <div class="p-4" style="font-family: Arial, sans-serif;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Deduction Details</h3>
            <small class="text-muted">Deduction ID: <span id="deduction-id"></span></small>
        </div>

        <table class="table table-bordered">
            <tr>
                <th>Name:</th>
                <td id="deduction-name"></td>
            </tr>
            <tr>
                <th>First Term (Amount):</th>
                <td id="deduction-first-term"></td>
            </tr>
            <tr>
                <th>Second Term (Amount):</th>
                <td id="deduction-second-term"></td>
            </tr>
            <tr>
                <th>Created At:</th>
                <td id="deduction-created-at"></td>
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

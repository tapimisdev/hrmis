<!-- Modal Component -->
<x-modal id="myModal" title="Earning Details" size="modal-lg">
    <div class="p-4" style="font-family: Arial, sans-serif;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Earning Details</h3>
            <small class="text-muted">Earning ID: <span id="earning-id"></span></small>
        </div>

        <table class="table table-bordered">
            <tr>
                <th>Name:</th>
                <td id="earning-name"></td>
            </tr>
            <tr>
                <th>First Term (Amount):</th>
                <td id="earning-first-term"></td>
            </tr>
            <tr>
                <th>Second Term (Amount):</th>
                <td id="earning-second-term"></td>
            </tr>
            <tr>
                <th>Is Taxable:</th>
                <td><span id="earning-is-taxable" class="badge"></span></td>
            </tr>
            <tr>
                <th>Created At:</th>
                <td id="earning-created-at"></td>
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

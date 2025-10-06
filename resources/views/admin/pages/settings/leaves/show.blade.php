<!-- Modal Component -->
<x-modal id="myModal" title="Leave Details" size="modal-lg">
    <div class="p-4" style="font-family: Arial, sans-serif;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Leave Details</h3>
            <small class="text-muted">Leave ID: <span id="leave-id"></span></small>
        </div>

        <table class="table table-bordered">
            <tr>
                <th>Name:</th>
                <td id="leave-name"></td>
            </tr>
            <tr>
                <th>Is Cumulative</th>
                <td id="leave-is-cumulative"></td>
            </tr>
            <tr>
                <th>Deduction</th>
                <td id="leave-deduction"></td>
            </tr>
            <tr>
                <th>Created At:</th>
                <td id="leave-created-at"></td>
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

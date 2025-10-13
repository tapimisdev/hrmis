<!-- Modal Component -->
<x-modal id="myModal" title="Leave Application" size="modal-lg">
    <div class="p-4" style="font-family: Arial, sans-serif;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Leave Application</h3>
            <small class="text-muted">Document ID: <span id="doc-id"></span></small>
        </div>

        <table class="table table-bordered">
            <tr>
                <th width="30%">Employee No:</th>
                <td id="employee-no"></td>
            </tr>
            <tr>
                <th>Leave Type:</th>
                <td id="leave-type"></td>
            </tr>
            <tr>
                <th>Dates:</th>
                <td id="selectedDates"></td>
            </tr>
            <tr>
                <th>Total Days:</th>
                <td id="days"></td>
            </tr>
            <tr>
                <th>Reason:</th>
                <td id="reason"></td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><span id="status" class="badge"></span></td>
            </tr>
            <tr>
                <th>Created At:</th>
                <td id="created-at"></td>
            </tr>
            <tr>
                <th>Attachments:</th>
                <td id="attachments">
                    <ul class="list-unstyled mb-0"></ul>
                </td>
            </tr>
        </table>

        <div class="d-flex justify-content-end gap-3">
            <p><strong>Approver:</strong> <span id="approver"></span></p>
            <p><strong>Approved At:</strong> <span id="approved-at"></span></p>
        </div>
    </div>

    <x-slot name="footer">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-danger px-4 py-2" data-bs-dismiss="modal">
                <i class="fa-solid fa-xmark me-1"></i> Close
            </button>
        </div>
    </x-slot>
</x-modal>

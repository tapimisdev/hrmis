<!-- Official Business Slip Show Modal -->
<x-modal id="myModal" title="Official Business Slip" size="modal-lg">
    <div class="p-4" style="font-family: Arial, sans-serif;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Official Business Slip</h3>
            <small class="text-muted">File No: <span id="obs-doc-id"></span></small>
        </div>
        <div id="approval-breadcrumbs">

        </div>
        <table class="table table-bordered">
            <tr>
                <th>Destination:</th>
                <td id="obs-destination"></td>
            </tr>
            <tr>
                <th>Purpose:</th>
                <td id="obs-purpose"></td>
            </tr>
            <tr>
                <th>Date From:</th>
                <td id="obs-date-from"></td>
            </tr>
            <tr>
                <th>Date To:</th>
                <td id="obs-date-to"></td>
            </tr>
            <tr>
                <th>Time Out:</th>
                <td id="obs-time-out"></td>
            </tr>
            <tr>
                <th>Time In:</th>
                <td id="obs-time-in"></td>
            </tr>
            <tr>
                <th>Mode of Transport:</th>
                <td id="obs-transport"></td>
            </tr>
            <tr>
                <th>Estimated Expense:</th>
                <td id="obs-expense"></td>
            </tr>
            <tr>
                <th>Charge To:</th>
                <td id="obs-charge-to"></td>
            </tr>
            <tr>
                <th>Remarks:</th>
                <td id="obs-remarks"></td>
            </tr>
            <tr>
                <th>Created At:</th>
                <td id="obs-created-at"></td>
            </tr>
            <tr>
                <th>Attachments:</th>
                <td id="obs-attachments">
                    <ul class="list-unstyled mb-0"></ul>
                </td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><span id="obs-status" class="badge"></span></td>
            </tr>
            <tr class="extended d-none">
                <th>Remarks:</th>
                <td><span id="remarks"></span></td>
            </tr>
        </table>

        <div class="w-100">
            <div id="approvers-by-level" class="mb-2"></div>
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


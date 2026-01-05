<!-- Modal Component -->
<x-modal id="myModal" title="Leave Application" size="modal-lg">
    <div class="p-4" style="font-family: Arial, sans-serif;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Leave Application</h3>
            <p class="text-muted text-uppercase fw-bold">File No: <span id="doc-id"></span></p>
        </div>
        <div id="approval-breadcrumbs">

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
                <th>Created At:</th>
                <td id="created-at"></td>
            </tr>
            <tr>
                <th>Attachments:</th>
                <td id="attachments">
                    <ul class="list-unstyled mb-0"></ul>
                </td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><span id="status" class="badge"></span></td>
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


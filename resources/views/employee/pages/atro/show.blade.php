<!-- Modal Component -->
<x-modal id="myModal" title="Overtime Application" size="modal-lg">
    <div class="p-4" style="font-family: Arial, sans-serif;">
        <div class="text-center mb-4">
            <p class="text-muted text-uppercase fw-bold">File No: <span id="doc-id"></span></small>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered">
                <tr>
                    <th>Date:</th>
                    <td id="date"></td>
                </tr>
                <tr>
                    <th>Start Time:</th>
                    <td id="start-time"></td>
                </tr>
                <tr>
                    <th>End Time:</th>
                    <td id="end-time"></td>
                </tr>
                <tr>
                    <th>Total Hours:</th>
                    <td id="total-hours"></td>
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
        </div>
        <div class="mt-4 mb-3">
            <small class="text-uppercase fw-bold text-muted">Your Chosen Approvers For Each Level</small>
        </div>
        
        <div class="w-100">
            <!-- <div id="approvers-by-level" class="mb-2"></div> -->
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

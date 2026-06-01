<x-modal id="myModal" title="Local Travel Order Application" size="modal-lg">
    <div class="p-4 pt-0" style="font-family: Arial, sans-serif;">
        <div class="text-center">
            <h4 class="text-muted text-uppercase fw-bold">Local Travel No: <span id="doc-id"></span></h4>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered text-uppercase">
                <tr>
                    <th width="30%">Employee No:</th>
                    <td id="employee-no"></td>
                </tr>
                <tr>
                    <th width="30%">Name:</th>
                    <td id="employee-name"></td>
                </tr>
                <tr>
                    <th>Date:</th>
                    <td id="selectedDates"></td>
                </tr>
                <tr>
                    <th>Is Hazardous:</th>
                    <td id="is-hazardous"></td>
                </tr>
                <tr>
                    <th>Remarks:</th>
                    <td id="remarks-text"></td>
                </tr>
                <tr>
                    <th>Applied At:</th>
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
            </table>
        </div>
    </div>
    <x-slot name="footer">
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-danger px-4 py-2 btn-close-action" data-bs-dismiss="modal">
                <i class="fa-solid fa-xmark me-1"></i> Close
            </button>
        </div>
    </x-slot>
</x-modal>

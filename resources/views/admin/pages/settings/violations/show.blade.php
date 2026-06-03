<x-modal id="myModal" title="Violation Details" size="modal-lg">
    <div class="p-4" style="font-family: Arial, sans-serif;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Violation Details</h3>
            <small class="text-muted">Violation ID: <span id="violation-id"></span></small>
        </div>

        <table class="table table-bordered">
            <tr>
                <th>Behavioral Type:</th>
                <td id="violation-type"></td>
            </tr>
            <tr>
                <th>Rule / Trigger:</th>
                <td id="violation-rule-trigger"></td>
            </tr>
            <tr>
                <th>Evaluation Period:</th>
                <td id="violation-evaluation-period"></td>
            </tr>
            <tr>
                <th>System Action / Sanction Text:</th>
                <td id="violation-action-name"></td>
            </tr>
            <tr>
                <th>Threshold:</th>
                <td id="violation-threshold"></td>
            </tr>
            <tr>
                <th>Created At:</th>
                <td id="violation-created-at"></td>
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

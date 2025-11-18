<!-- Modal Component -->
<x-modal id="shiftModal" title="Shift Details" size="modal-lg">
    <div class="p-4" style="font-family: Arial, sans-serif;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Shift Details</h3>
            <small class="text-muted">Shift ID: <span id="shift-id"></span></small>
        </div>

        <table class="table table-bordered">
            <tr>
                <th>Name:</th>
                <td id="shift-name"></td>
            </tr>
            <tr>
                <th>Earliest Time:</th>
                <td id="earliest-time"></td>
            </tr>
            <tr>
                <th>Start Time:</th>
                <td id="start-time"></td>
            </tr>
            <tr>
                <th>Break Out Time:</th>
                <td id="break-out-time"></td>
            </tr>
            <tr>
                <th>Break In Time:</th>
                <td id="break-in-time"></td>
            </tr>
            <tr>
                <th>End Time:</th>
                <td id="end-time"></td>
            </tr>
            <tr>
                <th>Working Hours:</th>
                <td id="working-hours"></td>
            </tr>
            <tr>
                <th>Minimum Overtime Hours:</th>
                <td id="minimum-overtime-hours"></td>
            </tr>
            <tr>
                <th>Flexible:</th>
                <td><span id="is-flexible" class="badge"></span></td>
            </tr>
            <tr>
                <th>Night Shift:</th>
                <td><span id="is-night-shift" class="badge"></span></td>
            </tr>
            <tr>
                <th>Break Required:</th>
                <td><span id="is-break-required" class="badge"></span></td>
            </tr>
            <tr>
                <th>Created At:</th>
                <td id="created-at"></td>
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

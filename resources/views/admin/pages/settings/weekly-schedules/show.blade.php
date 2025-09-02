<!-- Modal Component -->
<x-modal id="myModal" title="Weekly Schedule Details" size="modal-lg">
    <div class="p-4" style="font-family: Arial, sans-serif;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Weekly Schedule Details</h3>
            <small class="text-muted">Schedule ID: <span id="schedule-id"></span></small>
        </div>

        <table class="table table-bordered">
            <tr>
                <th>Name:</th>
                <td id="schedule-name"></td>
            </tr>
            <tr>
                <th>Monday:</th>
                <td><span id="is-monday" class="badge"></span></td>
            </tr>
            <tr>
                <th>Tuesday:</th>
                <td><span id="is-tuesday" class="badge"></span></td>
            </tr>
            <tr>
                <th>Wednesday:</th>
                <td><span id="is-wednesday" class="badge"></span></td>
            </tr>
            <tr>
                <th>Thursday:</th>
                <td><span id="is-thursday" class="badge"></span></td>
            </tr>
            <tr>
                <th>Friday:</th>
                <td><span id="is-friday" class="badge"></span></td>
            </tr>
            <tr>
                <th>Saturday:</th>
                <td><span id="is-saturday" class="badge"></span></td>
            </tr>
            <tr>
                <th>Sunday:</th>
                <td><span id="is-sunday" class="badge"></span></td>
            </tr>
            <tr>
                <th>Active:</th>
                <td><span id="is-active" class="badge"></span></td>
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

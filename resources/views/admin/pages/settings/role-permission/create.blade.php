<!-- Create Modal -->
<div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="myLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="myLabel">Add new Role</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="myForm" >
                @csrf
                <div class="modal-body">
                    <div class="mb-2">
                        <label for="tax_name" class="form-label">Name</label>
                        <input type="text" name="tax_name" class="form-control form-control-sm" id="tax_name" placeholder="Enter name here...">
                        <span id="tax_name_error" class="text-danger d-none"></span>
                    </div>
                    <div class="mb-2">
                        <label for="account" class="form-label">G/L Account</label>
                        <select name="account" id="account" class="form-control form-control-sm select2">
                            <option value="">-- Select G/L Account --</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->code }} - {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                        <span id="account_error" class="text-danger d-none"></span>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button id="update-button" type="button" class="btn btn-secondary">Update</button>
                    <button id="submit-button" type="button" class="btn btn-secondary">Add</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

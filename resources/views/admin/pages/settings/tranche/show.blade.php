<div class="table-responsive">
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>SG</th>
                <th>Step 1</th>
                <th>Step 2</th>
                <th>Step 3</th>
                <th>Step 4</th>
                <th>Step 5</th>
                <th>Step 6</th>
                <th>Step 7</th>
                <th>Step 8</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>{{ $item->salary_grade }}</td>
                    <td>{{ $item->step_1 }}</td>
                    <td>{{ $item->step_2 }}</td>
                    <td>{{ $item->step_3 }}</td>
                    <td>{{ $item->step_4 }}</td>
                    <td>{{ $item->step_5 }}</td>
                    <td>{{ $item->step_6 }}</td>
                    <td>{{ $item->step_7 }}</td>
                    <td>{{ $item->step_8 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No items available</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
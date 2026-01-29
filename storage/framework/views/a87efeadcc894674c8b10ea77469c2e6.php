<?php $__env->startSection('styles'); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('admin.pages.timekeeping.timelog-correction.modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<div class="container-fluid">
    <?php if (isset($component)) { $__componentOriginal2a2e454b2e62574a80c8110e5f128b60 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2a2e454b2e62574a80c8110e5f128b60 = $attributes; } ?>
<?php $component = App\View\Components\Header::resolve(['title' => 'Timelog Correction Request','subtitle' => 'Manage shift scheduling in this module'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(App\View\Components\Header::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>

     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2a2e454b2e62574a80c8110e5f128b60)): ?>
<?php $attributes = $__attributesOriginal2a2e454b2e62574a80c8110e5f128b60; ?>
<?php unset($__attributesOriginal2a2e454b2e62574a80c8110e5f128b60); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2a2e454b2e62574a80c8110e5f128b60)): ?>
<?php $component = $__componentOriginal2a2e454b2e62574a80c8110e5f128b60; ?>
<?php unset($__componentOriginal2a2e454b2e62574a80c8110e5f128b60); ?>
<?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-3">
            <label for="filter-month" class="form-label">Month</label>
            <select id="filter-month" class="form-select">
                <?php $__currentLoopData = range(1,12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($m); ?>" <?php echo e($m == date('n') ? 'selected' : ''); ?>>
                    <?php echo e(DateTime::createFromFormat('!m', $m)->format('F')); ?>

                </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="filter-year" class="form-label">Year</label>
            <select id="filter-year" class="form-select">
                <?php $__currentLoopData = range(date('Y'), date('Y') - 5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($y); ?>" <?php echo e($y == date('Y') ? 'selected' : ''); ?>>
                    <?php echo e($y); ?>

                </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="filter-status" class="form-label">Status</label>
            <select id="filter-status" class="form-select">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
    </div>


    <?php if (isset($component)) { $__componentOriginal163c8ba6efb795223894d5ffef5034f5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal163c8ba6efb795223894d5ffef5034f5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.table','data' => ['id' => 'myTable']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('table'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'myTable']); ?>
        <thead>
            <tr>
                <th>#</th>
                <th>Reference No</th>
                <th>Employee No</th>
                <th>Name</th>
                <th>Date</th>
                <th>status</th>
                <th>Applied at</th>
                <th style="width: 120px">Action</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal163c8ba6efb795223894d5ffef5034f5)): ?>
<?php $attributes = $__attributesOriginal163c8ba6efb795223894d5ffef5034f5; ?>
<?php unset($__attributesOriginal163c8ba6efb795223894d5ffef5034f5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal163c8ba6efb795223894d5ffef5034f5)): ?>
<?php $component = $__componentOriginal163c8ba6efb795223894d5ffef5034f5; ?>
<?php unset($__componentOriginal163c8ba6efb795223894d5ffef5034f5); ?>
<?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    $(function() {

        let DataTable = $('#myTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?php echo e(route('timelogs-correction.index')); ?>',
                data: function(d) {
                    d.month = $('#filter-month').val(); // defaults to current month
                    d.year = $('#filter-year').val();   // defaults to current year
                    d.status = $('#filter-status').val(); // optional
                }
            },
            columns: [{
                    data: "DT_RowIndex",
                    name: 'index'
                },
                {
                    data: "reference_no",
                    name: 'reference_no'
                },
                {
                    data: "employee_no",
                    name: 'employee_no'
                },
                {
                    data: "name",
                    name: 'name'
                },
                {
                    data: "date",
                    name: 'date'
                },
                {
                    data: "status",
                    name: 'status'
                },
                {
                    data: "applied_at",
                    name: 'applied_at'
                },
                {
                    data: "actions",
                    name: 'actions',
                    orderable: false,
                    searchable: false
                },
            ],
            columnDefs: [
                {
                    targets: [1, 2, 3, 4, 5, 6, 7],
                    className: 'min-table-width'
                }
            ],
            scrollX: true,
            autoWidth: false
        });

        const tcrModal = $('#tcrModal');

        $('#filter-month, #filter-year, #filter-status').on('change', function() {
            DataTable.ajax.reload();
        });

        let id;

        $(document).on('click', '.show-button', function() {
            id = $(this).data('id');

            axios.get(`/admin/timekeeping/timelogs-correction/${id}/edit`)
                .then(response => {
                    const data = response.data;

                    // Hidden ID
                    $('#correction-id').val(data.id);

                    // View-only fields
                    $('#reference_no').val(data.reference_no);
                    $('#employee-name').val(`${data.firstname} ${data.middlename || ''} ${data.lastname}`.trim());

                    // Editable fields
                    $('#date').val(data.date);
                    $('#time-in').val(data.time_in ? data.time_in.split(' ')[1] : '');
                    $('#break-out').val(data.break_out ? data.break_out.split(' ')[1] : '');
                    $('#break-in').val(data.break_in ? data.break_in.split(' ')[1] : '');
                    $('#time-out').val(data.time_out ? data.time_out.split(' ')[1] : '');
                    $('#overtime-in').val(data.overtime_in ? data.overtime_in.split(' ')[1] : '');
                    $('#overtime-out').val(data.overtime_out ? data.overtime_out.split(' ')[1] : '');
                    $('#status').val(data.status);

                    // Remarks
                    $('#remarks').text(data.remarks || '---');

                    // Attachment
                    if (data.attachment) {
                        const ext = data.attachment.split('.').pop().toLowerCase();

                        $('#attachment-pdf, #attachment-img, #attachment-link').addClass('d-none');

                        if (ext === 'pdf') {
                            $('#attachment-pdf').attr('src', data.attachment).removeClass('d-none');
                        } else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                            $('#attachment-img').attr('src', data.attachment).removeClass('d-none');
                        } else {
                            $('#attachment-link').attr('href', data.attachment).removeClass('d-none');
                        }
                    } else {
                        $('#attachment-link').attr('href', '#').text('No Attachment').removeClass('d-none');
                    }

                    // Show modal
                    $('#tcrModal').modal('show');
                })
                .catch(error => {
                    Swal.fire('Oops!', error.response?.data?.message || error.message, 'error');
                });
        });


        // Approve function
        $('.approve-button').click(function() {
            axios.post(`/admin/timekeeping/timelogs-correction/${id}/approve`, {
                _token: $('input[name="_token"]').val()
            })
            .then(response => {
                Swal.fire({
                    icon: 'success',
                    title: 'Approved',
                    text: 'Timelog correction has been approved.',
                });
                $('#tcrModal').modal('hide');
                // optionally reload your datatable or list
                DataTable.ajax.reload();
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.response.data.message || 'Something went wrong!',
                });
            });
        });


        // Approve function
        $('.reject-button').click(function() {
            axios.post(`/admin/timekeeping/timelogs-correction/${id}/reject`, {
                _token: $('input[name="_token"]').val()
            })
            .then(response => {
                Swal.fire({
                    icon: 'success',
                    title: 'Approved',
                    text: 'Timelog correction has been rejected.',
                });
                $('#tcrModal').modal('hide');
                // optionally reload your datatable or list
                DataTable.ajax.reload();
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.response.data.message || 'Something went wrong!',
                });
            });
        });




    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/pages/timekeeping/timelog-correction/index.blade.php ENDPATH**/ ?>
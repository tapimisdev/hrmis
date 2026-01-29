<!-- Sidebar -->
<aside>
    <button @click="toggleMobileMenu" class="d-md-none x-mark"><i class="fa-solid fa-xmark"></i></button>
    <div class="sidebar shadow" id="sidebar">
        <!-- Header -->
        <header class="sidebar-header">
            <div class="sidebar-brand">
                <div class="brand-body">
                    <button id="imgSwitchBtn" class="p-0 border-0 ">
                        <img src="<?php echo e(asset('img/dost-tapi.png')); ?>" alt="TAPI Logo">
                    </button>
                    <h5>DOST-TAPI</h5>
                </div>

                <button id="switchMenuBtn" class="sidebar-toggle-btn">
                    <i class="fa-regular fa-chart-bar"></i>
                </button>
            </div>
        </header>

        <!-- Sidebar Navigation -->
        <nav class="sidebar-nav">
            <div class="mb-2 ms-1">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Menu</small>
            </div>
            <ul class="side-container">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('emp.dashboard.view')): ?>

                <!-- Dashboard -->
                <li class="side-items <?php echo e(request()->routeIs('dashboard.*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('dashboard.index')); ?>" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-gauge"></i>
                        </span>
                        <span class="side-text">Dashboard</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any([
                    'emp.timelogs.view',
                    'emp.timelogs.checkin-out'
                ])): ?>
                <!-- Timelogs -->
                <li class="side-items has-submenu <?php echo e(request()->routeIs('checkinout.*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('checkinout.index')); ?>" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-clock"></i>
                        </span>
                        <span class="side-text">Timelogs</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Announcements -->
                <li class="side-items <?php echo e(request()->is('employee/announcements*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('announcement.index')); ?>" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-bullhorn"></i>
                        </span>
                        <span class="side-text">Announcements</span>
                    </a>
                </li>

                <li class="side-items">
                    <a class="side-link text-body dropdown-toggle 
                        <?php echo e(Str::contains(request()->path(), 'credits/leave') || Str::contains(request()->path(), 'credits/offset') ? '' : 'collapsed'); ?>"
                        data-bs-toggle="collapse"
                        data-bs-target="#credits"
                        role="button"
                        aria-expanded="<?php echo e(Str::contains(request()->path(), 'credits/leave') || Str::contains(request()->path(), 'credits/offset') ? 'true' : 'false'); ?>"
                        aria-controls="credits">

                        <i class="fa-regular fa-calendar"></i>
                        <span class="side-text">Credits</span>
                    </a>

                    <div class="collapse collapsable 
                        <?php echo e(Str::contains(request()->path(), 'credits/leave') || Str::contains(request()->path(), 'credits/offset') ? 'show' : ''); ?>"
                        id="credits">

                        <ul class="nested-list list-unstyled py-2">

                            
                            <li class="side-items nested-item py-1 px-4 mb-2 <?php echo e(Str::contains(request()->path(), 'credits/leave') ? 'active' : ''); ?>">
                                <a href="<?php echo e(route('leave-credits.index')); ?>"
                                class="d-flex justify-content-start align-items-center gap-2 text-body text-decoration-none">
                                    <i class="fa-solid fa-plane-departure"></i>
                                    <span>Leave</span>
                                </a>
                            </li>

                            
                            <li class="side-items nested-item py-1 px-4 mb-2 <?php echo e(Str::contains(request()->path(), 'credits/offset') ? 'active' : ''); ?>">
                                <a href="<?php echo e(route('offset-credits.index')); ?>"
                                class="d-flex justify-content-start align-items-center gap-2 text-body text-decoration-none">
                                    <i class="fa-solid fa-ghost"></i>
                                    <span>Offset</span>
                                </a>
                            </li>

                        </ul>
                    </div>
                </li>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('emp.dashboard.view')): ?>
                <!-- Payslip -->
                <li class="side-items has-submenu <?php echo e(request()->routeIs('payslip.*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('payslip.index')); ?>" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-money-check-dollar"></i>
                        </span>
                        <span class="side-text">Payslip</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <div class="sidebar-seperator"></div>
                
                <div class="mb-2 ms-1">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Applications</small>
                </div>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any([
                    'emp.leave_application.view',
                    'emp.leave_application.apply'
                ])): ?>
                    <?php if(Auth::user()->employment_type_id == \App\Enums\EmploymentTypesEnum::REGULAR->value): ?>
                        <!-- Leave Application -->
                        <li class="side-items has-submenu <?php echo e(request()->routeIs('leaves.*') ? 'active' : ''); ?>">
                            <a href="<?php echo e(route('leaves.index')); ?>" class="side-link text-body">
                                <span class="side-icon">
                                    <i class="fa-solid fa-plane-departure"></i>
                                </span>
                                <span class="side-text">Leave</span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any([
                    'emp.offset_application.view',
                    'emp.offset_application.apply'
                ])): ?>
                <li class="side-items has-submenu <?php echo e(request()->routeIs('offset.*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('offset.index')); ?>" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-ghost"></i>
                        </span>
                        <span class="side-text">Offset</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any([
                    'emp.overtime_application.view',
                    'emp.overtime_application.apply'
                ])): ?>
                <!-- Overtime -->
                <li class="side-items has-submenu <?php echo e(request()->routeIs('overtime.*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('overtime.index')); ?>" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </span>
                        <span class="side-text">Overtime</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any([
                    'emp.overtime_application.view',
                    'emp.overtime_application.apply'
                ])): ?>

                <li class="side-items has-submenu <?php echo e(request()->routeIs('obs.*') ? 'active' : ''); ?>">
                    <a href="<?php echo e(route('obs.index')); ?>" class="side-link text-body">
                        <span class="side-icon">
                            <i class="fa-solid fa-torii-gate"></i>
                        </span>
                        <span class="side-text">Pass Slip</span>
                    </a>
                </li>
                <?php endif; ?> -->

                
            </ul>
        </nav>
    </div>
</aside><?php /**PATH /var/www/html/resources/views/employee/components/sidebar.blade.php ENDPATH**/ ?>
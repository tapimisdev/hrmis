
<li class="sidebar-item {{ Str::contains(request()->path(), 'taxation') ? 'active' : '' }}">
    <a class="sidebar-link dropdown-toggle 
        {{ Str::contains(request()->path(), 'taxation') ? '' : 'collapsed' }}"
        data-bs-toggle="collapse" 
        data-bs-target="#taxation"
        role="button" 
        aria-expanded="{{ Str::contains(request()->path(), 'taxation') ? 'true' : 'false' }}" 
        aria-controls="taxation">

        <i class="fa-solid fa-calculator"></i>
        <span>Taxation</span>
    </a>

    <div class="collapse collapsable 
        {{ Str::contains(request()->path(), 'taxation') ? 'show' : '' }}" 
        id="taxation">

        <ul class="nested-list">

            {{-- TRAIN LAW --}}
            <!-- @can('hr.tax_train_law.view') -->
            <li class="nested-item">
                <a href="{{ route('taxation.train-law.index') }}"
                    class="{{ request()->is('admin/taxation/train-law*') ? 'active' : '' }}">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <span>TRAIN Law</span>
                </a>
            </li>

            <li class="nested-item">
                <a href="{{ route('taxation.index') }}"
                    class="{{ request()->is('admin/taxation') ? 'active' : '' }}">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <span>Taxation</span>
                </a>
            </li>
            <!-- @endcan -->
        </ul>
    </div>
</li>

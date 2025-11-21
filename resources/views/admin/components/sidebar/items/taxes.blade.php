<li class="sidebar-item {{ Str::contains(request()->path(), 'admin/deductions') ? 'active' : '' }}">
    <a class="sidebar-link dropdown-toggle {{ Str::contains(request()->path(), 'payroll') ? '' : 'collapsed' }}"
    data-bs-toggle="collapse" 
    data-bs-target="#taxes"
    role="button" 
    aria-expanded="{{ Str::contains(request()->path(), 'payroll') ? 'true' : 'false' }}" 
    aria-controls="taxes">
        <i class="fa-solid fa-file-invoice-dollar"></i>
        <span>Taxes</span>
    </a>
    <div class="collapse collapsable {{ Str::contains(request()->path(), 'payroll') ? 'show' : '' }}" 
        id="taxes">
         <ul class="nested-list">
            @if (count(getTaxesModules()) === 0)
                <li class="nested-item">
                    <div class="alert alert-danger p-0 p-2 text-center" role="alert">
                        No modules available
                    </div>
                </li>
            @else
                @foreach (getTaxesModules() as $module)
                    <li class="nested-item">
                        <a href="{{ route('tax.index', ['slug' => $module->slug]) }}"
                        class="">
                            <i class="{{ $module->icon }}"></i>
                            <span class="text-capitalize">{{ $module->name }}</span>
                        </a>
                    </li>
                @endforeach
            @endif
        </ul>
    </div>
</li>
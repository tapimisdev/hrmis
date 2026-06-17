@can('hr.recruitment.view')
<li class="sidebar-item {{ request()->is('admin/recruitment*') ? 'active' : '' }}">
    <a class="sidebar-link dropdown-toggle {{ request()->is('admin/recruitment*') ? '' : 'collapsed' }}"
       data-bs-toggle="collapse"
       data-bs-target="#recruitment"
       role="button"
       aria-expanded="{{ request()->is('admin/recruitment*') ? 'true' : 'false' }}"
       aria-controls="recruitment">
        <i class="fa-solid fa-user-plus"></i>
        <span>Recruitment</span>
    </a>
    <div class="collapse collapsable {{ request()->is('admin/recruitment*') ? 'show' : '' }}"
         id="recruitment">
        <ul class="nested-list">
            <li class="nested-item">
                <a href="{{ route('recruitment.jobs') }}"
                   class="{{ request()->routeIs('recruitment.jobs*') ? 'active' : '' }}">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Job Posting</span>
                </a>
            </li>
            <li class="nested-item">
                <a href="{{ route('recruitment.applicants') }}"
                   class="{{ request()->routeIs('recruitment.applicants') ? 'active' : '' }}">
                    <i class="fa-solid fa-users"></i>
                    <span>Applicants</span>
                </a>
            </li>
            <li class="nested-item">
                <a href="{{ route('recruitment.process') }}"
                   class="{{ request()->routeIs('recruitment.process', 'recruitment.applications.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-list-check"></i>
                    <span>Hiring Process</span>
                </a>
            </li>
            <li class="nested-item">
                <a href="{{ route('recruitment.assessments') }}"
                   class="{{ request()->routeIs('recruitment.assessments') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Interview / Exams</span>
                </a>
            </li>
        </ul>
    </div>
</li>
@endcan

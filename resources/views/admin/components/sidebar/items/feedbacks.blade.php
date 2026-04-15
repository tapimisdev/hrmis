<li class="sidebar-item {{ Str::contains(request()->path(), 'feedbacks') ? 'active' : '' }}">
    <a href="{{ route('feedbacks.index') }}" class="sidebar-link {{ Str::contains(request()->path(), 'feedbacks') ? '' : 'collapsed' }}">
        <i class="fa-solid fa-comment-dots"></i>
        <span>Feedbacks</span>
    </a>
</li>

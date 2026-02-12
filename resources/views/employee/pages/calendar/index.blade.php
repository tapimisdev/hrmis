@extends('employee.layout.app')

@section('content')
<div class="container-fluid min-vh-100">

    <x-employee-navbar>
        <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
    </x-employee-navbar>

    <x-header-employee title="My Calendar" subtitle="Manage calendar events in this module"></x-header-employee>

    <div class="container mb-5">
        <div class="card">
            <div class="card-header py-3 ps-4">
                <small class="text-uppercase fw-medium">Legends</small>
                <div class="d-flex gap-3 align-items-center flex-wrap mt-1">
                    <div class="legend-item d-flex align-items-center">
                        <span class="legend-color bg-info me-2"></span>
                        <span>Event / Announcement</span>
                    </div>
                    <div class="legend-item d-flex align-items-center">
                        <span class="legend-color bg-danger me-2"></span>
                        <span>Suspension / Holiday</span>
                    </div>
                    <div class="legend-item d-flex align-items-center">
                        <span class="legend-color bg-primary me-2"></span>
                        <span>Approved Applications</span>
                    </div>
                    <div class="legend-item d-flex align-items-center">
                        <span class="legend-color bg-warning me-2"></span>
                        <span>Pending Applications</span>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>

    .fc-event {
        margin-bottom: 8px;
    }

    .calendar-content { background: transparent; padding: 20px; }

    .fc-daygrid-day-events { text-align: center; text-transform: uppercase; font-weight: bold; }

    .fc-event.bg-info { background-color: #0c5460 !important; color: #ffffff !important; border-color: #0c5460 !important; }
    .fc-event.bg-danger { background-color: #fa1629 !important; color: #ffffff !important; border-color: #721c24 !important; }
    .fc-event.bg-primary { background-color: #004085 !important; color: #ffffff !important; border-color: #b3d4fc !important; }
    .fc-event.bg-warning { background-color: #ffc107 !important; color: #212529 !important; border-color: #ffc107 !important; }

    span { font-size: 13px; font-weight: normal; }

    .legend-color { display: inline-block; width: 12px; height: 12px; border-radius: 4px; border: 1px solid #00000033; }

    .legend-color.bg-info { background-color: #0c5460 !important; }
    .legend-color.bg-danger { background-color: #fa1629 !important; }
    .legend-color.bg-primary { background-color: #004085 !important; }
    .legend-color.bg-warning { background-color: #ffc107 !important; }
</style>
@endsection

@section('scripts')
<script type="module">
$(document).ready(function () {
    const calendarEl = document.getElementById('calendar');

    // Map event types to FullCalendar classes
    const typeToClass = {
        'event': 'bg-info',
        'suspension': 'bg-danger',
        'holiday': 'bg-danger',
        'pending': 'bg-warning',
        'approved': 'bg-primary'
    };

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        themeSystem: 'bootstrap5',

        events: function (fetchInfo, successCallback, failureCallback) {
            $.ajax({
                url: '/employee/calendar',
                type: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (response) {
                    if (response.status !== 'success') return failureCallback();

                    const events = [];

                    response.data.forEach(day => {
                        day.items.forEach(item => {
                            const fullTitle = item.title;
                            const truncatedTitle = fullTitle.length > 15 ? fullTitle.substring(0, 15) + '...' : fullTitle;
                            const className = typeToClass[item.type] || '';

                            events.push({
                                title: truncatedTitle,
                                start: day.date,
                                url: item.redirect,
                                allDay: true,
                                className: className ? [className] : [],
                                extendedProps: {
                                    fullTitle: fullTitle,
                                    description: item.description || null,
                                    type: item.type
                                }
                            });
                        });
                    });

                    successCallback(events);
                },
                error: function (xhr) {
                    console.error(xhr);
                    failureCallback(xhr);
                }
            });
        },

        eventDidMount: function (info) {
            const props = info.event.extendedProps;
            let tooltipText = props.fullTitle;
            if (props.description) tooltipText += '\n\n' + props.description;
            info.el.setAttribute('title', tooltipText.toUpperCase());
        },

        eventClick: function (info) {
            if (info.event.url) {
                info.jsEvent.preventDefault();
                window.location.href = info.event.url;
            }
        }
    });

    calendar.render();
});
</script>
@endsection

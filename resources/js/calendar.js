import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction'; // Import the interaction plugin
import { alert } from './helper';

let calendar = null;

export function initCalendar(selector = '.full-calendar', options = {}) {
    const calendarEl = document.querySelector(selector);
    if (!calendarEl) {
        console.warn('Calendar element not found:', selector);
        return;
    }

    const toggleEventStatus = (event) => {
        const dayOfWeek = new Date(event.start).getDay();

        if (dayOfWeek === 0 || dayOfWeek === 6) {
            alert('error', 'Weekend days are not available. Please choose only on available dates.');
            return;
        }

        if (event.title === 'Available') {
            event.setProp('title', 'Selected');
            event.setProp('backgroundColor', '#f0ad4e'); 
            event.setProp('borderColor', '#f0ad4e');
        } else if (event.title === 'Selected') {
            event.setProp('title', 'Available');
            event.setProp('backgroundColor', '#0c8384'); 
            event.setProp('borderColor', '#0c8384');
        } else {
            alert('error', 'Selected date has been already scheduled. Please choose only on available dates.');
        }
    };

    const defaultOptions = {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: [],
        eventContent: function(arg) {
            const { event } = arg;
            const description = event.extendedProps.description || '';
            return {
                html: `
                    <div class="fc-event-title" style="font-weight: bold; text-align: center; cursor: pointer;">
                        ${event.title}
                    </div>
                    <div class="fc-event-description" style="text-align: center;">
                        ${description}
                    </div>
                `
            };
        },
        dateClick: function(info) {
            const clickedDate = new Date(info.dateStr);
            const dayOfWeek = clickedDate.getDay();

            if (dayOfWeek === 0 || dayOfWeek === 6) {
                alert('error', 'Weekend days are not available. Please choose available dates.');
                return;
            }

            // Find event(s) on clicked date
            const events = calendar.getEvents().filter(event => event.startStr === info.dateStr);

            if (events.length === 0) {
                alert('error', 'No event found on this date to toggle.');
                return;
            }

            // Toggle all events found on the date
            events.forEach(event => toggleEventStatus(event));
        },
        eventClick: function(info) {
            const clickedEvent = info.event;
            toggleEventStatus(clickedEvent);
        }
    };

    calendar = new Calendar(calendarEl, { ...defaultOptions, ...options });
    calendar.render();
}

export function setEvents(events = []) {
    if (!calendar) {
        console.error('Calendar not initialized.');
        return;
    }

    calendar.removeAllEvents();
    calendar.addEventSource(events);
}

export function generateEventsWithAvailability(unavailable = []) {
    const events = [];
    const today = new Date();
    const year = today.getFullYear();
    const endDate = new Date(year, 11, 31);

    const unavailableMap = {};
    unavailable.forEach(u => {
        unavailableMap[u.date] = { title: u.title, description: u.description };
    });

    for (let d = new Date(today); d <= endDate; d.setDate(d.getDate() + 1)) {
        const day = d.getDay();
        if (day >= 1 && day <= 5) {
            const dateStr = d.toISOString().slice(0, 10);
            if (unavailableMap.hasOwnProperty(dateStr)) {
                events.push({
                    title: unavailableMap[dateStr].title,
                    start: dateStr,
                    allDay: true,
                    backgroundColor: 'red',
                    borderColor: 'red',
                });
            } else {
                events.push({
                    title: 'Available',
                    start: dateStr,
                    allDay: true,
                    backgroundColor: '#0c8384',
                    borderColor: '#0c8384',
                });
            }
        }
    }

    return events;
}

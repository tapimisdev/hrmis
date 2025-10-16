import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction'; // Import the interaction plugin
import { alert } from './helper';

let calendar = null;
let rawData = [];

export function initCalendar(selector = '.full-calendar', options = {}) {
  const calendarEl = document.querySelector(selector);
  if (!calendarEl) {
    console.warn('Calendar element not found:', selector);
    return;
  }

  let popoverEl = null; // global for the popover element
  let outsideClickListener = null; // keep track of outside click listener so we can remove it

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

    updateSelectedDates();
  };

  const closePopover = () => {
    if (popoverEl) {
      popoverEl.remove();
      popoverEl = null;
    }
    if (outsideClickListener) {
      document.removeEventListener('click', outsideClickListener);
      outsideClickListener = null;
    }
  };

  const createAndShowPopover = (anchorEl, title, events) => {

    closePopover(); // close any existing popover

    // Create popover container
    popoverEl = document.createElement('div');
    popoverEl.classList.add('fc-popover');
    popoverEl.style.position = 'absolute';
    popoverEl.style.zIndex = '9999';
    popoverEl.style.background = 'white';
    popoverEl.style.border = '1px solid #ccc';
    popoverEl.style.borderRadius = '5px';
    popoverEl.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
    popoverEl.style.padding = '1rem';
    popoverEl.style.minWidth = '250px';
    popoverEl.style.maxHeight = '300px';
    popoverEl.style.overflowY = 'auto';

    // Title bar + close button
    const titleEl = document.createElement('div');
    titleEl.style.fontWeight = 'bold';
    titleEl.style.marginBottom = '0.2rem';
    titleEl.style.display = 'flex';
    titleEl.style.justifyContent = 'space-between';
    titleEl.style.alignItems = 'center';
    titleEl.textContent = title;

    const closeBtn = document.createElement('button');
    closeBtn.textContent = '×';
    closeBtn.style.border = 'none';
    closeBtn.style.background = 'transparent';
    closeBtn.style.fontSize = '1.2rem';
    closeBtn.style.cursor = 'pointer';
    closeBtn.style.lineHeight = '1';
    closeBtn.style.marginBottom = '3px';
    closeBtn.onclick = () => {
      closePopover();
    };

    titleEl.appendChild(closeBtn);
    popoverEl.appendChild(titleEl);

    const hr = document.createElement('hr');
    popoverEl.appendChild(hr);

    hr.style.margin = '10px 0 12px 0';

    // List events
    const ul = document.createElement('ul');
    ul.style.listStyle = 'none';
    ul.style.padding = '0';
    ul.style.margin = '0';

    events.forEach(ev => {
        const li = document.createElement('li');
        li.style.padding = '0 0';
        li.style.fontSize = "13px";
        li.innerHTML = `<span class="me-2" style="font-size: 15px">•</span><strong class="text-capitalize my-0">${ev.title} - (${ev.status})</strong>`;
        ul.appendChild(li);
    });

    popoverEl.appendChild(ul);

    document.body.appendChild(popoverEl);

    // Position popover near anchor element
    const rect = anchorEl.getBoundingClientRect();

    const top = rect.bottom + window.scrollY + 5;
    const left = rect.left + window.scrollX;

    popoverEl.style.top = `${top}px`;
    popoverEl.style.left = `${left}px`;

    // Add outside click listener
    outsideClickListener = (event) => {
      if (popoverEl && !popoverEl.contains(event.target) && event.target !== anchorEl) {
        closePopover();
      }
    };

    // Add listener with a slight delay to avoid immediate closing
    setTimeout(() => {
      document.addEventListener('click', outsideClickListener);
    }, 0);
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
    eventOrder: 'order',
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

      // Find events on clicked date
      const events = calendar.getEvents().filter(event => event.startStr === info.dateStr);

      if (events.length === 0) {
        alert('error', 'This date is unavailable, please choose other dates.');
        return;
      }

      events.forEach(event => toggleEventStatus(event));
    },
    eventClick: function(info) {
        const clickedEvent = info.event;
        const clickedDate = clickedEvent.startStr;

        if (clickedEvent.classNames.includes('more-event')) {
    
            const wordDate = new Date(clickedEvent.startStr).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            // Get all events on the clicked date excluding the +more event itself
            const allEventsOnDate = getEventsByDate(clickedDate);

            // Show popover anchored to the clicked DOM element
            createAndShowPopover(info.el, `${wordDate}`, allEventsOnDate);

            return; // Prevent toggleEventStatus for +more event
        }

            toggleEventStatus(clickedEvent);
        }
    };

    calendar = new Calendar(calendarEl, { ...defaultOptions, ...options });
    calendar.render();
}

export function setEvents(events = [], raw = []) {

    rawData = raw;

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
    const endDate = new Date(year, 11, 31); // December 31 of current year

    // Start from the day after today
    let currentDate = new Date(year, 0, 1);
    console.log(currentDate.getDay());  

    currentDate.setDate(currentDate.getDate() + 1);

    // Group unavailable items by date (array per date)
    const unavailableMap = {};
    for (const item of unavailable) {
        const date = item.date;
        if (!unavailableMap[date]) {
            unavailableMap[date] = [];
        }
        unavailableMap[date].push(item);
    }

    let orderCounter = 0;

    while (currentDate <= endDate) {
        const dayOfWeek = currentDate.getDay(); // 0 = Sunday, 6 = Saturday
        const dateStr = currentDate.toISOString().slice(0, 10);

        if (dayOfWeek > 1) {
            const items = unavailableMap[dateStr];

            if (items && items.length > 0) {
                const maxVisibleItems = 2; // max original items to show
                const dateEvents = [];

                for (const item of items) {
                    const status = item.status;
                    const title = item.title;

                    let backgroundColor = '#0c8384'; // default color

                    switch (status) {
                        case 'suspension':
                        case 'holiday':
                            backgroundColor = 'red';
                            break;
                        case 'cancelled':
                            backgroundColor = '#d9534f';
                            break;
                        case 'pending':
                            backgroundColor = '#FF6701';
                            break;
                        case 'rejected':
                            backgroundColor = '#6c757d';
                            break;
                        case 'approved':
                            backgroundColor = '#5cb85c';
                            break;
                    }

                    if (status === 'holiday' || status === 'suspension') {
                        // Colored status event
                        dateEvents.push({
                            title: status,
                            start: dateStr,
                            allDay: true,
                            backgroundColor,
                            borderColor: backgroundColor,
                            order: orderCounter++,
                            className: 'text-center text-uppercase',
                        });

                        // Transparent title event
                        dateEvents.push({
                            title: title,
                            start: dateStr,
                            backgroundColor: 'transparent',
                            textColor: '#0c8384',
                            className: 'text-center text-uppercase',
                            borderColor: 'transparent',
                            order: orderCounter++,
                        });
                    } else {
                        // Colored title event
                        dateEvents.push({
                            title,
                            start: dateStr,
                            allDay: true,
                            backgroundColor,
                            className: 'text-center text-uppercase',
                            borderColor: backgroundColor,
                            order: orderCounter++,
                        });

                        // Transparent status event
                        dateEvents.push({
                            title: status,
                            start: dateStr,
                            backgroundColor: 'transparent',
                            textColor: '#0c8384',
                            className: 'text-center text-uppercase',
                            borderColor: 'transparent',
                            order: orderCounter++,
                        });
                    }
                }

                if (items.length > maxVisibleItems) {
                    // Show first 3 original items → 3 * 2 = 6 events
                    const visibleEventsCount = maxVisibleItems * 2;
                    events.push(...dateEvents.slice(0, visibleEventsCount));

                    // Calculate remaining original items
                    const remainingCount = items.length - maxVisibleItems;

                    events.push({
                        title: `+${remainingCount} more`,
                        start: dateStr,
                        allDay: true,
                        backgroundColor: '#888',
                        borderColor: '#888',
                        textColor: '#fff',
                        className: 'more-event text-uppercase text-center',
                        order: orderCounter++,
                    });
                } else {
                    // Show all events if within limit
                    events.push(...dateEvents);
                }
            } else {
                // Default Available
                events.push({
                    title: 'Available',
                    start: dateStr,
                    allDay: true,
                    backgroundColor: '#0c8384',
                    borderColor: '#0c8384',
                    className: 'text-center text-uppercase',
                    order: orderCounter++,
                });
            }
        }

        currentDate.setDate(currentDate.getDate() + 1);
    }

    return events;
}

function getEventsByDate(clickedDate) {
  const dateStr = typeof clickedDate === 'string'
    ? clickedDate
    : clickedDate.toISOString().slice(0, 10);

  return rawData.filter(item => item.date === dateStr);
}
function updateSelectedDates() {

    if (!calendar) return;

    const selectedDates = calendar.getEvents()
        .filter(event => event.title === 'Selected')
        .map(event => event.startStr)
        .sort(); 

    $('#selectedDates').val(JSON.stringify(selectedDates));
}


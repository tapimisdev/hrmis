import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import { alert } from './helper';
import * as bootstrap from 'bootstrap';

let calendar = null;
let rawData = [];
let modalContext = null;

export function initCalendar(selector = '.full-calendar', options = {}) {
    const calendarEl = document.querySelector(selector);
    if (!calendarEl) {
        return;
    }

    let popoverEl = null;
    let outsideClickListener = null;

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
        closePopover();

        popoverEl = document.createElement('div');
        popoverEl.classList.add('fc-popover');
        Object.assign(popoverEl.style, {
            position: 'absolute',
            zIndex: '9999',
            background: 'white',
            border: '1px solid #ccc',
            borderRadius: '5px',
            boxShadow: '0 2px 8px rgba(0,0,0,0.15)',
            padding: '1rem',
            minWidth: '250px',
            maxHeight: '300px',
            overflowY: 'auto',
        });

        const titleEl = document.createElement('div');
        Object.assign(titleEl.style, {
            fontWeight: 'bold',
            marginBottom: '0.2rem',
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
        });
        titleEl.textContent = title;

        const closeBtn = document.createElement('button');
        closeBtn.textContent = '×';
        Object.assign(closeBtn.style, {
            border: 'none',
            background: 'transparent',
            fontSize: '1.2rem',
            cursor: 'pointer',
            lineHeight: '1',
            marginBottom: '3px',
        });
        closeBtn.onclick = () => closePopover();

        titleEl.appendChild(closeBtn);
        popoverEl.appendChild(titleEl);

        const hr = document.createElement('hr');
        hr.style.margin = '10px 0 12px 0';
        popoverEl.appendChild(hr);

        const ul = document.createElement('ul');
        Object.assign(ul.style, { listStyle: 'none', padding: '0', margin: '0' });

        events.forEach(ev => {
            const li = document.createElement('li');
            li.style.padding = '0 0';
            li.style.fontSize = "13px";
            li.innerHTML = `<span class="me-2" style="font-size: 15px">•</span><strong class="text-capitalize my-0">${ev.title} - (${ev.status})</strong>`;
            ul.appendChild(li);
        });

        popoverEl.appendChild(ul);

        document.body.appendChild(popoverEl);

        const rect = anchorEl.getBoundingClientRect();
        popoverEl.style.top = `${rect.bottom + window.scrollY + 5}px`;
        popoverEl.style.left = `${rect.left + window.scrollX}px`;

        outsideClickListener = (event) => {
            if (popoverEl && !popoverEl.contains(event.target) && event.target !== anchorEl) {
                closePopover();
            }
        };

        setTimeout(() => {
            document.addEventListener('click', outsideClickListener);
        }, 0);
    };

    // ------------------------
    // Modal handling using jQuery
    // ------------------------
    function showTimeOptionModal(context) {
        modalContext = context;

        const $container = $('#slot-modal');
        if ($container.length === 0) {
            return;
        }

        // Remove any existing modal first
        $container.empty();

        // Create modal HTML dynamically
        const modalId = 'timeOptionModal';
        const $modal = $(`
        <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-uppercase">Choose Shift</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-primary w-100 text-nowrap time-option-btn" data-option="morning">
                                    <i class="fas fa-sun me-1"></i> Morning
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-success w-100 text-nowrap time-option-btn" data-option="afternoon">
                                    <i class="fas fa-cloud-sun me-1"></i> Afternoon
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-warning w-100 text-nowrap time-option-btn" data-option="wholeday">
                                    <i class="fas fa-calendar-day me-1"></i> Whole Day
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" data-bs-dismiss="modal" class="btn btn-danger w-100 text-nowrap cancel-btn">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);

        // Append to container
        $container.append($modal);

        // Attach button click handlers
        $modal.find('.time-option-btn').on('click', function () {
            const option = $(this).data('option');
            onTimeOptionSelected(option);
        });

        $modal.find('.btn[data-bs-dismiss="modal"]').on('click', function () {
            onCancelSelection();
        });

        // Bootstrap 5 way to show modal (not jQuery `.modal()`)
        const modalElement = document.getElementById(modalId);
        const bsModal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false
        });
        bsModal.show();
    }


    function closeTimeOptionModal() {
        $('#timeOptionModal').modal('hide').on('hidden.bs.modal', function () {
            $(this).remove(); // Clean up after hiding
        });
        modalContext = null;
    }

    function onCancelSelection() {
        if (!modalContext) return;

        // If editing an event, revert to "Available"
        if (modalContext.type === 'event' && modalContext.event) {
            modalContext.event.setProp('title', 'Available');
            modalContext.event.setProp('backgroundColor', '#0c8384');
            modalContext.event.setProp('borderColor', '#0c8384');
        }

        updateSelectedDates();
        modalContext = null;
    }


    function onTimeOptionSelected(option) {
        if (!modalContext) return;

        if (modalContext.type === 'date') {
            calendar.addEvent({
                title: `Selected (${option})`,
                start: modalContext.dateStr,
                allDay: option === 'wholeday',
                backgroundColor: '#f0ad4e',
                borderColor: '#f0ad4e',
                extendedProps: { shift: option } // ✅ store shift info
            });
        } else if (modalContext.type === 'event' && modalContext.event) {
            modalContext.event.setProp('title', `Selected (${option})`);
            modalContext.event.setExtendedProp('shift', option); // ✅ store shift
            modalContext.event.setProp('backgroundColor', '#f0ad4e');
            modalContext.event.setProp('borderColor', '#f0ad4e');
        }

        updateSelectedDates();
        closeTimeOptionModal();
    }


    // ------------------------
    // FullCalendar options
    // ------------------------
    const defaultOptions = {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth',
        },
        events: [],
        eventOrder: 'order',
        eventContent: function (arg) {
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
        `,
            };
        },
        dateClick: function (info) {
            const clickedDate = new Date(info.dateStr);
            const dayOfWeek = clickedDate.getDay();
        },
        eventClick: function (info) {
            const clickedEvent = info.event;
            const title = clickedEvent.title.toLowerCase();

            if (title !== 'available' && !title.includes('selected')) {
                alert('info', 'The selected date is currently unavailable as it has already been used or allocated.')
                return;
            }

            if (clickedEvent.classNames.includes('more-event')) {
                const wordDate = new Date(clickedEvent.startStr).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                });

                const allEventsOnDate = getEventsByDate(clickedEvent.startStr);

                createAndShowPopover(info.el, `${wordDate}`, allEventsOnDate);
                return;
            }

            showTimeOptionModal({ type: 'event', event: clickedEvent, dateStr: clickedEvent.startStr });
        },
    };

    calendar = new Calendar(calendarEl, { ...defaultOptions, ...options });
    calendar.render();
}

export function setEvents(events = [], raw = []) {
    rawData = raw;

    if (!calendar) {
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

    let currentDate = new Date(year, 0, 1);
    currentDate.setDate(currentDate.getDate() + 1);

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
        const dayOfWeek = currentDate.getDay();
        const dateStr = currentDate.toISOString().slice(0, 10);

        if (dayOfWeek >= 0) {
            const items = unavailableMap[dateStr];

            if (items && items.length > 0) {
                const maxVisibleItems = 2;
                const dateEvents = [];

                for (const item of items) {
                    const status = item.status;
                    const title = item.title;

                    let backgroundColor = '#0c8384';

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
                        dateEvents.push({
                            title: status,
                            start: dateStr,
                            allDay: true,
                            backgroundColor,
                            borderColor: backgroundColor,
                            order: orderCounter++,
                            className: 'text-center text-uppercase',
                        });

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
                        dateEvents.push({
                            title,
                            start: dateStr,
                            allDay: true,
                            backgroundColor,
                            className: 'text-center text-uppercase',
                            borderColor: backgroundColor,
                            order: orderCounter++,
                        });

                        dateEvents.push({
                            title: status,
                            start: dateStr,
                            backgroundColor: 'transparent',
                            textColor: '#0c8384',
                            borderColor: 'transparent',
                            className: 'text-center text-uppercase',
                            order: orderCounter++,
                        });
                    }
                }

                if (items.length > maxVisibleItems) {
                    const visibleEventsCount = maxVisibleItems * 2;
                    events.push(...dateEvents.slice(0, visibleEventsCount));

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
                    events.push(...dateEvents);
                }
            } else {
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

    const selectedData = calendar.getEvents()
        .filter(event => event.title.startsWith('Selected'))
        .map(event => {
            const date = event.startStr;

            // Extract shift from title like "Selected (morning)"
            const match = event.title.match(/\((.*?)\)/);
            const shift = match ? match[1].toLowerCase() : 'wholeday';

            return { date, shift };
        })
        .sort((a, b) => a.date.localeCompare(b.date)); // Optional: sort by date
    const data = JSON.stringify(selectedData);

    $('#selectedDates').val(data ?? null); // Pretty print for visibility
}



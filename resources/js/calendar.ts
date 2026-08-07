import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

const calendarElement = document.getElementById('calendar');

if (calendarElement) {
    const jobsUrl = calendarElement.dataset.jobsUrl;

    const navigateToDate = (date: string) => {
        if (!jobsUrl) {
            return;
        }

        const destination = new URL(jobsUrl, window.location.origin);
        destination.searchParams.set('date', date);
        window.location.assign(destination.toString());
    };

    const calendar = new Calendar(calendarElement, {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: '',
        },
        height: 'auto',
        eventOrder: (first, second) =>
            Number(first.extendedProps.order) -
            Number(second.extendedProps.order),
        events: async (fetchInfo, successCallback, failureCallback) => {
            const countsUrl = calendarElement.dataset.countsUrl;

            if (!countsUrl) {
                successCallback([]);

                return;
            }

            const endpoint = new URL(countsUrl, window.location.origin);
            endpoint.searchParams.set('start', fetchInfo.startStr.slice(0, 10));
            endpoint.searchParams.set('end', fetchInfo.endStr.slice(0, 10));

            try {
                const response = await fetch(endpoint, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    throw new Error(
                        `Calendar counts failed with HTTP ${response.status}`,
                    );
                }

                const days: Array<{
                    date: string;
                    whitelisted: number;
                    verified: number;
                    regular: number;
                }> = await response.json();

                successCallback(
                    days.flatMap((day) => [
                        ...(day.whitelisted > 0
                            ? [
                                  {
                                      title: String(day.whitelisted),
                                      start: day.date,
                                      allDay: true,
                                      classNames: ['calendar-whitelist-count'],
                                      order: 1,
                                  },
                              ]
                            : []),
                        ...(day.verified > 0
                            ? [
                                  {
                                      title: String(day.verified),
                                      start: day.date,
                                      allDay: true,
                                      classNames: ['calendar-verified-count'],
                                      order: 2,
                                  },
                              ]
                            : []),
                        ...(day.regular > 0
                            ? [
                                  {
                                      title: String(day.regular),
                                      start: day.date,
                                      allDay: true,
                                      classNames: ['calendar-regular-count'],
                                      order: 3,
                                  },
                              ]
                            : []),
                    ]),
                );
            } catch (error) {
                console.error(error);
                failureCallback(error as Error);
            }
        },
        dateClick(info) {
            navigateToDate(info.dateStr);
        },
        eventClick(info) {
            navigateToDate(info.event.startStr);
        },
    });

    calendar.render();
}

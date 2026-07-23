import Sortable from 'sortablejs';

const csrfToken = document
    .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
    ?.getAttribute('content');

const updateColumnCounts = () => {
    document
        .querySelectorAll<HTMLElement>('.kanban-column')
        .forEach((column) => {
            const count = column.querySelectorAll('[data-job-card]').length;
            const badge = column.querySelector<HTMLElement>(
                '[data-column-count]',
            );

            if (badge) {
                badge.textContent = String(count);
            }
        });
};

const updateCardActions = (card: HTMLElement) => {
    const column = card.closest<HTMLElement>('[data-kanban-column]');
    const button = card.querySelector<HTMLElement>('[data-not-interested]');
    const canMarkNotInterested = ['backlog', 'interested'].includes(
        column?.dataset.columnId ?? '',
    );

    button?.classList.toggle('d-none', !canMarkNotInterested);
};

document.addEventListener('click', async (event) => {
    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    const button = target.closest<HTMLButtonElement>('[data-not-interested]');

    if (!button) {
        return;
    }

    const card = button.closest<HTMLElement>('[data-job-card]');
    const statusUrl = card?.dataset.statusUrl;

    if (!card || !statusUrl || !csrfToken) {
        return;
    }

    button.disabled = true;

    try {
        const response = await fetch(statusUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ status: 'not_interested' }),
        });

        if (!response.ok) {
            throw new Error(
                `Not interested update failed with HTTP ${response.status}`,
            );
        }

        card.remove();
        updateColumnCounts();
    } catch (error) {
        console.error(error);
        button.disabled = false;
        window.alert(
            'The job could not be marked Not Interested. Please try again.',
        );
    }
});

document
    .querySelectorAll<HTMLElement>('[data-kanban-column]')
    .forEach((column) => {
        Sortable.create(column, {
            group: 'jobs-kanban',
            animation: 150,
            ghostClass: 'kanban-card-ghost',
            chosenClass: 'kanban-card-chosen',
            dragClass: 'kanban-card-dragging',
            filter: '[data-no-drag]',
            preventOnFilter: false,
            async onEnd(event) {
                const card = event.item as HTMLElement;
                const destination = event.to as HTMLElement;
                const updateUrl = card.dataset.updateUrl;
                const kanbanStatus = destination.dataset.columnId;

                updateColumnCounts();
                updateCardActions(card);

                if (!updateUrl || !kanbanStatus || !csrfToken) {
                    window.location.reload();
                    return;
                }

                try {
                    const response = await fetch(updateUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ kanban_status: kanbanStatus }),
                    });

                    if (!response.ok) {
                        throw new Error(
                            `Kanban update failed with HTTP ${response.status}`,
                        );
                    }
                } catch (error) {
                    console.error(error);
                    window.alert(
                        'The Kanban status could not be saved. The board will be refreshed.',
                    );
                    window.location.reload();
                }
            },
        });
    });

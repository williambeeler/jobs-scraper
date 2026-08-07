import Sortable from 'sortablejs';

const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
const todoBoard = document.querySelector<HTMLElement>('[data-todo-board]');

const updateCounts = () => {
    document.querySelectorAll<HTMLElement>('.todo-column').forEach((column) => {
        const badge = column.querySelector<HTMLElement>('[data-card-count]');
        const count = column.querySelectorAll('[data-card]').length;

        if (badge) {
            badge.textContent = String(count);
        }
    });
};

document.querySelectorAll<HTMLElement>('[data-card-list]').forEach((list) => {
    Sortable.create(list, {
        group: 'todo-board-cards',
        animation: 150,
        ghostClass: 'todo-card-ghost',
        filter: 'button, a, input, textarea',
        preventOnFilter: false,
        async onEnd(event) {
            const card = event.item as HTMLElement;
            const destination = event.to as HTMLElement;
            const moveUrl = card.dataset.moveUrl;
            const boardColumnId = Number(destination.dataset.columnId);

            updateCounts();

            if (!moveUrl || !boardColumnId || !csrfToken) {
                window.location.reload();

                return;
            }

            try {
                const response = await fetch(moveUrl, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ board_column_id: boardColumnId }),
                });

                if (!response.ok) {
                    throw new Error(`Card move failed with HTTP ${response.status}`);
                }
            } catch (error) {
                console.error(error);
                window.alert('The card could not be moved. The board will reload.');
                window.location.reload();
            }
        },
    });
});

if (todoBoard) {
    Sortable.create(todoBoard, {
        animation: 150,
        draggable: '[data-board-column]',
        handle: '[data-column-drag-handle]',
        ghostClass: 'todo-column-ghost',
        async onEnd() {
            const reorderUrl = todoBoard.dataset.reorderUrl;
            const columns = Array.from(todoBoard.querySelectorAll<HTMLElement>('[data-board-column]'))
                .map((column) => Number(column.dataset.columnId));

            if (!reorderUrl || !csrfToken) {
                window.location.reload();

                return;
            }

            try {
                const response = await fetch(reorderUrl, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ columns }),
                });

                if (!response.ok) {
                    throw new Error(`Column reorder failed with HTTP ${response.status}`);
                }
            } catch (error) {
                console.error(error);
                window.alert('The column order could not be saved. The board will reload.');
                window.location.reload();
            }
        },
    });
}

document.addEventListener('click', (event) => {
    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    const cancel = target.closest<HTMLElement>('[data-column-title-cancel]');

    if (cancel) {
        const wrapper = cancel.closest<HTMLElement>('.todo-column-title-wrap');
        const title = wrapper?.querySelector<HTMLElement>('[data-column-title]');
        const form = wrapper?.querySelector<HTMLFormElement>('[data-column-title-form]');
        const input = wrapper?.querySelector<HTMLInputElement>('[data-column-title-input]');

        form?.reset();
        form?.classList.add('d-none');
        title?.classList.remove('d-none');
        input?.blur();
        title?.focus();

        return;
    }

    const title = target.closest<HTMLElement>('[data-column-title]');

    if (!title) {
        return;
    }

    const wrapper = title.closest<HTMLElement>('.todo-column-title-wrap');
    const form = wrapper?.querySelector<HTMLElement>('[data-column-title-form]');
    const input = wrapper?.querySelector<HTMLInputElement>('[data-column-title-input]');

    title.classList.add('d-none');
    form?.classList.remove('d-none');
    input?.focus();
    input?.select();
});

document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.matches('[data-card-modal-form]')) {
        return;
    }

    event.preventDefault();

    const modal = form.closest<HTMLElement>('.modal');
    const modalContent = modal?.querySelector<HTMLElement>('.modal-content');
    const submitters = form.querySelectorAll<HTMLButtonElement>('button[type="submit"]');

    if (!modal?.id || !modalContent) {
        return;
    }

    submitters.forEach((button) => {
        button.disabled = true;
    });

    try {
        const response = await fetch(form.action, {
            method: form.method,
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            const result = await response.json().catch(() => null) as { message?: string } | null;

            throw new Error(result?.message ?? `Request failed with HTTP ${response.status}`);
        }

        const pageResponse = await fetch(window.location.href, {
            credentials: 'same-origin',
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!pageResponse.ok) {
            throw new Error(`Modal refresh failed with HTTP ${pageResponse.status}`);
        }

        const page = new DOMParser().parseFromString(await pageResponse.text(), 'text/html');
        const refreshedContent = page.getElementById(modal.id)?.querySelector<HTMLElement>('.modal-content');

        if (!refreshedContent) {
            throw new Error('The refreshed modal could not be found.');
        }

        const modalBody = modalContent.querySelector<HTMLElement>('.modal-body');
        const scrollTop = modalBody?.scrollTop ?? 0;
        const cardForm = modalContent.querySelector<HTMLFormElement>('form[id^="card-update-"]');
        const pendingCardValues = new Map<string, string>();

        cardForm?.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('[name]')
            .forEach((input) => {
                pendingCardValues.set(input.name, input.value);
            });

        document.dispatchEvent(new CustomEvent('todo:modal-will-refresh', { detail: modal }));
        modalContent.replaceWith(refreshedContent);
        refreshedContent
            .querySelector<HTMLFormElement>('form[id^="card-update-"]')
            ?.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>('[name]')
            .forEach((input) => {
                const value = pendingCardValues.get(input.name);

                if (value !== undefined) {
                    input.value = value;
                }
            });
        refreshedContent.querySelector<HTMLElement>('.modal-body')?.scrollTo({ top: scrollTop });
        document.dispatchEvent(new CustomEvent('todo:modal-refreshed', { detail: modal }));
    } catch (error) {
        console.error(error);
        window.alert(error instanceof Error ? error.message : 'The change could not be saved.');
        submitters.forEach((button) => {
            button.disabled = false;
        });
    }
});

document.addEventListener('click', async (event) => {
    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    const completeButton = target.closest<HTMLButtonElement>('[data-card-complete]');

    if (!completeButton) {
        return;
    }

    const card = completeButton.closest<HTMLElement>('[data-card]');
    const completeUrl = completeButton.dataset.completeUrl;

    if (!card || !completeUrl || !csrfToken) {
        return;
    }

    completeButton.disabled = true;

    try {
        const response = await fetch(completeUrl, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Card completion failed with HTTP ${response.status}`);
        }

        const result = await response.json() as { completed: boolean };
        card.classList.toggle('is-complete', result.completed);
        completeButton.ariaLabel = result.completed ? 'Mark incomplete' : 'Complete';
        completeButton.title = completeButton.ariaLabel;
    } catch (error) {
        console.error(error);
        window.alert('The card completion could not be saved.');
    } finally {
        completeButton.disabled = false;
    }
});

document.addEventListener('click', (event) => {
    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    const start = target.closest<HTMLElement>('[data-delete-card-start]');
    const cancel = target.closest<HTMLElement>('[data-delete-card-cancel]');
    const modal = target.closest<HTMLElement>('.modal-content');
    const confirmation = modal?.querySelector<HTMLElement>('[data-delete-card-confirmation]');

    if (start) {
        confirmation?.classList.remove('d-none');
    } else if (cancel) {
        confirmation?.classList.add('d-none');
    }
});

document.addEventListener('click', (event) => {
    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    const addTrigger = target.closest<HTMLElement>('[data-add-card-trigger]');

    if (addTrigger) {
        const column = addTrigger.closest<HTMLElement>('[data-board-column]');
        const form = column?.querySelector<HTMLElement>('[data-add-card-form]');
        const title = column?.querySelector<HTMLTextAreaElement>('[data-add-card-title]');

        addTrigger.classList.add('d-none');
        form?.classList.remove('d-none');
        title?.focus();

        return;
    }

    const cancel = target.closest<HTMLElement>('[data-add-card-cancel]');

    if (cancel) {
        const column = cancel.closest<HTMLElement>('[data-board-column]');
        const form = column?.querySelector<HTMLFormElement>('[data-add-card-form]');
        const trigger = column?.querySelector<HTMLElement>('[data-add-card-trigger]');

        form?.reset();
        form?.classList.add('d-none');
        trigger?.classList.remove('d-none');
        trigger?.focus();
    }
});

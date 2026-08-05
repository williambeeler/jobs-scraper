function updateProgress(page, completedCount, totalCount) {
    const progress =
        totalCount > 0 ? Math.round((completedCount / totalCount) * 100) : 0;
    const progressValue = page.querySelector('[data-bible-progress]');
    const progressBar = page.querySelector('.bible-progress-bar span');
    const subtitle = page.querySelector('.bible-hero-subtitle');

    if (progressValue) {
        progressValue.textContent = `${progress}%`;
    }

    if (progressBar) {
        progressBar.style.width = `${progress}%`;
    }

    if (subtitle) {
        subtitle.textContent = `${completedCount} of ${totalCount} chapters completed`;
    }
}

function updateBookProgress(page, bookSlug) {
    const checkboxes = Array.from(
        page.querySelectorAll(
            `.bible-chapter-checkbox[data-book="${bookSlug}"]`,
        ),
    );
    const checkedCount = checkboxes.filter(
        (checkbox) => checkbox.checked,
    ).length;
    const progress = page.querySelector(
        `[data-bible-book-progress="${bookSlug}"]`,
    );

    if (progress) {
        progress.textContent = `${checkedCount}/${checkboxes.length}`;
    }
}

function setFeedback(page, message, state = '') {
    const feedback = page.querySelector('[data-bible-feedback]');

    if (!feedback) {
        return;
    }

    feedback.textContent = message;
    feedback.classList.toggle('is-saving', state === 'saving');
    feedback.classList.toggle('is-error', state === 'error');
}

function initBibleReadingPage() {
    const page = document.querySelector('[data-bible-reading-page]');

    if (!page) {
        return;
    }

    const csrfToken = document.querySelector(
        'meta[name="csrf-token"]',
    )?.content;
    const urlTemplate = page.dataset.updateUrlTemplate;

    page.querySelectorAll('.bible-chapter-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', async (event) => {
            const target = event.currentTarget;
            const previousChecked = !target.checked;
            const book = target.dataset.book;
            const chapter = target.dataset.chapter;
            const year = target.dataset.year;

            if (!csrfToken || !urlTemplate || !book || !chapter || !year) {
                target.checked = previousChecked;
                setFeedback(
                    page,
                    'The page is missing the data needed to save changes.',
                    'error',
                );

                return;
            }

            const updateUrl = urlTemplate
                .replace('__YEAR__', year)
                .replace('__BOOK__', book)
                .replace('__CHAPTER__', chapter);

            setFeedback(
                page,
                `Saving ${book.replaceAll('-', ' ')} ${chapter}...`,
                'saving',
            );

            try {
                const response = await window.fetch(updateUrl, {
                    method: 'PUT',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        read: target.checked,
                    }),
                });

                if (!response.ok) {
                    throw new Error(
                        `Request failed with status ${response.status}`,
                    );
                }

                const payload = await response.json();
                updateProgress(
                    page,
                    payload.completed_chapters_count,
                    payload.total_chapters_count,
                );
                updateBookProgress(page, book);
                setFeedback(
                    page,
                    target.checked ? 'Chapter saved.' : 'Chapter unchecked.',
                );
            } catch {
                target.checked = previousChecked;
                updateBookProgress(page, book);
                setFeedback(
                    page,
                    'That change could not be saved. Please try again.',
                    'error',
                );
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initBibleReadingPage();
});

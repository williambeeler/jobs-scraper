const rowsForCompany = (companyId: string): HTMLElement[] => Array.from(
    document.querySelectorAll<HTMLElement>('[data-job-row]'),
).filter((row) => row.dataset.companyId === companyId);

document.addEventListener('submit', async (event) => {
    const target = event.target;

    if (!(target instanceof HTMLFormElement) || !target.matches('[data-company-preference-form]')) {
        return;
    }

    event.preventDefault();

    const sourceRow = target.closest<HTMLElement>('[data-job-row]');

    if (!sourceRow?.dataset.companyId) {
        return;
    }

    const companyRows = rowsForCompany(sourceRow.dataset.companyId);
    const buttons = companyRows.flatMap((row) => Array.from(
        row.querySelectorAll<HTMLButtonElement>('[data-company-preference-button]'),
    ));

    buttons.forEach((button) => {
        button.disabled = true;
    });

    try {
        const response = await fetch(target.action, {
            method: 'POST',
            body: new FormData(target),
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Company preference update failed with HTTP ${response.status}`);
        }

        const preference = await response.json() as { type: string; company_url: string };

        if (preference.type === 'blacklist') {
            companyRows.forEach((row) => row.remove());

            return;
        }

        companyRows.forEach((row) => {
            const isWhitelisted = preference.type === 'whitelist';
            const isVerified = preference.type === 'verified';
            const whitelistForm = row.querySelector<HTMLInputElement>('input[value="whitelist"]')?.closest('form');
            const verifiedForm = row.querySelector<HTMLInputElement>('input[value="verified"]')?.closest('form');
            const whitelistButton = whitelistForm?.querySelector<HTMLButtonElement>('button');
            const verifiedButton = verifiedForm?.querySelector<HTMLButtonElement>('button');
            const star = row.querySelector<HTMLElement>('[data-job-row-star]');
            const whitelistLabel = row.querySelector<HTMLElement>('[data-whitelist-label]');

            row.classList.toggle('job-row-whitelisted', isWhitelisted);
            row.classList.toggle('job-row-verified', isVerified);
            star?.classList.toggle('d-none', !isWhitelisted);
            row.querySelectorAll<HTMLAnchorElement>('[data-company-link]').forEach((link) => {
                link.href = preference.company_url;
            });
            whitelistButton?.classList.toggle('btn-warning', isWhitelisted);
            whitelistButton?.classList.toggle('btn-outline-warning', !isWhitelisted);
            verifiedButton?.classList.toggle('btn-info', isVerified);
            verifiedButton?.classList.toggle('btn-outline-info', !isVerified);

            if (whitelistLabel) {
                whitelistLabel.textContent = isWhitelisted ? 'Whitelisted' : 'Whitelist';
            }
        });
    } catch (error) {
        window.alert('The company preference could not be saved. Please try again.');
        console.error(error);
    } finally {
        buttons.forEach((button) => {
            button.disabled = false;
        });
    }
});

document.addEventListener('submit', async (event) => {
    const target = event.target;

    if (!(target instanceof HTMLFormElement) || !target.matches('[data-job-status-form]')) {
        return;
    }

    event.preventDefault();

    const row = target.closest<HTMLElement>('[data-job-row]');

    if (!row) {
        return;
    }

    const buttons = row.querySelectorAll<HTMLButtonElement>('button');
    buttons.forEach((button) => {
        button.disabled = true;
    });

    try {
        const response = await fetch(target.action, {
            method: 'POST',
            body: new FormData(target),
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Status update failed with HTTP ${response.status}`);
        }

        row.style.transition = 'opacity 150ms ease, transform 150ms ease';
        row.style.opacity = '0';
        row.style.transform = 'translateX(12px)';
        window.setTimeout(() => row.remove(), 150);
    } catch (error) {
        buttons.forEach((button) => {
            button.disabled = false;
        });
        window.alert('The job decision could not be saved. Please try again.');
        console.error(error);
    }
});

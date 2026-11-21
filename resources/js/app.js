const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Tampilkan / sembunyikan kata sandi: <button data-password-toggle="idInput">
document.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-password-toggle]');
    if (!btn) return;

    const input = document.getElementById(btn.dataset.passwordToggle);
    if (!input) return;

    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
    btn.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    btn.querySelector('[data-eye="open"]')?.classList.toggle('hidden', show);
    btn.querySelector('[data-eye="closed"]')?.classList.toggle('hidden', !show);
});

// Cegah kirim ganda dan beri tahu bahwa formulir sedang diproses:
// <form data-submit-loading> dengan <button type="submit" data-loading-text="Menyimpan...">
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-submit-loading')) return;

    // event.submitter juga menangkap tombol di luar formulir yang memakai atribut form="...".
    const btn = event.submitter ?? form.querySelector('button[type="submit"]');
    if (!btn) return;

    if (btn.dataset.busy === 'true') {
        event.preventDefault();
        return;
    }

    btn.dataset.busy = 'true';
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    const label = btn.querySelector('[data-label]');
    if (label && btn.dataset.loadingText) {
        label.dataset.original = label.textContent;
        label.textContent = btn.dataset.loadingText;
    }
    btn.querySelector('[data-spinner]')?.classList.remove('hidden');
    btn.querySelector('[data-icon]')?.classList.add('hidden');
});

// Halaman yang dibuka ulang dari riwayat browser: kembalikan tombol ke keadaan normal.
window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;
    document.querySelectorAll('button[data-busy="true"]').forEach((btn) => {
        btn.dataset.busy = 'false';
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
        const label = btn.querySelector('[data-label]');
        if (label?.dataset.original) label.textContent = label.dataset.original;
        btn.querySelector('[data-spinner]')?.classList.add('hidden');
        btn.querySelector('[data-icon]')?.classList.remove('hidden');
    });
});

// Tutup notifikasi: <div data-alert> ... <button data-alert-dismiss>
document.addEventListener('click', (event) => {
    event.target.closest('[data-alert-dismiss]')?.closest('[data-alert]')?.remove();
});

// Dialog bersama. Markup: <div id="x" data-dialog class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
// dengan .dialog-backdrop dan .dialog di dalamnya. Buka lewat <button data-dialog-open="x"> atau openDialog('x'),
// tutup lewat [data-dialog-close], klik latar, atau Escape. Fokus pindah ke [autofocus] / tombol pertama,
// lalu kembali ke pemicu saat ditutup.
const openDialogs = [];

function openDialog(id, trigger = document.activeElement) {
    const dialog = document.getElementById(id);
    if (!dialog || !dialog.classList.contains('hidden')) return;

    dialog.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    openDialogs.push({ dialog, trigger });

    const target = dialog.querySelector('[autofocus], [data-dialog-focus]')
        ?? dialog.querySelector('.dialog button, .dialog [href], .dialog input, .dialog textarea, .dialog select');
    // Elemen sudah terlihat begitu kelas hidden dilepas, jadi fokus bisa dipindah saat itu juga.
    target?.focus({ preventScroll: true });
}

function closeDialog(id) {
    const index = id
        ? openDialogs.findIndex((entry) => entry.dialog.id === id)
        : openDialogs.length - 1;
    if (index < 0) return;

    const [{ dialog, trigger }] = openDialogs.splice(index, 1);
    dialog.classList.add('hidden');
    if (openDialogs.length === 0) document.body.style.overflow = '';
    if (trigger instanceof HTMLElement && document.contains(trigger)) trigger.focus();
}

window.openDialog = openDialog;
window.closeDialog = closeDialog;

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-dialog-open]');
    if (opener) {
        openDialog(opener.dataset.dialogOpen, opener);
        return;
    }

    const dialog = event.target.closest('[data-dialog]');
    if (!dialog) return;
    const isBackdrop = event.target.classList.contains('dialog-backdrop') && !dialog.hasAttribute('data-dialog-static');
    if (isBackdrop || event.target.closest('[data-dialog-close]')) closeDialog(dialog.id);
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || openDialogs.length === 0) return;
    const { dialog } = openDialogs[openDialogs.length - 1];
    if (dialog.hasAttribute('data-dialog-static')) return;
    event.preventDefault();
    closeDialog(dialog.id);
});

// Spotlight: cahaya lembut mengikuti kursor di kartu .spotlight (21st.dev "Spotlight Card").
document.addEventListener('pointermove', (event) => {
    const card = event.target.closest?.('.spotlight');
    if (!card) return;
    const rect = card.getBoundingClientRect();
    card.style.setProperty('--spot-x', `${event.clientX - rect.left}px`);
    card.style.setProperty('--spot-y', `${event.clientY - rect.top}px`);
});

// Number ticker: angka statistik berhitung naik ke nilai sebenarnya (21st.dev "Number Ticker").
function runTickers() {
    document.querySelectorAll('[data-ticker]').forEach((el) => {
        const target = parseInt(el.dataset.ticker, 10);
        if (Number.isNaN(target) || reduceMotion || target === 0) {
            el.textContent = String(target || 0);
            return;
        }

        const duration = Math.min(1200, 500 + target * 40);
        const start = performance.now();
        const step = (now) => {
            const t = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - t, 4);
            el.textContent = String(Math.round(target * eased));
            if (t < 1) requestAnimationFrame(step);
        };
        el.textContent = '0';
        requestAnimationFrame(step);
    });
}

// Navigasi pil dengan indikator yang bergeser (21st.dev "Animated Tabs").
function enhancePillNav(nav) {
    const items = [...nav.querySelectorAll(':scope > a, :scope > button')];
    if (items.length === 0) return;

    const indicator = document.createElement('span');
    indicator.className = 'pill-indicator';
    indicator.setAttribute('aria-hidden', 'true');
    const hover = document.createElement('span');
    hover.className = 'pill-hover';
    hover.setAttribute('aria-hidden', 'true');
    nav.prepend(hover);
    nav.prepend(indicator);

    const place = (el, target) => {
        target.style.width = `${el.offsetWidth}px`;
        target.style.transform = `translateX(${el.offsetLeft}px)`;
    };

    const active = items.find((el) => el.hasAttribute('aria-current'));
    if (active) {
        indicator.style.transition = 'none';
        place(active, indicator);
        requestAnimationFrame(() => { indicator.style.transition = ''; });
    } else {
        indicator.style.opacity = '0';
    }

    items.forEach((el) => {
        el.addEventListener('pointerenter', () => {
            if (el.hasAttribute('aria-current')) {
                hover.style.opacity = '0';
                return;
            }
            hover.style.opacity = '';
            if (!hover.classList.contains('is-ready')) {
                hover.style.transition = 'none';
                place(el, hover);
                requestAnimationFrame(() => {
                    hover.style.transition = '';
                    hover.classList.add('is-ready');
                });
            } else {
                place(el, hover);
            }
        });

        // Klik: geser indikator dulu ke item tujuan sebelum halaman berpindah.
        el.addEventListener('click', () => {
            if (el.tagName !== 'A') return;
            items.forEach((i) => i.removeAttribute('aria-current'));
            el.setAttribute('aria-current', 'page');
            indicator.style.opacity = '';
            place(el, indicator);
            hover.style.opacity = '0';
        });
    });

    nav.addEventListener('pointerleave', () => hover.classList.remove('is-ready'));
    window.addEventListener('resize', () => {
        const current = items.find((el) => el.hasAttribute('aria-current'));
        if (current) place(current, indicator);
    });

    nav.classList.add('is-enhanced');
}

function init() {
    document.querySelectorAll('.pill-nav').forEach(enhancePillNav);
    runTickers();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

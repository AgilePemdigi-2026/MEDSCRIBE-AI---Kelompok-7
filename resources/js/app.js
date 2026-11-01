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

    const btn = form.querySelector('button[type="submit"]');
    if (!btn) return;

    if (btn.dataset.busy === 'true') {
        event.preventDefault();
        return;
    }

    btn.dataset.busy = 'true';
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    const label = btn.querySelector('[data-label]');
    if (label && btn.dataset.loadingText) label.textContent = btn.dataset.loadingText;
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
        btn.querySelector('[data-spinner]')?.classList.add('hidden');
        btn.querySelector('[data-icon]')?.classList.remove('hidden');
    });
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

// Bilah progres: isi dari 0 ke persentase sebenarnya setelah halaman tampil.
function runBars() {
    document.querySelectorAll('[data-bar]').forEach((el) => {
        const width = `${Math.max(0, Math.min(100, parseFloat(el.dataset.bar) || 0))}%`;
        if (reduceMotion) {
            el.style.width = width;
        } else {
            requestAnimationFrame(() => requestAnimationFrame(() => { el.style.width = width; }));
        }
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
    runBars();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

{{--
    Carousel polaroid tergantung (diadaptasi dari 21st.dev "Hanging Polaroid Image Carousel" oleh kedhareswer).
    Gambar = tangkapan layar MedScribe AI dengan akun & data uji coba, bukan data pasien asli.
--}}
@php
    $polaroids = [
        ['polaroid-1-ttv', 'Perawat mengisi TTV', 'Perawat', 'Formulir tanda-tanda vital di Nurse Station: tekanan darah, nadi, suhu, pernapasan, berat dan tinggi badan.'],
        ['polaroid-2-antrean', 'Antrean tertata rapi', 'Perawat', 'Daftar antrean Nurse Station dengan tanda centang untuk pasien yang TTV-nya sudah diisi.'],
        ['polaroid-3-ringkasan', 'Dokter memantau antrean', 'Dokter', 'Ringkasan antrean di dasbor dokter berupa grafik donat per status.'],
        ['polaroid-4-rekam-medis', 'TTV langsung terbaca dokter', 'Dokter', 'Kartu TTV dan catatan perawat di halaman rekam medis dokter.'],
        ['polaroid-5-wawancara', 'Wawancara klinis direkam', 'Dokter', 'Perekam wawancara klinis dengan timer melingkar di halaman rekam medis.'],
    ];
@endphp

<section class="polaroid-carousel" aria-roledescription="carousel" aria-label="Cuplikan fitur MedScribe AI" data-polaroid-carousel>
    <div class="polaroid-stage" tabindex="0" aria-label="Geser atau gunakan tombol panah kiri dan kanan untuk berpindah foto">
        <svg class="polaroid-string" preserveAspectRatio="none" aria-hidden="true"><path /></svg>

        @foreach ($polaroids as $i => [$file, $caption, $role, $alt])
            <figure class="polaroid" role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} dari {{ count($polaroids) }}: {{ $caption }}" data-index="{{ $i }}">
                <span class="polaroid-peg" aria-hidden="true"></span>
                <img src="{{ asset('images/polaroid/' . $file . '.webp') }}" width="720" height="540" alt="{{ $alt }}"
                     loading="{{ $i < 3 ? 'eager' : 'lazy' }}" draggable="false">
                <figcaption>
                    <span class="polaroid-caption">{{ $caption }}</span>
                    <span class="polaroid-role">{{ $role }}</span>
                </figcaption>
            </figure>
        @endforeach
    </div>

    <div class="mt-5 flex items-center justify-between gap-3">
        <p class="text-xs text-[#A5ADD8]" aria-live="polite" data-polaroid-status>
            Fitur <span class="font-semibold text-white lowercase" data-polaroid-role>{{ $polaroids[0][2] }}</span>
            <span class="sr-only">: <span data-polaroid-caption>{{ $polaroids[0][1] }}</span></span>
            · <span class="num" data-polaroid-count>1/{{ count($polaroids) }}</span>
        </p>
        <div class="flex items-center gap-1.5">
            <button type="button" class="polaroid-btn" data-polaroid-prev aria-label="Foto sebelumnya">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <button type="button" class="polaroid-btn" data-polaroid-toggle aria-label="Jeda putar otomatis" aria-pressed="false">
                <svg data-icon-pause xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1" /><rect x="14" y="5" width="4" height="14" rx="1" /></svg>
                <svg data-icon-play xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 hidden" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13a1 1 0 001.5.86l10.5-6.5a1 1 0 000-1.72L9.5 4.64A1 1 0 008 5.5z" /></svg>
            </button>
            <button type="button" class="polaroid-btn" data-polaroid-next aria-label="Foto berikutnya">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </button>
        </div>
    </div>
</section>

<script>
    (function () {
        const root = document.querySelector('[data-polaroid-carousel]');
        if (!root) return;

        const stage = root.querySelector('.polaroid-stage');
        const path = root.querySelector('.polaroid-string path');
        const svg = root.querySelector('.polaroid-string');
        const cards = [...root.querySelectorAll('.polaroid')];
        const n = cards.length;
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const AUTOPLAY_MS = 5000;

        let pos = 0;              // posisi (bisa pecahan saat digeser)
        let swing = 0;            // ayunan karena kecepatan geser, dalam derajat
        let dragging = false, startX = 0, startPos = 0, lastX = 0, lastT = 0, velocity = 0, moved = 0;
        let paused = reduce, hoverPause = false, timer = null;

        const geom = () => {
            const w = stage.clientWidth;
            const h = stage.clientHeight;
            return { w, h, spacing: Math.min(230, w * 0.38), top: 16, sag: Math.min(40, w * 0.065) };
        };

        // Tali melengkung: y = top + sag * (1 - t^2), t = jarak dari tengah dinormalisasi.
        const stringY = (x, g) => {
            const t = (x - g.w / 2) / (g.w / 2);
            return g.top + g.sag * (1 - Math.min(1, t * t));
        };

        function drawString(g) {
            svg.setAttribute('viewBox', `0 0 ${g.w} ${g.h}`);
            path.setAttribute('d', `M -20 ${g.top - 4} Q ${g.w / 2} ${g.top + g.sag * 2} ${g.w + 20} ${g.top - 4}`);
        }

        function render() {
            const g = geom();
            drawString(g);
            cards.forEach((card, i) => {
                let d = i - pos;
                const cw = card.offsetWidth;
                const cx = g.w / 2 + d * g.spacing;
                const y = stringY(cx, g);
                const dist = Math.abs(d);
                const scale = Math.max(0.78, 1 - dist * 0.12);
                const rot = d * -5 + swing * (1 - Math.min(dist, 1) * 0.4);
                card.style.transform = `translate(${cx - cw / 2}px, ${y}px) rotate(${rot}deg) scale(${scale})`;
                card.style.opacity = dist > 2.4 ? 0 : String(Math.max(0.35, 1 - dist * 0.32));
                card.style.zIndex = String(100 - Math.round(dist * 10));
                card.classList.toggle('is-active', Math.round(pos) === i);
                card.setAttribute('aria-hidden', dist > 0.5 ? 'true' : 'false');
            });
            const active = ((Math.round(pos) % n) + n) % n;
            const fc = cards[active].querySelector('figcaption');
            root.querySelector('[data-polaroid-role]').textContent = fc.querySelector('.polaroid-role').textContent;
            root.querySelector('[data-polaroid-caption]').textContent = fc.querySelector('.polaroid-caption').textContent;
            root.querySelector('[data-polaroid-count]').textContent = `${active + 1}/${n}`;
        }

        function goTo(index) {
            const dir = Math.sign(index - Math.round(pos));
            pos = ((index % n) + n) % n;
            root.classList.add('is-settling');
            if (!reduce && dir !== 0) {
                // Ayun kecil berlawanan arah gerak, lalu tenang (efek "berayun di jepitan").
                swing = dir * -7;
                render();
                setTimeout(() => { swing = 0; render(); }, 280);
            } else {
                render();
            }
            restartTimer();
        }

        function restartTimer() {
            clearInterval(timer);
            if (!paused && !hoverPause) timer = setInterval(() => goTo(Math.round(pos) + 1), AUTOPLAY_MS);
        }

        // Geser dengan mouse / sentuh
        stage.addEventListener('pointerdown', (e) => {
            if (e.button !== 0) return;
            dragging = true; moved = 0;
            startX = lastX = e.clientX; startPos = pos; lastT = performance.now(); velocity = 0;
            root.classList.remove('is-settling');
            stage.setPointerCapture(e.pointerId);
            clearInterval(timer);
        });

        stage.addEventListener('pointermove', (e) => {
            if (!dragging) return;
            const g = geom();
            const now = performance.now();
            velocity = (e.clientX - lastX) / Math.max(1, now - lastT);
            lastX = e.clientX; lastT = now;
            moved = Math.max(moved, Math.abs(e.clientX - startX));
            pos = Math.max(-0.4, Math.min(n - 0.6, startPos - (e.clientX - startX) / g.spacing));
            swing = reduce ? 0 : Math.max(-14, Math.min(14, velocity * 18));
            render();
        });

        const endDrag = (e) => {
            if (!dragging) return;
            dragging = false;
            if (moved < 6) {
                // Klik biasa: pindah ke polaroid yang diklik.
                const card = e.target.closest?.('.polaroid');
                if (card) { goTo(Number(card.dataset.index)); return; }
            }
            const target = Math.round(pos - velocity * 0.6);
            swing = 0;
            goTo(Math.max(0, Math.min(n - 1, target)));
        };
        stage.addEventListener('pointerup', endDrag);
        stage.addEventListener('pointercancel', endDrag);

        stage.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') { e.preventDefault(); goTo(Math.round(pos) + 1); }
            if (e.key === 'ArrowLeft') { e.preventDefault(); goTo(Math.round(pos) - 1); }
        });

        root.querySelector('[data-polaroid-prev]').addEventListener('click', () => goTo(Math.round(pos) - 1));
        root.querySelector('[data-polaroid-next]').addEventListener('click', () => goTo(Math.round(pos) + 1));

        const toggle = root.querySelector('[data-polaroid-toggle]');
        const syncToggle = () => {
            toggle.setAttribute('aria-pressed', paused ? 'true' : 'false');
            toggle.setAttribute('aria-label', paused ? 'Putar otomatis' : 'Jeda putar otomatis');
            toggle.querySelector('[data-icon-pause]').classList.toggle('hidden', paused);
            toggle.querySelector('[data-icon-play]').classList.toggle('hidden', !paused);
            root.querySelector('[data-polaroid-status]').setAttribute('aria-live', paused ? 'polite' : 'off');
        };
        toggle.addEventListener('click', () => { paused = !paused; syncToggle(); restartTimer(); });

        // Jeda saat disorot atau difokus, agar pengguna sempat membaca.
        root.addEventListener('pointerenter', () => { hoverPause = true; clearInterval(timer); });
        root.addEventListener('pointerleave', () => { hoverPause = false; restartTimer(); });
        root.addEventListener('focusin', () => { hoverPause = true; clearInterval(timer); });
        root.addEventListener('focusout', (e) => { if (!root.contains(e.relatedTarget)) { hoverPause = false; restartTimer(); } });

        window.addEventListener('resize', render);
        syncToggle();
        render();
        requestAnimationFrame(() => root.classList.add('is-ready'));
        restartTimer();
    })();
</script>

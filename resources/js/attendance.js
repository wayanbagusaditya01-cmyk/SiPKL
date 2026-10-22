import { gsap } from 'gsap';

const root = document.querySelector('[data-attendance]');

if (root) {
    initAttendance(root);
}

function initAttendance(root) {
    const one = (selector) => root.querySelector(selector);
    const all = (selector) => [...root.querySelectorAll(selector)];
    const state = root.dataset.state;
    const button = one('[data-attend-button]');
    const hint = one('[data-hint]');
    const pill = one('[data-status]');
    const retry = one('[data-refresh]');
    const me = one('[data-me]');
    const maxAccuracy = Number(root.dataset.maxAccuracy);
    const place = root.dataset.placeLat
        ? { lat: Number(root.dataset.placeLat), lng: Number(root.dataset.placeLng), radius: Number(root.dataset.radius) }
        : null;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const CENTER = 150;

    startClock(one('[data-clock]'), root.dataset.timezone);
    reloadWhenOpening(root.dataset.opensAt);
    initLeaveDialog(root, reducedMotion);
    playIntro();

    if (!place || !['check-in', 'check-out'].includes(state)) {
        return;
    }

    let current = null;
    let watchId = null;
    let hasPosition = false;
    let readyPulse = null;

    gsap.set(me, { x: CENTER, y: CENTER });
    retry?.addEventListener('click', watchLocation);
    button.addEventListener('click', submitAttendance);
    watchLocation();

    function setStatus(pillText, hintText, tone = 'neutral', isError = false) {
        pill.textContent = pillText;
        pill.className = `attend-pill attend-pill--${tone}`;
        hint.textContent = hintText;
        hint.classList.toggle('is-error', isError);
        if (retry) {
            retry.hidden = !isError;
        }
    }

    function watchLocation() {
        if (!window.isSecureContext || !('geolocation' in navigator)) {
            setStatus('Lokasi tidak tersedia', 'Lokasi hanya bisa dibaca melalui HTTPS.', 'warn', true);
            return;
        }

        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
        }

        setStatus('Mencari lokasi', '');
        watchId = navigator.geolocation.watchPosition(showPosition, showLocationError, {
            enableHighAccuracy: true,
            maximumAge: 5000,
            timeout: 20000,
        });
    }

    function showPosition(position) {
        const { latitude, longitude, accuracy } = position.coords;
        const offset = offsetFromPlace(latitude, longitude);
        const isInside = offset.distance <= place.radius;
        const isPrecise = accuracy <= maxAccuracy;
        const meters = Math.round(offset.distance).toLocaleString('id-ID');
        current = { latitude, longitude, accuracy };
        root.dataset.zone = isInside ? 'inside' : 'outside';

        // Di dalam area: titik berada di antara tepi tombol dan garis radius; di luar: di luar garis radius.
        const ringRadius = isInside
            ? 74 + (offset.distance / place.radius) * 22
            : 106 + Math.min(1, (offset.distance - place.radius) / (place.radius * 2)) * 32;
        const angle = Math.atan2(-offset.north, offset.east);
        gsap.to(me, {
            x: CENTER + Math.cos(angle) * ringRadius,
            y: CENTER + Math.sin(angle) * ringRadius,
            opacity: 1,
            duration: hasPosition && !reducedMotion ? 0.9 : 0,
            ease: 'power3.out',
        });
        hasPosition = true;

        if (!isPrecise) {
            setStatus('GPS kurang akurat', `Akurasi ±${Math.round(accuracy)} m. Nyalakan GPS dan tunggu sebentar.`, 'warn', true);
        } else if (!isInside) {
            setStatus('Di luar area', `${meters} m dari lokasi PKL`, 'warn');
        } else {
            setStatus('Di area PKL', `${meters} m dari lokasi PKL`, 'ok');
        }

        setReady(isInside && isPrecise);
    }

    function showLocationError(error) {
        setReady(false);
        delete root.dataset.zone;
        const messages = {
            1: 'Izinkan akses lokasi di pengaturan browser.',
            2: 'Pastikan GPS ponsel menyala.',
            3: 'Lokasi belum didapat.',
        };
        setStatus('Lokasi tidak terbaca', messages[error.code] ?? 'Lokasi gagal dibaca.', 'warn', true);
    }

    function setReady(isReady) {
        if (button.classList.contains('is-loading')) {
            return;
        }

        button.disabled = !isReady;

        if (isReady && !readyPulse && !reducedMotion) {
            readyPulse = gsap.fromTo(one('.attend-button__ring'), { opacity: 0.9, scale: 0.94 }, { opacity: 0, scale: 1.3, duration: 1.6, repeat: -1, ease: 'power2.out' });
        } else if (!isReady && readyPulse) {
            readyPulse.kill();
            readyPulse = null;
            gsap.set(one('.attend-button__ring'), { opacity: 0 });
        }
    }

    async function submitAttendance() {
        if (!current || button.disabled) {
            return;
        }

        button.disabled = true;
        button.classList.add('is-loading');
        gsap.fromTo(button, { scale: 0.92 }, { scale: 1, duration: 0.5, ease: 'back.out(3)' });

        const body = new FormData();
        body.append('latitude', current.latitude);
        body.append('longitude', current.longitude);
        body.append('accuracy', current.accuracy);

        try {
            const response = await fetch(root.dataset.url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body,
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                throw new Error(firstError ?? data.message ?? 'Absensi gagal disimpan. Coba lagi.');
            }

            navigator.geolocation.clearWatch(watchId);
            celebrate(data.message);
        } catch (error) {
            button.classList.remove('is-loading');
            button.disabled = false;
            hint.textContent = error.message;
            hint.classList.add('is-error');
            gsap.fromTo(button, { x: -10 }, { x: 0, duration: 0.6, ease: 'elastic.out(1, 0.3)' });
        }
    }

    function celebrate(message) {
        const overlay = one('[data-success]');
        const circle = one('.attend-success__circle');
        const check = one('.attend-success__check');
        one('[data-success-message]').textContent = message;
        overlay.hidden = false;

        if (reducedMotion) {
            setTimeout(() => window.location.reload(), 1200);
            return;
        }

        const circleLength = circle.getTotalLength();
        const checkLength = check.getTotalLength();
        gsap.set(circle, { strokeDasharray: circleLength, strokeDashoffset: circleLength });
        gsap.set(check, { strokeDasharray: checkLength, strokeDashoffset: checkLength });

        gsap.timeline({ onComplete: () => setTimeout(() => window.location.reload(), 900) })
            .from(overlay, { opacity: 0, duration: 0.3 })
            .to(circle, { strokeDashoffset: 0, duration: 0.6, ease: 'power2.inOut' })
            .to(check, { strokeDashoffset: 0, duration: 0.4, ease: 'power2.out' }, '-=0.1')
            .from(one('[data-success-message]'), { y: 12, opacity: 0, duration: 0.4 }, '-=0.2')
            .add(() => burst(overlay), '-=0.5');
    }

    function burst(overlay) {
        for (let index = 0; index < 16; index++) {
            const spark = document.createElement('span');
            const angle = (index / 16) * Math.PI * 2;
            const reach = 80 + Math.random() * 50;
            spark.className = 'attend-spark';
            overlay.append(spark);
            gsap.fromTo(spark, { x: 0, y: 0, opacity: 1, scale: 1 }, {
                x: Math.cos(angle) * reach,
                y: Math.sin(angle) * reach,
                opacity: 0,
                scale: 0.3,
                duration: 1,
                ease: 'power3.out',
                onComplete: () => spark.remove(),
            });
        }
    }

    function offsetFromPlace(latitude, longitude) {
        const earthRadius = 6371000;
        const toRadians = (degrees) => (degrees * Math.PI) / 180;
        const latitudeDelta = toRadians(latitude - place.lat);
        const longitudeDelta = toRadians(longitude - place.lng);
        const a = Math.sin(latitudeDelta / 2) ** 2
            + Math.cos(toRadians(place.lat)) * Math.cos(toRadians(latitude)) * Math.sin(longitudeDelta / 2) ** 2;

        return {
            distance: 2 * earthRadius * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)),
            east: longitudeDelta * earthRadius * Math.cos(toRadians((latitude + place.lat) / 2)),
            north: latitudeDelta * earthRadius,
        };
    }

    function playIntro() {
        if (reducedMotion) {
            return;
        }

        gsap.timeline({ defaults: { ease: 'power3.out' } })
            .from(one('.attend-main'), { y: 24, opacity: 0, duration: 0.6 })
            .from([one('.attend-clock'), one('.attend-date')], { y: 14, opacity: 0, stagger: 0.08, duration: 0.5 }, '-=0.3')
            .from(all('[data-rings] circle'), { scale: 0.4, opacity: 0, transformOrigin: '50% 50%', stagger: 0.08, duration: 0.8, ease: 'expo.out' }, '-=0.3')
            .from(one('[data-fence]'), { scale: 0.5, opacity: 0, transformOrigin: '50% 50%', duration: 0.9, ease: 'elastic.out(1, 0.6)' }, '-=0.6')
            .from(button, { scale: 0.6, opacity: 0, duration: 0.6, ease: 'back.out(1.8)' }, '-=0.7')
            .from(all('.attend-times > div'), { y: 10, opacity: 0, stagger: 0.06, duration: 0.4 }, '-=0.3')
            .from(all('.attend-stat'), { y: 16, opacity: 0, stagger: 0.06, duration: 0.5 }, 0.2)
            .from(one('.attend-history'), { y: 16, opacity: 0, duration: 0.5 }, 0.35)
            .from(all('[data-animate="entry"]'), { x: -12, opacity: 0, stagger: 0.04, duration: 0.4 }, 0.5);

        all('[data-count]').forEach((element) => {
            const counter = { value: 0 };
            gsap.to(counter, {
                value: Number(element.dataset.count),
                duration: 1.1,
                delay: 0.4,
                ease: 'power2.out',
                onUpdate: () => { element.textContent = Math.round(counter.value); },
            });
        });

        gsap.to(one('[data-sweep]'), { rotation: 360, svgOrigin: '150 150', duration: 5, repeat: -1, ease: 'none' });
        gsap.fromTo(one('[data-pulse]'), { attr: { r: 64 }, opacity: 0.7 }, { attr: { r: 120 }, opacity: 0, duration: 2.4, repeat: -1, ease: 'power1.out' });
    }
}

function startClock(clock, timeZone) {
    if (!clock) {
        return;
    }

    const format = new Intl.DateTimeFormat('id-ID', { timeZone, hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' });
    const tick = () => {
        const parts = Object.fromEntries(format.formatToParts(new Date()).map((part) => [part.type, part.value]));
        clock.innerHTML = `${parts.hour}:${parts.minute}<span>:${parts.second}</span>`;
    };

    tick();
    setInterval(tick, 1000);
}

/**
 * Muat ulang halaman saat jam absen masuk/pulang dibuka agar tombol aktif.
 */
function reloadWhenOpening(opensAt) {
    const delay = opensAt ? new Date(opensAt).getTime() - Date.now() : NaN;

    if (delay > 0 && delay < 12 * 60 * 60 * 1000) {
        setTimeout(() => window.location.reload(), delay + 1000);
    }
}

function initLeaveDialog(root, reducedMotion) {
    const dialog = root.querySelector('#leave-dialog');

    if (!dialog) {
        return;
    }

    const animateOpen = () => {
        if (!reducedMotion) {
            gsap.fromTo(dialog, { y: 24, opacity: 0, scale: 0.97 }, { y: 0, opacity: 1, scale: 1, duration: 0.35, ease: 'power3.out' });
        }
    };

    root.querySelectorAll('[data-dialog="leave-dialog"]').forEach((trigger) => trigger.addEventListener('click', () => requestAnimationFrame(animateOpen)));

    if (dialog.hasAttribute('data-open-on-load')) {
        dialog.showModal();
        document.body.classList.add('modal-open');
    }

    const input = dialog.querySelector('[data-photo-input]');
    const preview = dialog.querySelector('[data-photo-preview]');
    const label = dialog.querySelector('[data-photo-label]');
    const remove = dialog.querySelector('[data-photo-remove]');

    const reset = () => {
        input.value = '';
        preview.hidden = true;
        preview.removeAttribute('src');
        label.textContent = 'Tambah foto';
        remove.hidden = true;
    };

    input.addEventListener('change', () => {
        const file = input.files[0];

        if (!file) {
            reset();
            return;
        }

        preview.src = URL.createObjectURL(file);
        preview.alt = `Pratinjau ${file.name}`;
        preview.hidden = false;
        label.textContent = 'Ganti foto';
        remove.hidden = false;

        if (!reducedMotion) {
            gsap.from(preview, { opacity: 0, scale: 0.96, duration: 0.3, ease: 'power2.out' });
        }
    });
    remove.addEventListener('click', reset);
}

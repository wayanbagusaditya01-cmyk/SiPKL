import { gsap } from 'gsap';

// Pastikan animasi selalu selesai tepat waktu walau perangkat lambat atau tab sempat tertahan,
// sehingga konten tidak pernah tertinggal dalam keadaan transparan.
gsap.ticker.lagSmoothing(0);

const one = (selector, root = document) => root.querySelector(selector);
const all = (selector, root = document) => [...root.querySelectorAll(selector)];
const dialog = one('#confirm-dialog');
let pending = null;
let restoreFocus = null;
const updateScroll = () => document.body.classList.toggle('modal-open', !!one('dialog[open]') || !!one('.sidebar.open'));
all('[data-dialog]').forEach(button => button.addEventListener('click', () => {
    const target = document.getElementById(button.dataset.dialog);
    restoreFocus = button; target.showModal(); updateScroll();
}));
all('[data-close-dialog]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
all('dialog').forEach(target => target.addEventListener('close', () => {
    pending = null; updateScroll(); restoreFocus?.focus();
}));
function confirm(form, submitter, message) {
    pending = { form, submitter }; restoreFocus = submitter;
    one('#confirm-description').textContent = message;
    const summary = one('#confirm-summary'); summary.replaceChildren();
    if (form.hasAttribute('data-summary')) {
        const list = document.createElement('dl');
        all('.field', form).forEach(field => {
            const input = one('input, select, textarea', field);
            if (!input || !input.value) return;
            const term = document.createElement('dt'), value = document.createElement('dd');
            term.textContent = one('label', field).textContent;
            value.textContent = input.tagName === 'SELECT' ? input.selectedOptions[0].textContent : input.value;
            list.append(term, value);
        }); summary.append(list);
    }
    dialog.showModal(); updateScroll();
}
function send(form, submitter) {
    if (form.dataset.sending) return;
    form.dataset.sending = 'true';
    if (submitter?.name) {
        const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = submitter.name; hidden.value = submitter.value; form.append(hidden);
    }
    all('button[type="submit"]', form).forEach(button => { button.disabled = true; });
    if (submitter) submitter.textContent = 'Memproses…';
    form.setAttribute('aria-busy', 'true'); HTMLFormElement.prototype.submit.call(form);
}
one('#confirm-submit')?.addEventListener('click', () => {
    const submission = pending; dialog.close();
    if (submission) send(submission.form, submission.submitter);
});
all('form[data-submit], form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    event.preventDefault(); const submitter = event.submitter, note = one('[name="note"]', form);
    if (form.getAttribute('id') === 'review-form' && submitter?.value === 'revise' && !note.value.trim()) {
        note.setCustomValidity('Tuliskan catatan sebelum meminta revisi.'); note.reportValidity(); note.focus(); return;
    }
    note?.setCustomValidity('');
    if (form.hasAttribute('data-confirm') || (form.hasAttribute('data-confirm-publish') && submitter?.value === 'publish')) {
        confirm(form, submitter, form.dataset.confirm || 'Terbitkan nilai contoh ini? Nilai dan catatan akan terlihat di pratinjau Siswa.');
    } else send(form, submitter);
}));
one('#review-form [name="note"]')?.addEventListener('input', event => event.target.setCustomValidity(''));
const sidebar = one('#sidebar'), trigger = one('#menu-trigger'), overlay = one('.sidebar-overlay');
const mobile = window.matchMedia('(max-width: 1023px)');
function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('open'); overlay.hidden = true; sidebar.removeAttribute('role'); sidebar.removeAttribute('aria-modal'); sidebar.inert = mobile.matches;
    one('.app-main').inert = false; trigger.setAttribute('aria-expanded', 'false'); updateScroll(); trigger.focus();
}
function syncSidebar() {
    if (!sidebar) return;
    if (!mobile.matches && sidebar.classList.contains('open')) closeSidebar();
    sidebar.inert = mobile.matches && !sidebar.classList.contains('open');
}
syncSidebar(); mobile.addEventListener('change', syncSidebar);
trigger?.addEventListener('click', () => {
    sidebar.inert = false; sidebar.classList.add('open'); overlay.hidden = false; sidebar.setAttribute('role', 'dialog'); sidebar.setAttribute('aria-modal', 'true');
    trigger.setAttribute('aria-expanded', 'true'); one('.app-main').inert = true; updateScroll(); one('[data-close-sidebar]', sidebar).focus();
});
all('[data-close-sidebar]').forEach(button => button.addEventListener('click', closeSidebar));
all('nav a', sidebar || document.createElement('div')).forEach(link => link.addEventListener('click', () => { if (mobile.matches) closeSidebar(); }));
document.addEventListener('keydown', event => {
    if (!sidebar?.classList.contains('open')) return;
    if (event.key === 'Escape') { event.preventDefault(); closeSidebar(); }
    if (event.key === 'Tab') {
        const focusable = all('a[href],button:not([disabled])', sidebar).filter(item => item.getClientRects().length);
        const first = focusable[0], last = focusable.at(-1);
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
});
all('input[name="role"]').forEach(input => input.addEventListener('change', () => {
    const link = one('#open-preview'); link.href = `${link.dataset.base}/${input.value}/dashboard`; link.textContent = `Buka pratinjau ${input.dataset.name}`;
}));
const loginMessages = {initial:'Pilih peran untuk membuka pratinjau tanpa kredensial.',role:'Pilih peran sebelum melanjutkan.',empty:'Username dan kata sandi wajib diisi.',invalid:'Kredensial tidak valid. Periksa kembali masukanmu.',mismatch:'Pilihan peran tidak sesuai. Periksa pilihanmu atau hubungi Admin.',inactive:'Akun belum dapat digunakan. Hubungi Admin sekolah.',loading:'Memeriksa akun… (contoh keadaan)',success:'Berhasil masuk. Dashboard sesuai peran akan terbuka (contoh keadaan).',visible:'Kata sandi contoh ditampilkan.',hidden:'Kata sandi contoh disembunyikan.'};
one('#login-state')?.addEventListener('change', event => {
    const state = event.target.value; one('#login-state-message').textContent = loginMessages[state]; const sample = one('#sample-password'); sample.hidden = !['visible','hidden'].includes(state); sample.textContent = state === 'visible' ? 'contoh-ui' : '••••••••';
});
const attendanceStatus = one('#attendance-form [name="status"]');
function updateAttendance() {
    const present = attendanceStatus.value === 'Hadir';
    all('.attendance-time').forEach(wrapper => { wrapper.hidden = !present; const input = one('input',wrapper); input.disabled = !present; input.required = present; });
    one('#attendance-form [name="note"]').required = !present;
}
if (attendanceStatus) { attendanceStatus.addEventListener('change',updateAttendance); updateAttendance(); }
const mentorJSON = one('#mentor-options');
if (mentorJSON) {
    const mentors = JSON.parse(mentorJSON.textContent), place = one('[name="place"]'), industry = one('[name="industry"]'); let previous = industry.value;
    function updateIndustry() {
        industry.replaceChildren(new Option('Pilih pembina industri',''));
        mentors.filter(item => String(item.place) === place.value).forEach(item => industry.add(new Option(item.name,item.id)));
        industry.value = previous; previous = industry.value;
        one('#placement-preview').textContent = place.value ? (industry.options.length > 1 ? 'Pembina industri disaring sesuai tempat PKL. Tinjau ringkasan sebelum konfirmasi.' : 'Belum ada pembina industri untuk tempat ini. Lengkapi Data Pembina terlebih dahulu.') : 'Pilih tempat untuk melihat pembina industri yang sesuai.';
    }
    place.addEventListener('change',updateIndustry); updateIndustry();
}
const mentorType = one('form [name="type"]');
if (mentorType) {
    function updateMentorType() {
        const industrial = mentorType.value === 'Industri';
        ['place','place_id','position','phone'].forEach(name => { const input = one(`[name="${name}"]`); if(input) { input.required = industrial; if(name !== 'phone') { input.closest('.field').hidden = !industrial; input.disabled = !industrial; } } });
        const nip = one('[name="nip"]'); if(nip) { nip.closest('.field').hidden = industrial; nip.disabled = industrial; }
    }
    mentorType.addEventListener('change',updateMentorType); updateMentorType();
}
one('[data-use-location]')?.addEventListener('click', event => {
    const button = event.currentTarget, status = one('[data-use-location-status]'), form = button.closest('form');
    if (!window.isSecureContext || !('geolocation' in navigator)) { status.textContent = 'Lokasi hanya bisa dibaca melalui HTTPS di browser yang mendukung.'; return; }
    button.disabled = true; status.textContent = 'Membaca lokasi…';
    navigator.geolocation.getCurrentPosition(position => {
        one('[name="latitude"]', form).value = position.coords.latitude.toFixed(7);
        one('[name="longitude"]', form).value = position.coords.longitude.toFixed(7);
        status.textContent = `Lokasi terisi (akurasi ±${Math.round(position.coords.accuracy)} m). Simpan untuk menerapkan.`; button.disabled = false;
    }, () => { status.textContent = 'Lokasi gagal dibaca. Izinkan akses lokasi lalu coba lagi.'; button.disabled = false; }, { enableHighAccuracy: true, timeout: 20000 });
});
window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });

/**
 * Animasi masuk halaman portal: banner judul, kartu, baris tabel, dan angka statistik.
 * Halaman absensi memakai animasinya sendiri.
 */
function playPageIntro() {
    const workspace = one('main.workspace');
    if (!workspace || one('[data-attendance]') || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const groups = ['stats', 'content-grid', 'two-column', 'schedule-grid'];
    const cards = all(':scope > *:not(.page-heading):not(.hero):not(.alert)', workspace)
        .flatMap(block => groups.some(name => block.classList.contains(name)) ? [...block.children] : [block]);
    const heading = one('.page-heading, .hero', workspace);
    const timeline = gsap.timeline({ defaults: { ease: 'power3.out', clearProps: 'transform,opacity' } });

    if (heading?.classList.contains('hero')) {
        const title = one('.hero__title', heading);
        title.innerHTML = title.textContent.trim().split(/\s+/).map(word => `<span class="word">${word.replace(/[&<>"]/g, character => `&#${character.charCodeAt(0)};`)}</span>`).join(' ');
        timeline
            .from(heading, { y: 24, opacity: 0, scale: 0.98, duration: 0.7 })
            .from(one('.hero__orbit', heading), { scale: 0.6, opacity: 0, rotation: -40, transformOrigin: '50% 50%', duration: 1.2, ease: 'expo.out' }, '-=0.5')
            .from(one('.hero__eyebrow', heading), { y: 10, opacity: 0, duration: 0.4 }, '-=1')
            .from(all('.word', title), { yPercent: 100, opacity: 0, rotationX: -70, stagger: 0.07, duration: 0.6, ease: 'back.out(1.8)' }, '-=0.85')
            .from(all('.hero__description, .hero__actions > *', heading), { y: 12, opacity: 0, stagger: 0.08, duration: 0.45 }, '-=0.4')
            .from(all('.hero-stat', heading), { y: 24, opacity: 0, scale: 0.95, stagger: 0.08, duration: 0.55, ease: 'back.out(1.6)' }, '-=0.3');

        all('[data-count]', heading).forEach(element => {
            if (element.dataset.count === '') return;
            const counter = { value: 0 };
            gsap.to(counter, { value: Number(element.dataset.count), duration: 1.2, delay: 0.7, ease: 'power2.out', onUpdate: () => { element.textContent = Math.round(counter.value); } });
        });
    } else if (heading) {
        timeline
            .from(heading, { y: 20, opacity: 0, duration: 0.6 })
            .from(one('.page-heading__orbit', heading), { scale: 0.6, opacity: 0, transformOrigin: '50% 50%', duration: 1, ease: 'expo.out' }, '-=0.45')
            .from(all('h1, p, .heading-actions > *', heading), { y: 12, opacity: 0, stagger: 0.06, duration: 0.45 }, '-=0.9');
    }

    if (cards.length) timeline.from(cards, { y: 24, opacity: 0, stagger: 0.07, duration: 0.55 }, heading ? '-=0.35' : 0);

    const rows = all('tbody tr, .review-row', workspace).slice(0, 20);
    if (rows.length) timeline.from(rows, { x: -10, opacity: 0, stagger: 0.03, duration: 0.4 }, '-=0.35');

    all('.stat strong', workspace).forEach(element => {
        const match = element.textContent.trim().match(/^(\d{1,5})(\D.*)?$/);
        if (!match) return;
        const counter = { value: 0 }, suffix = match[2] ?? '';
        gsap.to(counter, { value: Number(match[1]), duration: 1, delay: 0.3, ease: 'power2.out', onUpdate: () => { element.textContent = `${Math.round(counter.value)}${suffix}`; } });
    });
}
playPageIntro();

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Pop-up notifikasi dan menu akun di bilah atas.
 */
const popovers = all('[data-popover]');
function openPopover(wrap) {
    const trigger = one('[data-popover-trigger]', wrap), panel = one('[data-popover-panel]', wrap);
    panel.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
    if (prefersReducedMotion) return;
    gsap.fromTo(panel, { opacity: 0, y: -8, scale: 0.96 }, { opacity: 1, y: 0, scale: 1, duration: 0.28, ease: 'power3.out' });
    gsap.from(all('.notif-item, .popover__menu a, .account-card', panel), { x: 10, opacity: 0, stagger: 0.04, duration: 0.3, delay: 0.05, ease: 'power2.out', clearProps: 'transform,opacity' });
}
function closePopover(wrap, returnFocus = false) {
    const trigger = one('[data-popover-trigger]', wrap), panel = one('[data-popover-panel]', wrap);
    if (panel.hidden) return;
    trigger.setAttribute('aria-expanded', 'false');
    if (returnFocus) trigger.focus();
    if (prefersReducedMotion) { panel.hidden = true; return; }
    gsap.to(panel, { opacity: 0, y: -6, scale: 0.97, duration: 0.16, ease: 'power2.in', onComplete: () => { panel.hidden = true; gsap.set(panel, { clearProps: 'all' }); } });
}
popovers.forEach(wrap => one('[data-popover-trigger]', wrap).addEventListener('click', () => {
    const isOpen = one('[data-popover-trigger]', wrap).getAttribute('aria-expanded') === 'true';
    popovers.filter(other => other !== wrap).forEach(other => closePopover(other));
    isOpen ? closePopover(wrap) : openPopover(wrap);
}));
document.addEventListener('click', event => popovers.filter(wrap => !wrap.contains(event.target)).forEach(wrap => closePopover(wrap)));
document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    popovers.filter(wrap => !one('[data-popover-panel]', wrap).hidden).forEach(wrap => closePopover(wrap, true));
});

/**
 * Sorotan cahaya mengikuti kursor pada banner dan kartu.
 */
document.addEventListener('pointermove', event => {
    let element = event.target instanceof Element ? event.target.closest('[data-spotlight]') : null;
    while (element) {
        const box = element.getBoundingClientRect();
        element.style.setProperty('--mx', `${event.clientX - box.left}px`);
        element.style.setProperty('--my', `${event.clientY - box.top}px`);
        element = element.parentElement?.closest('[data-spotlight]');
    }
}, { passive: true });

if (!prefersReducedMotion) {
    gsap.to(all('[data-orbit-spin]'), { rotation: 360, svgOrigin: '200 200', duration: 16, repeat: -1, ease: 'none' });
    gsap.to(all('[data-orbit-spin-reverse]'), { rotation: -360, svgOrigin: '200 200', duration: 24, repeat: -1, ease: 'none' });
    gsap.to(all('[data-orbit-dash]'), { rotation: 360, svgOrigin: '200 200', duration: 60, repeat: -1, ease: 'none' });
}

all('[data-live-clock]').forEach(clock => {
    const format = new Intl.DateTimeFormat('id-ID', { timeZone: clock.closest('[data-timezone]')?.dataset.timezone, hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
    const tick = () => { clock.textContent = format.format(new Date()).replace('.', ':'); };
    tick();
    setInterval(tick, 15000);
});


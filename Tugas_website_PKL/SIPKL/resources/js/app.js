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
        ['place','position','phone'].forEach(name => { const input = one(`[name="${name}"]`); if(input) { input.required = industrial; if(name !== 'phone') { input.closest('.field').hidden = !industrial; input.disabled = !industrial; } } });
        const nip = one('[name="nip"]'); if(nip) { nip.closest('.field').hidden = industrial; nip.disabled = industrial; }
    }
    mentorType.addEventListener('change',updateMentorType); updateMentorType();
}
window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });


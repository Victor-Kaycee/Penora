/* =============== TAB NAVIGATION =============== */
const navItems = document.querySelectorAll('.nav-item');
const panels   = document.querySelectorAll('.tab-panel');

navItems.forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;

        navItems.forEach(i => {
            i.classList.toggle('active', i === btn);
            i.setAttribute('aria-selected', i === btn ? 'true' : 'false');
        });

        panels.forEach(p => {
            const match = p.id === `tab-${tab}`;
            p.classList.toggle('active', match);
            p.hidden = !match;
        });

        // remember last tab
        try { localStorage.setItem('blogar:settings:tab', tab); } catch (_) {}
    });
});

// restore last tab
try {
    const last = localStorage.getItem('blogar:settings:tab');
    if (last) {
        const target = document.querySelector(`.nav-item[data-tab="${last}"]`);
        if (target) target.click();
    }
} catch (_) {}

/* =============== AVATAR UPLOAD =============== */
const avatarTrigger = document.getElementById('avatarTrigger');
const profileUpload = document.getElementById('profileUpload');
const previewImage  = document.getElementById('previewImage');
const changePhotoBtn = document.getElementById('changePhotoBtn');

function openFilePicker() { profileUpload && profileUpload.click(); }
avatarTrigger && avatarTrigger.addEventListener('click', openFilePicker);
changePhotoBtn && changePhotoBtn.addEventListener('click', openFilePicker);

profileUpload && profileUpload.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    const okTypes = ['image/png', 'image/jpeg', 'image/webp'];
    if (!okTypes.includes(file.type)) {
        alert('Please choose a PNG, JPG, or WEBP image.');
        this.value = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        alert('Image must be smaller than 5MB.');
        this.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = e => { if (previewImage) previewImage.src = e.target.result; };
    reader.readAsDataURL(file);
});

/* =============== BIO COUNTER =============== */
const bio = document.getElementById('bio');
const bioCount = document.getElementById('bioCount');
function updateBioCount() { if (bio && bioCount) bioCount.textContent = bio.value.length; }
bio && bio.addEventListener('input', updateBioCount);
updateBioCount();

/* =============== PASSWORD SHOW / HIDE =============== */
document.querySelectorAll('.pwd-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = document.getElementById(btn.dataset.target);
        if (!target) return;
        const isPwd = target.type === 'password';
        target.type = isPwd ? 'text' : 'password';
        btn.innerHTML = isPwd ? '<i class="ri-eye-off-line"></i>' : '<i class="ri-eye-line"></i>';
        btn.setAttribute('aria-label', isPwd ? 'Hide password' : 'Show password');
    });
});

/* =============== PASSWORD STRENGTH =============== */
const newPwd     = document.getElementById('new_pwd');
const confirmPwd = document.getElementById('confirm_pwd');
const strengthEl = document.getElementById('pwdStrength');
const matchMsg   = document.getElementById('matchMsg');

function scorePassword(pwd) {
    if (!pwd) return { score: 0, label: 'Enter a password' };
    let score = 0;
    if (pwd.length >= 8) score++;
    if (pwd.length >= 12) score++;
    if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) score++;
    if (/\d/.test(pwd) && /[^A-Za-z0-9]/.test(pwd)) score++;
    const labels = ['Very weak', 'Weak', 'Okay', 'Strong', 'Excellent'];
    return { score, label: labels[score] || 'Very weak' };
}

function renderStrength() {
    if (!strengthEl || !newPwd) return;
    const { score, label } = scorePassword(newPwd.value);
    strengthEl.classList.remove('s1', 's2', 's3', 's4');
    if (score > 0) strengthEl.classList.add('s' + score);
    strengthEl.querySelector('.label').textContent = label;
}
function renderMatch() {
    if (!matchMsg || !newPwd || !confirmPwd) return;
    if (!confirmPwd.value) { matchMsg.textContent = ''; matchMsg.className = 'match-msg'; return; }
    if (newPwd.value === confirmPwd.value) {
        matchMsg.textContent = 'Passwords match.'; matchMsg.className = 'match-msg ok';
    } else {
        matchMsg.textContent = 'Passwords do not match.'; matchMsg.className = 'match-msg bad';
    }
}
newPwd     && newPwd.addEventListener('input', () => { renderStrength(); renderMatch(); });
confirmPwd && confirmPwd.addEventListener('input', renderMatch);

/* =============== PASSWORD FORM CLIENT VALIDATION =============== */
const passwordForm = document.getElementById('passwordForm');
passwordForm && passwordForm.addEventListener('submit', e => {
    const cur = document.getElementById('current_pwd').value;
    const nw  = newPwd.value;
    const cf  = confirmPwd.value;

    if (!cur || !nw || !cf) { e.preventDefault(); alert('All password fields are required.'); return; }
    const strong = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,128}$/.test(nw);
    if (!strong) { e.preventDefault(); alert('Password must be 8+ chars with upper, lower, number, and symbol.'); return; }
    if (nw !== cf) { e.preventDefault(); alert('New passwords do not match.'); return; }
});

/* =============== DELETE CONFIRMATION =============== */
const deleteInput = document.getElementById('confirm_delete');
const deleteBtn   = document.getElementById('deleteBtn');
deleteInput && deleteInput.addEventListener('input', () => {
    deleteBtn.disabled = deleteInput.value.trim() !== 'DELETE';
});

/* =============== APPEARANCE =============== */
document.querySelectorAll('.theme-card').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
        try { localStorage.setItem('blogar:theme', card.dataset.theme); } catch (_) {}
    });
});
document.querySelectorAll('.accent').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.accent').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        try { localStorage.setItem('blogar:accent', btn.dataset.accent); } catch (_) {}
    });
});

/* =============== ALERTS DISMISS =============== */
document.querySelectorAll('.alert-close').forEach(btn => {
    btn.addEventListener('click', () => btn.parentElement.remove());
});

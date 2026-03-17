/* assets/js/app.js — Version PHP (pas de localStorage) */

/* ── Toggle mot de passe ── */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.toggle-pwd').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.previousElementSibling;
            const isText = input.type === 'text';
            input.type = isText ? 'password' : 'text';
            btn.querySelector('i').className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    });

    // Fermer modals en cliquant le backdrop
    document.querySelectorAll('.modal-backdrop').forEach(bd => {
        bd.addEventListener('click', e => {
            if (e.target === bd) bd.classList.remove('open');
        });
    });
});

/* ── Toast notifications ── */
function showToast(message, type = 'success') {
    let toast = document.querySelector('.toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'toast';
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.className = `toast ${type}`;
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => toast.classList.remove('show'), 3500);
}

/* ── Modals ── */
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

/* ── Indicateur force mot de passe ── */
function initPasswordStrength(inputId, fillId, labelId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('input', () => {
        const v = input.value;
        let score = 0;
        if (v.length >= 8)          score++;
        if (/[A-Z]/.test(v))        score++;
        if (/[0-9]/.test(v))        score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;
        const levels = [
            {w:'0%',   bg:'transparent', lbl:''},
            {w:'25%',  bg:'#e74c3c',     lbl:'Faible'},
            {w:'50%',  bg:'#e67e22',     lbl:'Moyen'},
            {w:'75%',  bg:'#2ecc71',     lbl:'Bon'},
            {w:'100%', bg:'#27ae60',     lbl:'Excellent'},
        ];
        const lvl = levels[score];
        const fill  = document.getElementById(fillId);
        const label = document.getElementById(labelId);
        if (fill)  { fill.style.width = lvl.w; fill.style.background = lvl.bg; }
        if (label)   label.textContent = lvl.lbl;
    });
}

/* ── Requête AJAX vers l'API PHP ── */
async function apiFetch(endpoint, options = {}) {
    const defaults = {
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    };
    const config = { ...defaults, ...options };
    if (config.headers && options.headers) {
        config.headers = { ...defaults.headers, ...options.headers };
    }
    const res = await fetch((typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/api/' + endpoint, config);
    if (!res.ok) throw new Error('Erreur ' + res.status);
    return res.json();
}

/* ── Confirmer suppression ── */
function confirmerSuppression(msg, callback) {
    if (confirm(msg || 'Supprimer cet élément ?')) callback();
}

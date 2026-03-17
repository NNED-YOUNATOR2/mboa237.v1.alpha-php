<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['apprenant']);
$user = currentUser();

$coursId = (int)($_GET['id'] ?? 0);
$cours   = $coursId ? getCoursById($coursId) : null;

if (!$cours || $cours['statut'] !== 'publie') {
    setFlash('error', 'Leçon introuvable.');
    redirect('dashboard.php');
}

$lecons = getLeconsByCours($coursId);

// Traitement AJAX : sauvegarder progression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_progression'])) {
    $score = min(100, max(0, (int)($_POST['score'] ?? 0)));
    updateProgression($user['id'], $coursId, $score);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'score' => $score]);
    exit;
}

$pageTitle = e($cours['titre']);
$rootPath  = '../';
$extraCss  = 'lecon.css';
include __DIR__ . '/../includes/header.php';

// Passer les leçons en JSON pour le JS
$leconsJson = json_encode($lecons, JSON_UNESCAPED_UNICODE);
$coursJson  = json_encode([
    'id'          => $cours['id'],
    'titre'       => $cours['titre'],
    'langue'      => $cours['langue'],
    'niveau'      => $cours['niveau'],
    'description' => $cours['description'],
], JSON_UNESCAPED_UNICODE);
?>

<!-- Écrans -->
<div id="screen-loading" class="screen active">
    <div class="loading-wrap">
        <div class="loading-spinner"></div>
        <p>Chargement de la leçon...</p>
    </div>
</div>

<div id="screen-intro" class="screen">
    <div class="intro-wrap">
        <div class="intro-icon"><i class="fas fa-book-open"></i></div>
        <h1 id="intro-titre"><?= e($cours['titre']) ?></h1>
        <p class="intro-langue"><?= e($cours['langue']) ?> · Niveau <?= (int)$cours['niveau'] ?></p>
        <p class="intro-desc"><?= e($cours['description']) ?></p>
        <div class="intro-meta">
            <span><i class="fas fa-list"></i> <?= count($lecons) ?> exercices</span>
            <span><i class="fas fa-clock"></i> ~5 min</span>
        </div>
        <button class="btn btn-primary" id="btn-start"><i class="fas fa-play"></i> Commencer</button>
    </div>
</div>

<div id="screen-exercice" class="screen">
    <div class="container exercice-wrap">
        <div class="lesson-progress-header" style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
            <div class="progress-bg" style="flex:1;">
                <div class="progress-fill" id="header-progress" style="width:0%"></div>
            </div>
            <span id="header-step" style="font-size:.85rem;color:#7b6e64;white-space:nowrap;">1 / <?= count($lecons) ?></span>
        </div>
        <div class="exercice-type" id="ex-type-label">Vocabulaire</div>
        <div id="exercice-content"></div>
    </div>
</div>

<div id="screen-feedback" class="screen">
    <div class="feedback-wrap" id="feedback-wrap">
        <div class="feedback-icon" id="feedback-icon"></div>
        <h2 id="feedback-titre"></h2>
        <p id="feedback-message"></p>
        <div class="feedback-answer" id="feedback-answer"></div>
        <button class="btn btn-primary" id="btn-next">Continuer</button>
    </div>
</div>

<div id="screen-fin" class="screen">
    <div class="fin-wrap">
        <div class="fin-medal">🏆</div>
        <h1>Leçon terminée !</h1>
        <p id="fin-score"></p>
        <div class="fin-stats">
            <div class="fin-stat"><div class="fin-val" id="fin-correct">0</div><div class="fin-lbl">Bonnes réponses</div></div>
            <div class="fin-stat"><div class="fin-val" id="fin-total">0</div><div class="fin-lbl">Exercices</div></div>
            <div class="fin-stat"><div class="fin-val" id="fin-pct">0%</div><div class="fin-lbl">Score</div></div>
        </div>
        <div class="fin-btns">
            <a href="dashboard.php" class="btn btn-secondary">Tableau de bord</a>
            <a href="carte-sentier.php" class="btn btn-primary" id="btn-suite">Leçon suivante</a>
        </div>
    </div>
</div>

<script>
const LECONS  = <?= $leconsJson ?>;
const COURS   = <?= $coursJson ?>;
const SAVE_URL = 'lecon.php?id=<?= (int)$coursId ?>';

let indexEx = 0, score = 0, reponseChoisie = null, modeVerif = false;

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => showScreen('intro'), 600);
    document.getElementById('btn-start').addEventListener('click', () => {
        indexEx = 0; score = 0;
        afficherExercice();
    });
    document.getElementById('btn-next').addEventListener('click', () => {
        indexEx++;
        afficherExercice();
    });
});

function afficherExercice() {
    if (indexEx >= LECONS.length) { afficherFin(); return; }
    const ex = LECONS[indexEx];
    const c  = ex.contenu || {};
    modeVerif = false; reponseChoisie = null;

    const pct = Math.round((indexEx / LECONS.length) * 100);
    document.getElementById('header-progress').style.width = pct + '%';
    document.getElementById('header-step').textContent = (indexEx+1) + ' / ' + LECONS.length;

    const labels = {vocabulaire:'Vocabulaire', qcm:'Choix multiple', saisie:'Écriture', vf:'Vrai / Faux'};
    document.getElementById('ex-type-label').textContent = labels[ex.type] || ex.type;

    const content = document.getElementById('exercice-content');
    if      (ex.type === 'vocabulaire') content.innerHTML = renderVocab(c);
    else if (ex.type === 'qcm')        content.innerHTML = renderQCM(c);
    else if (ex.type === 'saisie')     content.innerHTML = renderSaisie(c);
    else if (ex.type === 'vf')         content.innerHTML = renderVF(c);

    bindEvents(ex);
    showScreen('exercice');
}

function renderVocab(c) {
    return `<div class="vocab-card">
        <div class="vocab-mot">${c.mot||''}</div>
        <div class="vocab-phonetique">${c.phonetique||''}</div>
        <div class="vocab-traduction">${c.traduction||''}</div>
        <button class="btn-audio" id="btn-audio"><i class="fas fa-volume-up"></i> Écouter</button>
    </div>
    <button class="btn btn-primary btn-valider" id="btn-valider">J'ai compris <i class="fas fa-arrow-right"></i></button>`;
}

function renderQCM(c) {
    const lettres = ['A','B','C','D'];
    const opts = (c.options||[]).map((o,i) =>
        `<button class="qcm-option" data-index="${i}">
            <span class="opt-letter">${lettres[i]}</span>${o}
        </button>`).join('');
    return `<div class="qcm-question">${c.question||''}</div>
    <div class="qcm-options">${opts}</div>
    <button class="btn btn-primary btn-valider" id="btn-valider" disabled>Valider</button>`;
}

function renderSaisie(c) {
    return `<div class="saisie-question">${c.question||''}</div>
    ${c.indice ? `<p style="text-align:center;color:#7b6e64;font-size:.9rem;margin-bottom:.8rem;">Indice : <strong>${c.indice}</strong></p>` : ''}
    <input type="text" class="saisie-input" id="saisie-input" placeholder="Votre réponse..." autocomplete="off" autocorrect="off">
    <button class="btn btn-primary btn-valider" id="btn-valider" style="width:100%;justify-content:center;margin-top:.8rem;" disabled>Valider</button>`;
}

function renderVF(c) {
    return `<div class="qcm-question">Cette affirmation est-elle vraie ou fausse ?</div>
    <div style="background:#f3efe9;border-radius:10px;padding:1rem 1.2rem;font-family:'Playfair Display',serif;font-size:1.2rem;text-align:center;color:var(--primaire);margin-bottom:1.5rem;">${c.affirmation||''}</div>
    <div style="display:flex;gap:1rem;justify-content:center;">
        <button class="eq-vf-btn vrai qcm-option" data-vf="true" style="flex:1;max-width:160px;padding:.85rem;border-radius:12px;border:2px solid var(--bordure);background:white;font-size:1rem;cursor:pointer;font-family:'Roboto',sans-serif;transition:var(--tr);">✅ Vrai</button>
        <button class="eq-vf-btn faux qcm-option" data-vf="false" style="flex:1;max-width:160px;padding:.85rem;border-radius:12px;border:2px solid var(--bordure);background:white;font-size:1rem;cursor:pointer;font-family:'Roboto',sans-serif;transition:var(--tr);">❌ Faux</button>
    </div>
    <button class="btn btn-primary btn-valider" id="btn-valider" style="width:100%;justify-content:center;margin-top:1rem;" disabled>Valider</button>`;
}

function bindEvents(ex) {
    const c = ex.contenu || {};

    if (ex.type === 'vocabulaire') {
        document.getElementById('btn-audio')?.addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-volume-up fa-beat"></i> Lecture...';
            setTimeout(() => this.innerHTML = '<i class="fas fa-volume-up"></i> Écouter', 1200);
        });
        document.getElementById('btn-valider')?.addEventListener('click', () => {
            score++; indexEx++; afficherExercice();
        });
    }

    if (ex.type === 'qcm') {
        document.querySelectorAll('.qcm-option').forEach(btn => {
            btn.addEventListener('click', () => {
                if (modeVerif) return;
                document.querySelectorAll('.qcm-option').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
                reponseChoisie = parseInt(btn.dataset.index);
                document.getElementById('btn-valider').disabled = false;
            });
        });
        document.getElementById('btn-valider')?.addEventListener('click', () => validerQCM(c));
    }

    if (ex.type === 'saisie') {
        const input = document.getElementById('saisie-input');
        input?.addEventListener('input', () => {
            document.getElementById('btn-valider').disabled = !input.value.trim();
        });
        input?.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !document.getElementById('btn-valider').disabled)
                validerSaisie(c);
        });
        document.getElementById('btn-valider')?.addEventListener('click', () => validerSaisie(c));
        setTimeout(() => input?.focus(), 100);
    }

    if (ex.type === 'vf') {
        document.querySelectorAll('.eq-vf-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (modeVerif) return;
                document.querySelectorAll('.eq-vf-btn').forEach(b => {
                    b.style.borderColor = 'var(--bordure)'; b.style.background = 'white';
                });
                btn.style.borderColor = 'var(--primaire)'; btn.style.background = '#fdf8f3';
                reponseChoisie = btn.dataset.vf === 'true';
                document.getElementById('btn-valider').disabled = false;
            });
        });
        document.getElementById('btn-valider')?.addEventListener('click', () => validerVF(c));
    }
}

function validerQCM(c) {
    if (reponseChoisie === null || modeVerif) return;
    modeVerif = true;
    const correct = reponseChoisie === c.reponse;
    if (correct) score++;
    document.querySelectorAll('.qcm-option').forEach((btn, i) => {
        if (i === c.reponse) btn.classList.add('correct');
        else if (i === reponseChoisie && !correct) btn.classList.add('wrong');
    });
    document.getElementById('btn-valider').disabled = true;
    setTimeout(() => afficherFeedback(correct, (c.options||[])[c.reponse] || ''), 700);
}

function validerSaisie(c) {
    if (modeVerif) return;
    modeVerif = true;
    const input   = document.getElementById('saisie-input');
    const valeur  = input.value.trim().toLowerCase();
    const correct = valeur === (c.reponse||'').toLowerCase();
    if (correct) score++;
    input.classList.add(correct ? 'correct' : 'wrong');
    input.disabled = true;
    document.getElementById('btn-valider').disabled = true;
    setTimeout(() => afficherFeedback(correct, c.reponse || ''), 500);
}

function validerVF(c) {
    if (reponseChoisie === null || modeVerif) return;
    modeVerif = true;
    const reponseAttendue = c.reponse === true || c.reponse === 'true' || c.reponse === 1;
    const correct = reponseChoisie === reponseAttendue;
    if (correct) score++;
    document.querySelectorAll('.eq-vf-btn').forEach(btn => {
        const bVal = btn.dataset.vf === 'true';
        if (bVal === reponseAttendue) btn.style.borderColor = '#4caf50', btn.style.background = '#e8f5e9';
        else if (bVal === reponseChoisie && !correct) btn.style.borderColor = '#f44336', btn.style.background = '#ffebee';
    });
    document.getElementById('btn-valider').disabled = true;
    setTimeout(() => afficherFeedback(correct, reponseAttendue ? 'Vrai ✅' : 'Faux ❌'), 700);
}

function afficherFeedback(correct, bonneReponse) {
    const wrap = document.getElementById('feedback-wrap');
    wrap.className = 'feedback-wrap ' + (correct ? 'correct' : 'wrong');
    document.getElementById('feedback-icon').textContent = correct ? '✅' : '❌';
    document.getElementById('feedback-titre').textContent = correct ? 'Bonne réponse !' : 'Pas tout à fait...';
    document.getElementById('feedback-message').textContent = correct
        ? 'Excellent ! Continuez comme ça.'
        : 'Ne vous découragez pas, c\'est en pratiquant qu\'on apprend.';
    const ansEl = document.getElementById('feedback-answer');
    if (!correct && bonneReponse) {
        ansEl.textContent = 'Bonne réponse : ' + bonneReponse;
        ansEl.classList.add('visible');
    } else ansEl.classList.remove('visible');
    showScreen('feedback');
}

function afficherFin() {
    const total = LECONS.length;
    const pct   = Math.round((score / total) * 100);
    document.getElementById('fin-correct').textContent = score;
    document.getElementById('fin-total').textContent   = total;
    document.getElementById('fin-pct').textContent     = pct + '%';
    document.getElementById('fin-score').textContent   =
        pct >= 80 ? '🌟 Excellent travail !' : pct >= 50 ? '👍 Bon effort !' : '💪 Continuez !';

    // Lien célébration si 100%
    if (pct === 100) {
        document.getElementById('btn-suite').href =
            `celebration.php?niveau=${COURS.niveau}&langue=${encodeURIComponent(COURS.langue)}&score=${pct}&lecons=1`;
    }

    document.getElementById('header-progress').style.width = '100%';

    // Sauvegarder en BDD via AJAX
    const form = new FormData();
    form.append('ajax_progression', '1');
    form.append('score', pct);
    fetch(SAVE_URL, { method:'POST', body: form, credentials:'same-origin' });

    showScreen('fin');
}

function showScreen(name) {
    document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
    document.getElementById('screen-' + name).classList.add('active');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

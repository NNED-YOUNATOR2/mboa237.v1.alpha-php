<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['apprenant']);
$user = currentUser();

// Gestion AJAX : enregistrer la série
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_streak'])) {
    $newStreak = updateStreak($user['id']);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'streak' => $newStreak]);
    exit;
}

// Vérifier si déjà fait aujourd'hui (streak date)
$pdo       = getPDO();
$stmt      = $pdo->prepare('SELECT last_eq_date, streak FROM utilisateurs WHERE id = ?');
$stmt->execute([$user['id']]);
$row       = $stmt->fetch();
$dejaFait  = ($row['last_eq_date'] === date('Y-m-d'));
$streak    = (int)$row['streak'];

$pageTitle = 'Exercice du jour';
$rootPath  = '../';
$extraCss  = 'lecon.css';
include __DIR__ . '/../includes/header.php';
?>

<!-- Écran intro -->
<div id="screen-intro" class="screen active">
    <div class="intro-wrap">
        <div class="intro-icon" style="font-size:3.5rem;">🔥</div>
        <h1>Exercice du jour</h1>
        <p class="intro-langue"><?= date('l d F Y') ?></p>
        <p class="intro-desc">5 exercices rapides pour maintenir votre niveau et garder votre série active.</p>

        <div class="intro-meta">
            <span><i class="fas fa-list"></i> 5 exercices</span>
            <span><i class="fas fa-clock"></i> ~3 min</span>
            <span><i class="fas fa-fire" style="color:#e65100;"></i>
                <strong><?= $streak ?></strong> jour<?= $streak>1?'s':'' ?></span>
        </div>

        <?php if ($dejaFait): ?>
            <div style="background:#e8f5e9;border:1px solid #c8e6c9;border-radius:10px;padding:1rem;margin:1.2rem 0;color:#2e7d32;font-size:.92rem;">
                <i class="fas fa-check-circle"></i> Vous avez déjà fait l'exercice aujourd'hui ! Revenez demain.
            </div>
            <a href="dashboard.php" class="btn btn-secondary">Retour au tableau de bord</a>
        <?php else: ?>
            <button class="btn btn-primary btn-start-eq" id="btn-start-eq" style="padding:.9rem 2.5rem;font-size:1.05rem;">
                <i class="fas fa-play"></i> Commencer
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Écran exercice -->
<div id="screen-exercice" class="screen">
    <div class="container exercice-wrap">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
            <div class="eq-dots" id="eq-dots" style="display:flex;gap:.5rem;"></div>
            <span id="eq-step" style="font-size:.85rem;color:#7b6e64;">1 / 5</span>
            <div id="timer-display" style="display:inline-flex;align-items:center;gap:.4rem;background:#f3efe9;border-radius:20px;padding:.3rem .9rem;font-size:.9rem;font-weight:600;color:var(--primaire);">
                <i class="fas fa-clock"></i> <span id="timer">05:00</span>
            </div>
        </div>
        <div class="exercice-type" id="eq-type-label">Vocabulaire</div>
        <div id="eq-content"></div>
        <button class="btn btn-primary" id="eq-btn-valider"
                style="width:100%;justify-content:center;padding:.9rem;margin-top:1rem;" disabled>
            Valider
        </button>
    </div>
</div>

<!-- Écran résultat -->
<div id="screen-resultat" class="screen">
    <div class="fin-wrap">
        <div class="fin-medal" id="res-emoji">⭐</div>
        <h1 id="res-titre">Exercice terminé !</h1>
        <p id="res-msg" style="color:#5f554b;margin-bottom:1.8rem;"></p>
        <div class="fin-stats">
            <div class="fin-stat">
                <div class="fin-val" id="res-score">0/5</div>
                <div class="fin-lbl">Score</div>
            </div>
            <div class="fin-stat">
                <div class="fin-val" id="res-streak" style="color:#e65100;">0🔥</div>
                <div class="fin-lbl">Série</div>
            </div>
        </div>
        <div class="fin-btns">
            <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-home"></i> Tableau de bord</a>
            <a href="carte-sentier.php" class="btn btn-secondary"><i class="fas fa-map-marked-alt"></i> Mon parcours</a>
        </div>
    </div>
</div>

<script>
const EXERCICES_JOUR = [
    [{type:'qcm',question:'Comment dit-on Bonjour en Ewondo ?',options:['Akwaaba','Mbolo','Jambo','Sannu'],reponse:1},
     {type:'vf', affirmation:'Akiba signifie Merci en Ewondo',reponse:true},
     {type:'saisie',question:'Écrivez Bonjour en Ewondo',reponse:'mbolo'},
     {type:'qcm',question:'Comment dit-on 1 en Duala ?',options:['Beba','Loba','Wala','Nai'],reponse:2},
     {type:'vf', affirmation:'Baaba signifie Mère en Fulfulde',reponse:false}],
    [{type:'qcm',question:'Comment dit-on 2 en Duala ?',options:['Wala','Beba','Loba','Nai'],reponse:1},
     {type:'saisie',question:'Écrivez Merci en Ewondo',reponse:'akiba'},
     {type:'vf', affirmation:'Nene signifie Mère en Fulfulde',reponse:true},
     {type:'qcm',question:'Que signifie A yé ? en Ewondo ?',options:['Bonjour','Au revoir','Comment vas-tu ?','Merci'],reponse:2},
     {type:'saisie',question:'Écrivez Père en Fulfulde',reponse:'baaba'}],
    [{type:'vf', affirmation:'Loba signifie Trois en Duala',reponse:true},
     {type:'qcm',question:'Quelle langue est parlée dans le Nord Cameroun ?',options:['Ewondo','Duala','Fulfulde','Basaa'],reponse:2},
     {type:'saisie',question:'Écrivez Comment vas-tu en Ewondo',reponse:'a yé ?'},
     {type:'vf', affirmation:'Mbolo signifie Au revoir en Ewondo',reponse:false},
     {type:'qcm',question:'Comment dit-on 3 en Duala ?',options:['Wala','Beba','Loba','Nai'],reponse:2}],
];

const jour = new Date().getDay() % EXERCICES_JOUR.length;
const EXERCICES = EXERCICES_JOUR[jour];

let indexEx = 0, score = 0, rep = null, modeVerif = false;
let timerInterval, tempsRestant = 300;

<?php if (!$dejaFait): ?>
document.getElementById('btn-start-eq').addEventListener('click', demarrer);
<?php endif; ?>

function demarrer() {
    // Dots
    const dots = document.getElementById('eq-dots');
    dots.innerHTML = EXERCICES.map((_,i) => `<div class="eq-dot" id="dot-${i}" style="width:32px;height:8px;border-radius:4px;background:var(--bordure);transition:.3s;"></div>`).join('');
    afficherExercice();
    demarrerTimer();
    showScreen('exercice');
}

function demarrerTimer() {
    tempsRestant = 300;
    majTimer();
    timerInterval = setInterval(() => {
        tempsRestant--;
        majTimer();
        if (tempsRestant <= 0) { clearInterval(timerInterval); afficherResultat(); }
    }, 1000);
}

function majTimer() {
    const m = Math.floor(tempsRestant/60), s = tempsRestant%60;
    document.getElementById('timer').textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    const td = document.getElementById('timer-display');
    td.style.background = tempsRestant <= 30 ? '#ffebee' : '#f3efe9';
    td.style.color      = tempsRestant <= 30 ? '#c62828' : 'var(--primaire)';
}

function afficherExercice() {
    if (indexEx >= EXERCICES.length) { afficherResultat(); return; }
    const ex = EXERCICES[indexEx];
    rep = null; modeVerif = false;

    // Dots
    document.querySelectorAll('.eq-dot').forEach((d,i) => {
        d.style.background = i < indexEx ? 'var(--secondaire)' : i === indexEx ? 'var(--primaire)' : 'var(--bordure)';
    });
    document.getElementById('eq-step').textContent = (indexEx+1) + ' / ' + EXERCICES.length;

    const labels = {qcm:'Choix multiple', saisie:'Écriture', vf:'Vrai / Faux'};
    document.getElementById('eq-type-label').textContent = labels[ex.type] || ex.type;

    const content = document.getElementById('eq-content');
    if (ex.type === 'qcm') {
        const l = ['A','B','C','D'];
        content.innerHTML = `<div class="qcm-question">${ex.question}</div>
        <div class="qcm-options">${ex.options.map((o,i)=>`
            <button class="qcm-option" data-i="${i}"><span class="opt-letter">${l[i]}</span>${o}</button>`).join('')}</div>`;
        document.querySelectorAll('.qcm-option').forEach(btn => {
            btn.addEventListener('click', () => {
                if (modeVerif) return;
                document.querySelectorAll('.qcm-option').forEach(b=>b.classList.remove('selected'));
                btn.classList.add('selected'); rep = parseInt(btn.dataset.i);
                document.getElementById('eq-btn-valider').disabled = false;
            });
        });
    } else if (ex.type === 'saisie') {
        content.innerHTML = `<div class="qcm-question">${ex.question}</div>
        <input type="text" class="saisie-input" id="eq-input" placeholder="Votre réponse..." autocomplete="off">`;
        const inp = document.getElementById('eq-input');
        inp.addEventListener('input', () => document.getElementById('eq-btn-valider').disabled = !inp.value.trim());
        inp.addEventListener('keydown', e => { if(e.key==='Enter' && !document.getElementById('eq-btn-valider').disabled) valider(ex); });
        setTimeout(() => inp.focus(), 50);
    } else if (ex.type === 'vf') {
        content.innerHTML = `<div class="qcm-question">Cette affirmation est-elle vraie ou fausse ?</div>
        <div style="background:#f3efe9;border-radius:10px;padding:1rem;font-family:'Playfair Display',serif;font-size:1.1rem;text-align:center;color:var(--primaire);margin-bottom:1.2rem;">${ex.affirmation}</div>
        <div style="display:flex;gap:1rem;justify-content:center;">
            <button class="vf-btn" data-v="true" style="flex:1;max-width:140px;padding:.8rem;border-radius:10px;border:2px solid var(--bordure);background:white;font-size:1rem;cursor:pointer;font-family:'Roboto',sans-serif;">✅ Vrai</button>
            <button class="vf-btn" data-v="false" style="flex:1;max-width:140px;padding:.8rem;border-radius:10px;border:2px solid var(--bordure);background:white;font-size:1rem;cursor:pointer;font-family:'Roboto',sans-serif;">❌ Faux</button>
        </div>`;
        document.querySelectorAll('.vf-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (modeVerif) return;
                document.querySelectorAll('.vf-btn').forEach(b=>{b.style.borderColor='var(--bordure)';b.style.background='white';});
                btn.style.borderColor='var(--primaire)'; btn.style.background='#fdf8f3';
                rep = (btn.dataset.v === 'true');
                document.getElementById('eq-btn-valider').disabled = false;
            });
        });
    }

    document.getElementById('eq-btn-valider').disabled = (ex.type !== 'vocabulaire');
    document.getElementById('eq-btn-valider').onclick = () => valider(ex);
}

function valider(ex) {
    if (modeVerif) return;
    modeVerif = true;
    let correct = false;
    const btn = document.getElementById('eq-btn-valider');

    if (ex.type === 'qcm') {
        correct = rep === ex.reponse;
        document.querySelectorAll('.qcm-option').forEach((b,i) => {
            if (i === ex.reponse) b.classList.add('correct');
            else if (i === rep && !correct) b.classList.add('wrong');
        });
    } else if (ex.type === 'saisie') {
        const v = document.getElementById('eq-input').value.trim().toLowerCase();
        correct = v === (ex.reponse||'').toLowerCase();
        document.getElementById('eq-input').classList.add(correct?'correct':'wrong');
        document.getElementById('eq-input').disabled = true;
    } else if (ex.type === 'vf') {
        const attendu = ex.reponse === true || ex.reponse === 'true';
        correct = rep === attendu;
        document.querySelectorAll('.vf-btn').forEach(b => {
            const bv = b.dataset.v === 'true';
            if (bv === attendu) { b.style.borderColor='#4caf50'; b.style.background='#e8f5e9'; }
            else if (bv === rep && !correct) { b.style.borderColor='#f44336'; b.style.background='#ffebee'; }
        });
    }

    if (correct) score++;
    btn.textContent = correct ? '✓ Bonne réponse !' : '✗ Mauvaise réponse';
    btn.style.background = correct ? '#4caf50' : '#f44336';

    setTimeout(() => {
        btn.style.background = ''; btn.textContent = 'Valider';
        indexEx++; afficherExercice();
    }, 900);
}

async function afficherResultat() {
    clearInterval(timerInterval);
    const pct = Math.round((score / EXERCICES.length) * 100);
    document.getElementById('res-emoji').textContent = pct===100?'🌟':pct>=80?'⭐':pct>=60?'👍':'💪';
    document.getElementById('res-titre').textContent = pct>=80 ? 'Excellent !' : 'Exercice terminé !';
    document.getElementById('res-msg').textContent   = pct>=80 ? 'Vous maîtrisez les bases !' : 'Continuez à pratiquer chaque jour.';
    document.getElementById('res-score').textContent = score + '/' + EXERCICES.length;

    // Sauvegarder la série en BD
    const fd = new FormData();
    fd.append('ajax_streak', '1');
    const res = await fetch('exercice-quotidien.php', {method:'POST', body:fd, credentials:'same-origin'});
    const data = await res.json();
    document.getElementById('res-streak').textContent = (data.streak||0) + '🔥';

    showScreen('resultat');
}

function showScreen(n) {
    document.querySelectorAll('.screen').forEach(s=>s.classList.remove('active'));
    document.getElementById('screen-'+n).classList.add('active');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

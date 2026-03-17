<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['apprenant']);
$user = currentUser();

$niveau = (int)($_GET['niveau'] ?? 1);
$langue = htmlspecialchars($_GET['langue'] ?? $user['langue'] ?? 'Ewondo');
$score  = min(100, max(0, (int)($_GET['score']  ?? 85)));
$lecons = max(1, (int)($_GET['lecons'] ?? 1));

$badges = [
    1 => ['icone'=>'🌱','label'=>'Niveau 1','badge'=>'Explorateur des langues'],
    2 => ['icone'=>'🔥','label'=>'Niveau 2','badge'=>'Maître des expressions'],
    3 => ['icone'=>'👑','label'=>'Niveau 3','badge'=>'Gardien de la tradition'],
];
$badge = $badges[$niveau] ?? $badges[1];
$motsByNiveau = [1=>24, 2=>48, 3=>80];

$pageTitle = 'Félicitations !';
$rootPath  = '../';
$extraCss  = 'celebration.css';
include __DIR__ . '/../includes/header.php';
?>

<div class="confettis" id="confettis"></div>

<div class="celebration-wrap">
    <div class="badge-wrap">
        <div class="badge-rayon"></div>
        <div class="badge-cercle">
            <div class="badge-inner">
                <div class="badge-icone"><?= $badge['icone'] ?></div>
                <div class="badge-label"><?= e($badge['label']) ?></div>
            </div>
        </div>
    </div>

    <div class="cel-texte">
        <h1>Félicitations !</h1>
        <p class="cel-sous">Vous avez terminé le niveau <?= (int)$niveau ?> !</p>
        <p class="cel-langue">Langue : <?= e($langue) ?></p>
    </div>

    <div class="cel-stats">
        <div class="cel-stat">
            <div class="cel-val" id="stat-lecons">0</div>
            <div class="cel-lbl">Leçons</div>
        </div>
        <div class="cel-stat">
            <div class="cel-val" id="stat-score">0%</div>
            <div class="cel-lbl">Score moyen</div>
        </div>
        <div class="cel-stat">
            <div class="cel-val" id="stat-mots">0</div>
            <div class="cel-lbl">Mots appris</div>
        </div>
    </div>

    <div class="nouveau-badge">
        <div class="nb-icone"><i class="fas fa-star"></i></div>
        <div>
            <div class="nb-titre">Badge débloqué !</div>
            <div class="nb-desc"><?= e($badge['badge']) ?></div>
        </div>
    </div>

    <div class="cel-btns">
        <a href="carte-sentier.php" class="btn btn-primary">
            <i class="fas fa-map-marked-alt"></i> Niveau suivant
        </a>
        <a href="dashboard.php" class="btn btn-secondary">Tableau de bord</a>
    </div>

    <button class="btn-partager" onclick="partager()">
        <i class="fas fa-share-alt"></i> Partager ma progression
    </button>
</div>

<script>
const LECONS_VAL = <?= (int)$lecons ?>;
const SCORE_VAL  = <?= (int)$score ?>;
const MOTS_VAL   = <?= (int)($motsByNiveau[$niveau] ?? 24) ?>;
const LANGUE     = '<?= e($langue) ?>';

document.addEventListener('DOMContentLoaded', () => {
    animerCompteur('stat-lecons', LECONS_VAL, '');
    animerCompteur('stat-score',  SCORE_VAL,  '%');
    animerCompteur('stat-mots',   MOTS_VAL,   '');
    lancerConfettis();
});

function animerCompteur(id, cible, suf) {
    const el = document.getElementById(id);
    const dur = 1200, fps = 60;
    const pas = cible / (dur / (1000/fps));
    let val = 0;
    const t = setInterval(() => {
        val = Math.min(val+pas, cible);
        el.textContent = Math.round(val) + suf;
        if (val >= cible) clearInterval(t);
    }, 1000/fps);
}

function lancerConfettis() {
    const container = document.getElementById('confettis');
    const couleurs  = ['#8b5a2b','#4a6b3f','#d9b382','#e8f5e9','#fff9e6','#fce4ec'];
    for (let i = 0; i < 80; i++) {
        const el = document.createElement('div');
        el.className = 'confetti-piece';
        const taille = 6 + Math.random()*10;
        el.style.cssText = `left:${Math.random()*100}%;width:${taille}px;height:${taille}px;background:${couleurs[Math.floor(Math.random()*couleurs.length)]};--dur:${2.5+Math.random()*2}s;--delay:${Math.random()*1.5}s;${Math.random()>.5?'border-radius:50%;':''}`;
        container.appendChild(el);
    }
    setTimeout(() => container.innerHTML = '', 5000);
}

function partager() {
    const texte = `Je viens de terminer un niveau en ${LANGUE} sur Mboa237 ! 🎉 #Mboa237`;
    if (navigator.share) navigator.share({title:'Mboa237', text:texte}).catch(()=>{});
    else if (navigator.clipboard) navigator.clipboard.writeText(texte)
        .then(() => showToast('Message copié !'))
        .catch(() => showToast('Partagez votre succès !','info'));
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['apprenant']);

$user = currentUser();

// Traitement du choix
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $langue = trim($_POST['langue'] ?? '');
    $languesValides = ['Ewondo','Duala','Fulfulde','Féfé','Basaa','Ghomala'];
    if (in_array($langue, $languesValides)) {
        updateUserLangue($user['id'], $langue);
        redirect('choisir-experience.php');
    }
}

$pageTitle = 'Choisir ma langue';
$rootPath  = '../';
include __DIR__ . '/../includes/header.php';
?>

<main class="sel-main">
    <div class="container sel-wrap">
        <div class="section-title">
            <h1>Choisissez votre langue</h1>
            <p>Sélectionnez la langue qui résonne avec votre héritage pour commencer votre parcours.</p>
        </div>

        <form method="POST" action="selection-langue.php">
            <div class="lang-grid" id="lang-grid">
                <?php
                $langues = [
                    ['nom'=>'Ewondo',   'region'=>'Fang-Béti · Centre',  'icon'=>'fas fa-drum'],
                    ['nom'=>'Duala',    'region'=>'Sawa · Littoral',      'icon'=>'fas fa-mask'],
                    ['nom'=>'Fulfulde', 'region'=>'Adamaoua · Nord',      'icon'=>'fas fa-feather'],
                    ['nom'=>'Féfé',     'region'=>'Grassfields · Ouest',  'icon'=>'fas fa-tree'],
                    ['nom'=>'Basaa',    'region'=>'Littoral · Centre',    'icon'=>'fas fa-leaf'],
                    ['nom'=>'Ghomala', 'region'=>'Grassfields · Ouest',  'icon'=>'fas fa-scroll'],
                ];
                foreach ($langues as $l):
                    $selected = ($user['langue'] === $l['nom']);
                ?>
                <label class="lang-card <?= $selected ? 'selected':'' ?>"
                       onclick="selectLang(this)">
                    <input type="radio" name="langue" value="<?= e($l['nom']) ?>"
                           <?= $selected ? 'checked':'' ?> style="display:none;">
                    <div class="lang-icon"><i class="<?= e($l['icon']) ?>"></i></div>
                    <div class="lang-name"><?= e($l['nom']) ?></div>
                    <div class="lang-region"><?= e($l['region']) ?></div>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="continue-bar" id="continue-bar"
                 style="<?= $user['langue'] ? 'display:flex;' : 'display:none;' ?>">
                <p>Langue choisie : <strong id="langue-label"><?= e($user['langue']) ?></strong></p>
                <button type="submit" class="btn btn-primary">Continuer →</button>
            </div>
        </form>
    </div>
</main>

<style>
body{display:flex;flex-direction:column;min-height:100vh;}
.sel-main{flex:1;padding:3rem 0 5rem;}
.section-title{text-align:center;margin-bottom:2rem;}
.section-title p{color:#5f554b;font-size:1rem;max-width:500px;margin:.5rem auto 0;}
.lang-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1.4rem;margin-top:1.5rem;}
.lang-card{background:white;border:2px solid var(--bordure);border-radius:var(--radius);padding:1.8rem 1.2rem;text-align:center;cursor:pointer;transition:var(--tr);display:flex;flex-direction:column;align-items:center;}
.lang-card:hover{transform:scale(1.04);border-color:var(--accent);}
.lang-card.selected{border-color:var(--secondaire);background:#f0f7ed;}
.lang-icon{width:80px;height:80px;background:#f3efe9;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:var(--primaire);margin-bottom:1.1rem;transition:var(--tr);}
.lang-card:hover .lang-icon,.lang-card.selected .lang-icon{background:var(--accent);color:white;}
.lang-name{font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:600;margin-bottom:.2rem;}
.lang-region{font-size:.82rem;color:#7b6e64;}
.continue-bar{position:fixed;bottom:0;left:0;right:0;background:white;border-top:1px solid var(--bordure);padding:.9rem 2rem;display:flex;align-items:center;justify-content:center;gap:1.5rem;box-shadow:0 -4px 20px rgba(0,0,0,.06);z-index:50;}
.continue-bar p{margin:0;font-size:.92rem;color:#5f554b;}
</style>

<script>
function selectLang(card) {
    document.querySelectorAll('.lang-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    const nom = card.querySelector('input').value;
    document.getElementById('langue-label').textContent = nom;
    document.getElementById('continue-bar').style.display = 'flex';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['apprenant']);
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $niveau = $_POST['niveau'] ?? 'debutant';
    $niveauxValides = ['debutant','intermediaire','avance'];
    if (in_array($niveau, $niveauxValides)) {
        updateUserNiveau($user['id'], $niveau);
        setFlash('success', 'Parcours configuré ! Bonne chance 🎯');
        redirect('dashboard.php');
    }
}

$pageTitle = 'Votre niveau';
$rootPath  = '../';
include __DIR__ . '/../includes/header.php';
?>

<main class="exp-main">
    <div class="container exp-wrap">

        <div class="etape-rappel">
            <i class="fas fa-check-circle"></i>
            <span>Langue sélectionnée : <strong><?= e($user['langue']) ?></strong></span>
            <a href="selection-langue.php" class="changer-lien">Changer</a>
        </div>

        <div class="exp-titre">
            <h1>Quel est votre niveau ?</h1>
            <p>Choisissez honnêtement pour que votre parcours soit bien adapté.</p>
        </div>

        <form method="POST" action="choisir-experience.php" id="exp-form">
            <div class="niveaux-grid">
                <?php
                $niveaux = [
                    ['val'=>'debutant',     'nom'=>'Débutant',      'icon'=>'fas fa-seedling',
                     'desc'=>'Je ne connais pas du tout cette langue. Je pars de zéro.',
                     'points'=>['Alphabet et sons de base','Salutations essentielles','Vocabulaire du quotidien'],
                     'duree'=>'~5 min / jour', 'badge'=>'Recommandé', 'color'=>'debutant'],
                    ['val'=>'intermediaire','nom'=>'Intermédiaire', 'icon'=>'fas fa-fire',
                     'desc'=>'Je connais quelques mots ou expressions de base.',
                     'points'=>['Conversations simples','Grammaire de base','Expressions courantes'],
                     'duree'=>'~8 min / jour', 'badge'=>'', 'color'=>'inter'],
                    ['val'=>'avance',       'nom'=>'Avancé',        'icon'=>'fas fa-crown',
                     'desc'=>'Je parle ou comprends déjà bien cette langue.',
                     'points'=>['Nuances culturelles','Proverbes et littérature','Expression orale avancée'],
                     'duree'=>'~12 min / jour', 'badge'=>'', 'color'=>'avance'],
                ];
                foreach ($niveaux as $n):
                    $sel = ($user['niveau'] === $n['val']);
                ?>
                <label class="niveau-card <?= $sel?'selected':'' ?>" onclick="selectNiveau(this)">
                    <input type="radio" name="niveau" value="<?= e($n['val']) ?>"
                           <?= $sel?'checked':'' ?> style="display:none;">
                    <?php if ($n['badge']): ?>
                        <div class="niveau-badge-top"><?= e($n['badge']) ?></div>
                    <?php endif; ?>
                    <div class="niveau-icone <?= e($n['color']) ?>-color">
                        <i class="<?= e($n['icon']) ?>"></i>
                    </div>
                    <h2 class="niveau-nom"><?= e($n['nom']) ?></h2>
                    <p class="niveau-desc"><?= e($n['desc']) ?></p>
                    <ul class="niveau-points">
                        <?php foreach ($n['points'] as $pt): ?>
                            <li><i class="fas fa-check"></i> <?= e($pt) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="niveau-duree"><i class="fas fa-clock"></i> <?= e($n['duree']) ?></div>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="exp-footer">
                <p id="footer-msg"><?= $user['niveau'] ? 'Niveau '.e(ucfirst($user['niveau'])).' sélectionné' : 'Sélectionnez votre niveau pour continuer' ?></p>
                <button type="submit" class="btn btn-primary" id="btn-continuer"
                        <?= !$user['niveau'] ? 'disabled':'' ?>>
                    Commencer mon parcours <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</main>

<style>
body{display:flex;flex-direction:column;min-height:100vh;}
.exp-main{flex:1;display:flex;align-items:center;padding:2rem 0 5rem;}
.exp-wrap{width:100%;max-width:960px;}
.etape-rappel{display:inline-flex;align-items:center;gap:.6rem;background:#f0f7ed;border:1px solid #c8e6c9;border-radius:30px;padding:.5rem 1.2rem;font-size:.88rem;margin-bottom:2rem;color:#2e7d32;}
.changer-lien{color:var(--primaire);font-size:.82rem;text-decoration:underline;margin-left:.4rem;}
.exp-titre{text-align:center;margin-bottom:2.5rem;}
.exp-titre h1{font-size:clamp(1.7rem,4vw,2.4rem);margin-bottom:.5rem;}
.exp-titre p{color:#5f554b;font-size:1rem;}
.niveaux-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem;margin-bottom:2rem;}
.niveau-card{background:white;border:2px solid var(--bordure);border-radius:18px;padding:1.8rem 1.5rem;cursor:pointer;transition:var(--tr);position:relative;display:flex;flex-direction:column;gap:.8rem;box-shadow:var(--ombre);}
.niveau-card:hover{transform:translateY(-6px);border-color:var(--accent);}
.niveau-card.selected{border-color:var(--secondaire);background:#f0f7ed;}
.niveau-card.selected::after{content:'\f058';font-family:'Font Awesome 6 Free';font-weight:900;position:absolute;top:1rem;right:1rem;color:var(--secondaire);font-size:1.3rem;}
.niveau-badge-top{position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--primaire);color:white;padding:.2rem .9rem;border-radius:20px;font-size:.72rem;font-weight:600;font-family:'Roboto',sans-serif;white-space:nowrap;}
.niveau-icone{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:.2rem;}
.debutant-color{background:#e8f5e9;color:#2e7d32;}.inter-color{background:#fff3e0;color:#e65100;}.avance-color{background:#fce4ec;color:#c62828;}
.niveau-card.selected .debutant-color{background:#2e7d32;color:white;}
.niveau-card.selected .inter-color{background:#e65100;color:white;}
.niveau-card.selected .avance-color{background:#c62828;color:white;}
.niveau-nom{font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:600;margin:0;}
.niveau-desc{font-size:.9rem;color:#5f554b;line-height:1.5;margin:0;}
.niveau-points{list-style:none;display:flex;flex-direction:column;gap:.4rem;margin:0;}
.niveau-points li{font-size:.85rem;color:#5f554b;display:flex;align-items:center;gap:.5rem;}
.niveau-points li i{color:var(--secondaire);font-size:.75rem;}
.niveau-duree{font-size:.8rem;color:#7b6e64;display:flex;align-items:center;gap:.4rem;margin-top:.2rem;padding-top:.8rem;border-top:1px solid var(--bordure);}
.exp-footer{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;background:white;border:1px solid var(--bordure);border-radius:14px;padding:1.2rem 1.5rem;box-shadow:var(--ombre);}
#footer-msg{font-size:.9rem;color:#7b6e64;margin:0;}
#btn-continuer:disabled{opacity:.4;cursor:not-allowed;}
</style>
<script>
function selectNiveau(card) {
    document.querySelectorAll('.niveau-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    const val = card.querySelector('input').value;
    const labels = {debutant:'Niveau Débutant sélectionné !', intermediaire:'Niveau Intermédiaire sélectionné !', avance:'Niveau Avancé sélectionné !'};
    document.getElementById('footer-msg').textContent = labels[val] || '';
    document.getElementById('btn-continuer').disabled = false;
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>

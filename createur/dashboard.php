<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['createur']);
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { setFlash('error','Action non autorisée.'); redirect('dashboard.php'); }

    if (isset($_POST['action_soumettre'])) {
        $titre  = trim($_POST['titre']  ?? '');
        $langue = trim($_POST['langue'] ?? '');
        $niveau = (int)($_POST['niveau'] ?? 1);
        $desc   = trim($_POST['description'] ?? '');
        if ($titre && $langue) {
            createCours([
                'titre'       => $titre,
                'langue'      => $langue,
                'niveau'      => $niveau,
                'description' => $desc,
                'statut'      => 'en_attente',
                'createur_id' => $user['id'],
            ]);
            setFlash('success', 'Cours soumis pour validation par l\'administrateur !');
        } else {
            setFlash('error', 'Titre et langue sont obligatoires.');
        }
        redirect('dashboard.php');
    }
}

// Cours de ce créateur
$pdo   = getPDO();
$stmt  = $pdo->prepare('SELECT * FROM cours WHERE createur_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$mesCours = $stmt->fetchAll();

$publies  = array_filter($mesCours, fn($c) => $c['statut'] === 'publie');
$attentes = array_filter($mesCours, fn($c) => $c['statut'] === 'en_attente');
$brouillons = array_filter($mesCours, fn($c) => $c['statut'] === 'brouillon');

$pageTitle = 'Espace Créateur';
$rootPath  = '../';
$extraCss  = 'dashboard-createur.css';
include __DIR__ . '/../includes/header.php';
?>

<div class="container editeur-wrap">
    <div class="top-bar">
        <h1>Mon espace créateur</h1>
        <div style="display:flex;gap:.8rem;">
            <button class="btn btn-primary" onclick="toggleForm()">
                <i class="fas fa-plus"></i> Nouveau cours
            </button>
            <a href="editeur-lecon.php" class="btn btn-secondary">
                <i class="fas fa-edit"></i> Éditeur avancé
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat"><div class="stat-v"><?= count($mesCours) ?></div><div class="stat-l">Cours créés</div></div>
        <div class="stat"><div class="stat-v"><?= count($publies) ?></div><div class="stat-l">Publiés</div></div>
        <div class="stat"><div class="stat-v"><?= count($attentes) ?></div><div class="stat-l">En attente</div></div>
    </div>

    <!-- Formulaire nouveau cours -->
    <div class="form-panel" id="form-panel">
        <h3>Créer un nouveau cours</h3>
        <form method="POST" action="dashboard.php">
            <input type="hidden" name="action_soumettre" value="1">
            <input type="hidden" name="csrf_token" value="<?= csrf() ?>">

            <div class="grid-2">
                <div class="form-group">
                    <label>Titre du cours *</label>
                    <input type="text" name="titre" placeholder="Ex: Les salutations en Ewondo" required>
                </div>
                <div class="form-group">
                    <label>Langue *</label>
                    <select name="langue">
                        <?php foreach (['Ewondo','Duala','Fulfulde','Féfé','Basaa','Ghomala'] as $l): ?>
                            <option><?= e($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group" style="max-width:300px;">
                <label>Niveau</label>
                <select name="niveau">
                    <option value="1">Niveau 1 — Débutant</option>
                    <option value="2">Niveau 2 — Intermédiaire</option>
                    <option value="3">Niveau 3 — Avancé</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Décrivez le contenu pédagogique de ce cours..." style="min-height:80px;"></textarea>
            </div>
            <div class="form-btns">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Soumettre pour validation
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleForm()">Annuler</button>
            </div>
        </form>
    </div>

    <!-- Liste mes cours -->
    <h2 style="font-family:'Playfair Display',serif;margin-bottom:1rem;">Mes cours</h2>

    <?php if (empty($mesCours)): ?>
        <p style="color:#7b6e64;padding:2rem;text-align:center;">
            Vous n'avez pas encore créé de cours. Cliquez sur "Nouveau cours" pour commencer.
        </p>
    <?php else: ?>
    <div id="cours-list">
        <?php foreach ($mesCours as $c):
            $statutClass = ['publie'=>'pill-publie','en_attente'=>'pill-attente','brouillon'=>'pill-brouillon'][$c['statut']] ?? '';
            $statutLabel = ['publie'=>'Publié','en_attente'=>'En attente','brouillon'=>'Brouillon'][$c['statut']] ?? $c['statut'];
        ?>
        <div class="cours-item">
            <div class="ci-icon"><i class="fas fa-book"></i></div>
            <div class="ci-info">
                <div class="ci-title"><?= e($c['titre']) ?></div>
                <div class="ci-meta"><?= e($c['langue']) ?> · Niveau <?= (int)$c['niveau'] ?></div>
            </div>
            <span class="pill <?= e($statutClass) ?>"><?= e($statutLabel) ?></span>
            <?php if ($c['statut'] !== 'publie'): ?>
            <a href="editeur-lecon.php?cours_id=<?= (int)$c['id'] ?>" class="btn btn-secondary btn-sm" style="margin-left:.5rem;">
                <i class="fas fa-edit"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleForm() {
    document.getElementById('form-panel').classList.toggle('open');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

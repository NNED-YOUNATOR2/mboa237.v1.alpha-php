<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['admin']);

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { setFlash('error','Action non autorisée.'); redirect('dashboard.php'); }

    if (isset($_POST['action_ajouter'])) {
        $titre  = trim($_POST['titre']  ?? '');
        $langue = trim($_POST['langue'] ?? '');
        $niveau = (int)($_POST['niveau'] ?? 1);
        $desc   = trim($_POST['description'] ?? '');
        if ($titre && $langue) {
            createCours(['titre'=>$titre,'langue'=>$langue,'niveau'=>$niveau,'description'=>$desc,'statut'=>'publie','createur_id'=>currentUser()['id']]);
            setFlash('success', 'Cours "' . $titre . '" ajouté avec succès !');
        } else {
            setFlash('error', 'Titre et langue sont obligatoires.');
        }
        redirect('dashboard.php#cours');
    }

    if (isset($_POST['action_supprimer'])) {
        $id = (int)($_POST['cours_id'] ?? 0);
        if ($id) { deleteCours($id); setFlash('success', 'Cours supprimé.'); }
        redirect('dashboard.php#cours');
    }

    if (isset($_POST['action_valider'])) {
        $id = (int)($_POST['cours_id'] ?? 0);
        if ($id) { updateCoursStatut($id, 'publie'); setFlash('success', 'Cours publié !'); }
        redirect('dashboard.php#validation');
    }

    if (isset($_POST['action_rejeter'])) {
        $id = (int)($_POST['cours_id'] ?? 0);
        if ($id) { deleteCours($id); setFlash('success', 'Cours rejeté et supprimé.'); }
        redirect('dashboard.php#validation');
    }
}

$stats   = getAdminStats();
$cours   = getCoursList('publie');
$attente = getCoursList('en_attente');
$users   = getAllUsers();

$pageTitle = 'Administration';
$rootPath  = '../';
$extraCss  = 'dashboard-admin.css';
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-wrap">
    <nav class="admin-nav">
        <a href="#stats"      class="nav-lnk active"><i class="fas fa-chart-pie"></i> Vue d'ensemble</a>
        <a href="#cours"      class="nav-lnk"><i class="fas fa-book"></i> Cours</a>
        <a href="#validation" class="nav-lnk">
            <i class="fas fa-check-circle"></i> Validation
            <?php if ($stats['en_attente'] > 0): ?>
                <span class="nav-badge"><?= $stats['en_attente'] ?></span>
            <?php endif; ?>
        </a>
        <a href="#utilisateurs" class="nav-lnk"><i class="fas fa-users"></i> Utilisateurs</a>
    </nav>

    <main class="admin-main">
        <h1 class="page-title">Tableau de bord</h1>

        <!-- Stats -->
        <div class="stats-grid" id="stats">
            <div class="stat-card">
                <div class="stat-val"><?= $stats['utilisateurs'] ?></div>
                <div class="stat-lbl">Utilisateurs</div>
            </div>
            <div class="stat-card">
                <div class="stat-val"><?= $stats['cours_publies'] ?></div>
                <div class="stat-lbl">Cours publiés</div>
            </div>
            <div class="stat-card">
                <div class="stat-val"><?= $stats['en_attente'] ?></div>
                <div class="stat-lbl">En attente</div>
            </div>
            <div class="stat-card">
                <div class="stat-val"><?= $stats['lecons'] ?></div>
                <div class="stat-lbl">Leçons</div>
            </div>
        </div>

        <!-- Cours publiés -->
        <div class="section-box" id="cours">
            <div class="box-head">
                <h2>Gestion des cours</h2>
                <button class="btn btn-primary btn-sm" onclick="openModal('modal-cours')">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </div>
            <table>
                <thead>
                    <tr><th>Titre</th><th>Langue</th><th>Niveau</th><th>Statut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (empty($cours)): ?>
                    <tr><td colspan="5" style="text-align:center;color:#7b6e64;padding:2rem;">Aucun cours publié.</td></tr>
                <?php else: foreach ($cours as $c): ?>
                    <tr>
                        <td><strong><?= e($c['titre']) ?></strong></td>
                        <td><?= e($c['langue']) ?></td>
                        <td>Niveau <?= (int)$c['niveau'] ?></td>
                        <td><span class="pill pill-publie">Publié</span></td>
                        <td>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Supprimer ce cours ?')">
                                <input type="hidden" name="action_supprimer" value="1">
                                <input type="hidden" name="cours_id" value="<?= (int)$c['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
                                <button type="submit" class="btn-del">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- En attente de validation -->
        <div class="section-box" id="validation">
            <div class="box-head">
                <h2>Cours en attente
                    <?php if ($stats['en_attente'] > 0): ?>
                        <span class="pill pill-attente" style="font-size:.8rem;margin-left:.5rem;">
                            <?= $stats['en_attente'] ?> en attente
                        </span>
                    <?php endif; ?>
                </h2>
            </div>
            <?php if (empty($attente)): ?>
                <p style="padding:2rem;text-align:center;color:#7b6e64;">
                    <i class="fas fa-check-double" style="font-size:1.5rem;display:block;margin-bottom:.5rem;opacity:.4;"></i>
                    Aucun cours en attente de validation.
                </p>
            <?php else: ?>
            <div style="padding:1rem 1.5rem;display:flex;flex-direction:column;gap:1rem;">
                <?php foreach ($attente as $c): ?>
                <div class="valid-card">
                    <div class="valid-icon"><i class="fas fa-book"></i></div>
                    <div class="valid-info">
                        <div class="valid-titre"><?= e($c['titre']) ?></div>
                        <div class="valid-meta">
                            <i class="fas fa-globe-africa"></i> <?= e($c['langue']) ?>
                            &nbsp;·&nbsp; Niveau <?= (int)$c['niveau'] ?>
                        </div>
                        <div class="valid-desc"><?= e($c['description'] ?? 'Aucune description.') ?></div>
                    </div>
                    <div class="valid-btns">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action_valider" value="1">
                            <input type="hidden" name="cours_id" value="<?= (int)$c['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
                            <button type="submit" class="btn-val">
                                <i class="fas fa-check"></i> Publier
                            </button>
                        </form>
                        <form method="POST" style="display:inline;"
                              onsubmit="return confirm('Rejeter et supprimer ce cours ?')">
                            <input type="hidden" name="action_rejeter" value="1">
                            <input type="hidden" name="cours_id" value="<?= (int)$c['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
                            <button type="submit" class="btn-del">
                                <i class="fas fa-times"></i> Rejeter
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Utilisateurs -->
        <div class="section-box" id="utilisateurs">
            <div class="box-head"><h2>Utilisateurs inscrits</h2></div>
            <table>
                <thead>
                    <tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Langue</th><th>Série</th><th>Inscription</th></tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e($u['nom']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="pill pill-<?= e($u['role']) ?>"><?= e($u['role']) ?></span></td>
                        <td><?= e($u['langue'] ?? '—') ?></td>
                        <td><?= (int)$u['streak'] ?> 🔥</td>
                        <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal ajout cours -->
<div class="modal-backdrop" id="modal-cours">
    <div class="modal-box">
        <h3>Ajouter un cours</h3>
        <form method="POST" action="dashboard.php">
            <input type="hidden" name="action_ajouter" value="1">
            <input type="hidden" name="csrf_token" value="<?= csrf() ?>">

            <div class="form-group">
                <label>Titre *</label>
                <input type="text" name="titre" placeholder="Ex: Les salutations en Ewondo" required>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Langue *</label>
                    <select name="langue">
                        <?php foreach (['Ewondo','Duala','Fulfulde','Féfé','Basaa','Ghomala'] as $l): ?>
                            <option><?= e($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Niveau</label>
                    <select name="niveau">
                        <option value="1">Niveau 1 — Débutant</option>
                        <option value="2">Niveau 2 — Intermédiaire</option>
                        <option value="3">Niveau 3 — Avancé</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Décrivez le contenu de ce cours..." style="min-height:80px;"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modal-cours')">Annuler</button>
                <button type="submit" class="btn btn-primary" style="border-radius:8px;padding:.6rem 1.4rem;">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.valid-card{background:white;border:1px solid var(--bordure);border-radius:var(--radius);padding:1.2rem 1.4rem;display:flex;gap:1.2rem;align-items:flex-start;transition:var(--tr);}
.valid-card:hover{border-color:var(--accent);}
.valid-icon{width:44px;height:44px;background:#f3efe9;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--primaire);font-size:1.1rem;flex-shrink:0;}
.valid-info{flex:1;}
.valid-titre{font-weight:600;font-size:1rem;margin-bottom:.2rem;}
.valid-meta{font-size:.82rem;color:#7b6e64;margin-bottom:.5rem;}
.valid-desc{font-size:.88rem;color:#5f554b;}
.valid-btns{display:flex;gap:.6rem;flex-shrink:0;flex-wrap:wrap;}
.nav-badge{background:var(--primaire);color:white;border-radius:10px;padding:.1rem .5rem;font-size:.72rem;font-family:'Roboto',sans-serif;font-weight:600;margin-left:.4rem;}
</style>

<script>
document.querySelectorAll('.nav-lnk').forEach(lnk => {
    lnk.addEventListener('click', () => {
        document.querySelectorAll('.nav-lnk').forEach(l => l.classList.remove('active'));
        lnk.classList.add('active');
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

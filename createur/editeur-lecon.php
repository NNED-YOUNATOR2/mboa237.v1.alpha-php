<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['createur','admin']);
$user = currentUser();

$coursId = isset($_GET['cours_id']) ? (int)$_GET['cours_id'] : null;
$cours   = $coursId ? getCoursById($coursId) : null;
$lecons  = $coursId ? getLeconsByCours($coursId) : [];

// Traitement POST : sauvegarder une leçon
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { setFlash('error','Action non autorisée.'); redirect('editeur-lecon.php'); }

    if (isset($_POST['action_save_cours'])) {
        $titre  = trim($_POST['titre']  ?? '');
        $langue = trim($_POST['langue'] ?? 'Ewondo');
        $niveau = (int)($_POST['niveau'] ?? 1);
        $desc   = trim($_POST['description'] ?? '');
        $statut = $_POST['statut'] ?? 'brouillon';

        if (!$coursId) {
            $coursId = createCours([
                'titre'       => $titre,
                'langue'      => $langue,
                'niveau'      => $niveau,
                'description' => $desc,
                'statut'      => in_array($statut,['brouillon','en_attente']) ? $statut : 'brouillon',
                'createur_id' => $user['id'],
            ]);
            setFlash('success', 'Cours créé ! Ajoutez maintenant vos exercices.');
            redirect('editeur-lecon.php?cours_id=' . $coursId);
        } else {
            $pdo = getPDO();
            $pdo->prepare('UPDATE cours SET titre=?,langue=?,niveau=?,description=?,statut=? WHERE id=?')
                ->execute([$titre, $langue, $niveau, $desc, $statut, $coursId]);
            setFlash('success', 'Cours mis à jour !');
            redirect('editeur-lecon.php?cours_id=' . $coursId);
        }
    }

    if (isset($_POST['action_add_lecon']) && $coursId) {
        $titre   = trim($_POST['lecon_titre']   ?? '');
        $type    = $_POST['lecon_type']          ?? 'vocabulaire';
        $contenu = $_POST['contenu_json']         ?? '{}';

        // Valider le JSON
        $contenuArr = json_decode($contenu, true);
        if (!$titre) { setFlash('error','Titre de la leçon requis.'); redirect('editeur-lecon.php?cours_id='.$coursId); }

        // Compter les leçons existantes pour l'ordre
        $pdo   = getPDO();
        $ordre = (int)$pdo->prepare('SELECT COUNT(*) FROM lecons WHERE cours_id = ?')
                          ->execute([$coursId]) ? $pdo->query("SELECT COUNT(*) FROM lecons WHERE cours_id = $coursId")->fetchColumn() + 1 : 1;

        $pdo->prepare('INSERT INTO lecons (cours_id, titre, type, contenu, ordre) VALUES (?,?,?,?,?)')
            ->execute([$coursId, $titre, $type, json_encode($contenuArr, JSON_UNESCAPED_UNICODE), $ordre]);
        setFlash('success', 'Exercice ajouté !');
        redirect('editeur-lecon.php?cours_id=' . $coursId);
    }

    if (isset($_POST['action_del_lecon'])) {
        $lid = (int)($_POST['lecon_id'] ?? 0);
        if ($lid) getPDO()->prepare('DELETE FROM lecons WHERE id = ?')->execute([$lid]);
        setFlash('success', 'Exercice supprimé.');
        redirect('editeur-lecon.php?cours_id=' . $coursId);
    }
}

$pageTitle = $cours ? 'Éditeur — ' . $cours['titre'] : 'Nouveau cours';
$rootPath  = '../';
$extraCss  = 'dashboard-createur.css';
include __DIR__ . '/../includes/header.php';
?>

<div class="container editeur-wrap">
    <div class="editeur-header">
        <div>
            <h1>Éditeur de leçon</h1>
            <p style="color:#7b6e64;margin:0;font-size:.9rem;">
                <?= $cours ? 'Cours : '.e($cours['titre']).' · '.e($cours['langue']) : 'Nouveau cours' ?>
            </p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Mon espace</a>
    </div>

    <!-- Infos cours -->
    <div class="panel" style="margin-bottom:1.5rem;">
        <div class="panel-title"><i class="fas fa-info-circle"></i> Informations du cours</div>
        <form method="POST" action="editeur-lecon.php<?= $coursId ? '?cours_id='.$coursId : '' ?>">
            <input type="hidden" name="action_save_cours" value="1">
            <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
            <div class="grid-2">
                <div class="form-group">
                    <label>Titre *</label>
                    <input type="text" name="titre" value="<?= e($cours['titre'] ?? '') ?>"
                           placeholder="Ex: Les salutations" required>
                </div>
                <div class="form-group">
                    <label>Langue</label>
                    <select name="langue">
                        <?php foreach (['Ewondo','Duala','Fulfulde','Féfé','Basaa','Ghomala'] as $l): ?>
                            <option <?= ($cours['langue'] ?? '') === $l ? 'selected':'' ?>><?= e($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Niveau</label>
                    <select name="niveau">
                        <?php for ($n=1; $n<=3; $n++): ?>
                            <option value="<?= $n ?>" <?= ($cours['niveau'] ?? 1) == $n ? 'selected':'' ?>>
                                Niveau <?= $n ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Statut</label>
                    <select name="statut">
                        <option value="brouillon"   <?= ($cours['statut'] ?? '') === 'brouillon'   ? 'selected':'' ?>>Brouillon</option>
                        <option value="en_attente"  <?= ($cours['statut'] ?? '') === 'en_attente'  ? 'selected':'' ?>>Soumettre pour validation</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" style="min-height:65px;"
                          placeholder="Décrivez cette leçon..."><?= e($cours['description'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= $coursId ? 'Enregistrer les modifications' : 'Créer le cours' ?>
            </button>
        </form>
    </div>

    <?php if ($coursId): ?>
    <!-- Liste des exercices existants -->
    <div class="panel" style="margin-bottom:1.5rem;">
        <div class="panel-title">
            <i class="fas fa-list-ol"></i> Exercices
            <span style="font-size:.8rem;font-weight:300;color:#7b6e64;">(<?= count($lecons) ?>)</span>
        </div>

        <?php if (empty($lecons)): ?>
            <p style="color:#7b6e64;font-size:.88rem;padding:.5rem 0;">Aucun exercice. Ajoutez-en un ci-dessous.</p>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:.7rem;margin-bottom:1.2rem;">
            <?php
            $typeLabels = ['vocabulaire'=>'Vocabulaire','qcm'=>'QCM','saisie'=>'Écriture','vf'=>'Vrai/Faux'];
            foreach ($lecons as $i => $l):
                $c = $l['contenu'] ?? [];
                $preview = $l['type'] === 'vocabulaire' ? ($c['mot'] ?? '—')
                         : ($c['question'] ?? $c['affirmation'] ?? '—');
            ?>
            <div style="background:#fafaf8;border:1px solid var(--bordure);border-radius:10px;padding:.9rem 1.1rem;display:flex;align-items:center;gap:.9rem;">
                <div style="width:28px;height:28px;border-radius:50%;background:var(--primaire);color:white;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:600;flex-shrink:0;"><?= $i+1 ?></div>
                <div style="flex:1;">
                    <div style="font-size:.75rem;color:var(--primaire);font-weight:500;text-transform:uppercase;"><?= e($typeLabels[$l['type']] ?? $l['type']) ?></div>
                    <div style="font-size:.88rem;color:#5f554b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:300px;"><?= e($preview) ?></div>
                </div>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?')">
                    <input type="hidden" name="action_del_lecon" value="1">
                    <input type="hidden" name="lecon_id" value="<?= (int)$l['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
                    <button type="submit" style="background:none;border:none;color:#ccc;cursor:pointer;font-size:.9rem;transition:var(--tr);"
                            onmouseover="this.style.color='#c0392b'" onmouseout="this.style.color='#ccc'">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Formulaire ajout exercice -->
        <div style="border-top:1px solid var(--bordure);padding-top:1.2rem;">
            <h4 style="font-family:'Playfair Display',serif;font-size:1rem;margin-bottom:1rem;">Ajouter un exercice</h4>
            <form method="POST" action="editeur-lecon.php?cours_id=<?= (int)$coursId ?>" id="form-lecon">
                <input type="hidden" name="action_add_lecon" value="1">
                <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
                <input type="hidden" name="contenu_json" id="contenu_json" value="{}">

                <div class="grid-2" style="margin-bottom:1rem;">
                    <div class="form-group">
                        <label>Titre de l'exercice *</label>
                        <input type="text" name="lecon_titre" id="lecon_titre" placeholder="Ex: Mbolo — Bonjour">
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="lecon_type" id="lecon_type" onchange="changerType(this.value)">
                            <option value="vocabulaire">Vocabulaire</option>
                            <option value="qcm">QCM — Choix multiple</option>
                            <option value="saisie">Écriture libre</option>
                            <option value="vf">Vrai / Faux</option>
                        </select>
                    </div>
                </div>

                <div id="champs-type"></div>

                <button type="button" class="btn btn-primary" onclick="soumettrelecon()">
                    <i class="fas fa-plus"></i> Ajouter cet exercice
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.editeur-wrap{padding:2rem 0;flex:1;}
.editeur-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;}
.editeur-header h1{font-family:'Playfair Display',serif;font-size:1.8rem;}
.panel{background:white;border:1px solid var(--bordure);border-radius:var(--radius);padding:1.5rem;box-shadow:var(--ombre);}
.panel-title{font-family:'Playfair Display',serif;font-size:1.1rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:.6rem;color:var(--primaire);}
</style>

<script>
function changerType(type) {
    const d = document.getElementById('champs-type');
    if (type === 'vocabulaire') {
        d.innerHTML = `
        <div class="grid-2" style="margin-bottom:1rem;">
            <div class="form-group"><label>Mot / Expression</label><input type="text" id="f-mot" placeholder="Ex: Mbolo"></div>
            <div class="form-group"><label>Phonétique</label><input type="text" id="f-phon" placeholder="/mbo-lo/"></div>
        </div>
        <div class="form-group" style="margin-bottom:1rem;">
            <label>Traduction</label><input type="text" id="f-trad" placeholder="Bonjour">
        </div>`;
    } else if (type === 'qcm') {
        d.innerHTML = `
        <div class="form-group" style="margin-bottom:1rem;">
            <label>Question</label><input type="text" id="f-q" placeholder="Comment dit-on...">
        </div>
        <div style="margin-bottom:1rem;">
            <label style="font-size:.88rem;margin-bottom:.4rem;display:block;">Options (cochez la bonne réponse)</label>
            ${['A','B','C','D'].map((l,i) => `
            <div style="display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem;">
                <span style="width:24px;text-align:center;font-weight:600;color:var(--primaire);">${l}</span>
                <input type="text" id="f-opt${i}" placeholder="Option ${l}" style="flex:1;">
                <input type="radio" name="bonne-rep" value="${i}" id="rep${i}">
                <label for="rep${i}" style="font-size:.8rem;">Bonne</label>
            </div>`).join('')}
        </div>`;
    } else if (type === 'saisie') {
        d.innerHTML = `
        <div class="form-group" style="margin-bottom:1rem;">
            <label>Question / Consigne</label><input type="text" id="f-q" placeholder="Écrivez... en Ewondo">
        </div>
        <div class="grid-2" style="margin-bottom:1rem;">
            <div class="form-group"><label>Bonne réponse (minuscules)</label><input type="text" id="f-rep" placeholder="mbolo"></div>
            <div class="form-group"><label>Indice (optionnel)</label><input type="text" id="f-ind" placeholder="M _ _ _ _ _"></div>
        </div>`;
    } else if (type === 'vf') {
        d.innerHTML = `
        <div class="form-group" style="margin-bottom:1rem;">
            <label>Affirmation à évaluer</label>
            <textarea id="f-aff" placeholder="Ex: Mbolo signifie Bonjour en Ewondo" style="min-height:70px;"></textarea>
        </div>
        <div class="form-group" style="margin-bottom:1rem;">
            <label>La réponse correcte est :</label>
            <div style="display:flex;gap:1rem;margin-top:.5rem;">
                <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;">
                    <input type="radio" name="vf-rep" value="true" checked> ✅ Vrai
                </label>
                <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;">
                    <input type="radio" name="vf-rep" value="false"> ❌ Faux
                </label>
            </div>
        </div>`;
    }
}
changerType('vocabulaire');

function soumettrelecon() {
    const type  = document.getElementById('lecon_type').value;
    const titre = document.getElementById('lecon_titre').value.trim();
    if (!titre) { alert('Veuillez saisir un titre pour l\'exercice.'); return; }

    let contenu = {};
    if (type === 'vocabulaire') {
        contenu = { mot: document.getElementById('f-mot')?.value||'', phonetique: document.getElementById('f-phon')?.value||'', traduction: document.getElementById('f-trad')?.value||'' };
    } else if (type === 'qcm') {
        const repEl = document.querySelector('input[name="bonne-rep"]:checked');
        contenu = {
            question: document.getElementById('f-q')?.value||'',
            options:  ['A','B','C','D'].map((_,i) => document.getElementById('f-opt'+i)?.value||'Option '+(i+1)),
            reponse:  repEl ? parseInt(repEl.value) : 0
        };
    } else if (type === 'saisie') {
        contenu = { question: document.getElementById('f-q')?.value||'', reponse: document.getElementById('f-rep')?.value||'', indice: document.getElementById('f-ind')?.value||'' };
    } else if (type === 'vf') {
        const repVf = document.querySelector('input[name="vf-rep"]:checked');
        contenu = { affirmation: document.getElementById('f-aff')?.value||'', reponse: repVf?.value === 'true' };
    }

    document.getElementById('contenu_json').value = JSON.stringify(contenu);
    document.getElementById('form-lecon').submit();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

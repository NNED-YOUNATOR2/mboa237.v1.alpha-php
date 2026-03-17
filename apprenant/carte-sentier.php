<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['apprenant']);
$user  = currentUser();

// Filtre langue
$langue = $_GET['langue'] ?? $user['langue'] ?? null;
$cours  = getCoursWithProgression($user['id'], ($langue && $langue !== 'all') ? $langue : null);
$prog   = getProgressionGlobale($user['id']);

// Langues disponibles pour les filtres
$pdo   = getPDO();
$langues = $pdo->query("SELECT DISTINCT langue FROM cours WHERE statut='publie' ORDER BY langue")
               ->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Mon parcours';
$rootPath  = '../';
$extraCss  = 'carte-sentier.css';
include __DIR__ . '/../includes/header.php';
?>

<div class="container page-body">

    <!-- En-tête -->
    <div class="parcours-header">
        <div class="parcours-info">
            <h1>Parcours de <?= e(explode(' ', $user['nom'])[0]) ?></h1>
            <p class="parcours-langue">Langue en cours : <strong><?= e($user['langue']) ?></strong></p>
        </div>
        <div class="parcours-global">
            <div class="global-cercle">
                <svg viewBox="0 0 44 44" class="progress-ring">
                    <circle cx="22" cy="22" r="18" class="ring-bg"/>
                    <circle cx="22" cy="22" r="18" class="ring-fill"
                            style="stroke-dashoffset:<?= 113 - (113 * $prog['moyenne'] / 100) ?>"/>
                </svg>
                <span class="global-pct"><?= $prog['moyenne'] ?>%</span>
            </div>
            <span class="global-lbl">Progression</span>
        </div>
    </div>

    <!-- Filtres langue -->
    <div class="filtres">
        <a href="carte-sentier.php?langue=all"
           class="filtre-btn <?= (!$langue || $langue==='all') ? 'active':'' ?>">Toutes</a>
        <?php foreach ($langues as $l): ?>
        <a href="carte-sentier.php?langue=<?= urlencode($l) ?>"
           class="filtre-btn <?= $langue===$l ? 'active':'' ?>"><?= e($l) ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Sentier -->
    <div class="sentier">
        <?php
        // Grouper par niveau
        $niveaux = [];
        foreach ($cours as $c) {
            $n = $c['niveau'] ?? 1;
            $niveaux[$n][] = $c;
        }
        ksort($niveaux);

        $icons = ['fas fa-handshake','fas fa-sort-numeric-up','fas fa-users','fas fa-palette',
                  'fas fa-drum','fas fa-leaf','fas fa-scroll','fas fa-star'];
        $niveauxLabels = [1=>'Débutant',2=>'Intermédiaire',3=>'Avancé'];

        foreach ($niveaux as $niv => $lecons):
        ?>
        <div class="niveau-groupe">
            <div class="niveau-titre">
                Niveau <?= (int)$niv ?>
                <span class="niveau-badge"><?= e($niveauxLabels[$niv] ?? 'Niveau '.$niv) ?></span>
            </div>
            <?php foreach ($lecons as $i => $c):
                $pct    = (int)$c['progression'];
                $locked = (bool)$c['verrouille'];
                $etat   = $locked ? 'verrouille' : ($pct===100 ? 'termine' : ($pct>0 ? 'en-cours' : 'a-faire'));
                $icon   = $icons[$i % count($icons)];
                $statutLabel = $locked ? 'Verrouillé' : ($pct===100 ? 'Terminé' : ($pct>0 ? 'En cours' : 'Nouveau'));
                $statutClass = $locked ? 'statut-verrouille' : ($pct===100 ? 'statut-termine' : ($pct>0 ? 'statut-encours' : 'statut-nouveau'));
                $pointIcon   = $pct===100 ? '✓' : ($pct>0 ? '▶' : ($locked ? '🔒' : ''));
            ?>
            <div class="noeud <?= e($etat) ?>">
                <div class="noeud-point"><?= $pointIcon ?></div>
                <?php if ($locked): ?>
                <div class="noeud-card locked"
                     onclick="showToast('Terminez les leçons précédentes d\'abord.','info')"
                     style="cursor:not-allowed;">
                <?php else: ?>
                <a href="lecon.php?id=<?= (int)$c['id'] ?>" class="noeud-card">
                <?php endif; ?>
                    <div class="noeud-icone"><i class="<?= e($icon) ?>"></i></div>
                    <div class="noeud-texte">
                        <div class="noeud-titre"><?= e($c['titre']) ?></div>
                        <div class="noeud-meta">
                            <?= e($c['langue']) ?> ·
                            <?= e(mb_substr($c['description'] ?? '', 0, 50)) ?>…
                        </div>
                    </div>
                    <div class="noeud-droite">
                        <?php if ($pct > 0): ?>
                            <div class="noeud-pct"><?= $pct ?>%</div>
                        <?php endif; ?>
                        <span class="noeud-statut <?= e($statutClass) ?>"><?= e($statutLabel) ?></span>
                    </div>
                <?php if ($locked): ?></div><?php else: ?></a><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <?php if (empty($cours)): ?>
            <p style="color:#7b6e64;padding:2rem;text-align:center;">
                Aucune leçon disponible pour cette langue.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

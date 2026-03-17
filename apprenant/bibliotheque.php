<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['apprenant']);

$type   = $_GET['type']   ?? null;
$search = $_GET['search'] ?? null;
$ressId = isset($_GET['id']) ? (int)$_GET['id'] : null;

$ressources = getRessources($type ?: null, $search ?: null);
$ressource  = $ressId ? getRessourceById($ressId) : null;

$pageTitle = 'Bibliothèque culturelle';
$rootPath  = '../';
$extraCss  = 'bibliotheque.css';
include __DIR__ . '/../includes/header.php';
?>

<div class="container page-body">

    <div class="biblio-header">
        <div>
            <h1>Bibliothèque culturelle</h1>
            <p class="biblio-sous-titre">Proverbes, contes, grammaires et traditions du Cameroun</p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <!-- Contrôles -->
    <div class="biblio-controls">
        <form method="GET" action="bibliotheque.php" class="search-form">
            <div class="search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="search-input"
                       placeholder="Rechercher une ressource..."
                       value="<?= e($search ?? '') ?>">
                <?php if ($type): ?>
                    <input type="hidden" name="type" value="<?= e($type) ?>">
                <?php endif; ?>
                <?php if ($search): ?>
                    <a href="bibliotheque.php<?= $type ? '?type='.urlencode($type) : '' ?>"
                       class="search-clear"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <div class="type-filtres">
            <a href="bibliotheque.php<?= $search ? '?search='.urlencode($search) : '' ?>"
               class="filtre-btn <?= !$type ? 'active':'' ?>">Tout</a>
            <?php foreach (['pdf'=>'PDF','audio'=>'Audio','video'=>'Vidéo','texte'=>'Texte'] as $v => $lbl): ?>
            <a href="bibliotheque.php?type=<?= $v ?><?= $search ? '&search='.urlencode($search) : '' ?>"
               class="filtre-btn <?= $type===$v ? 'active':'' ?>">
                <?php $typeIcons = ['pdf'=>'fas fa-file-pdf','audio'=>'fas fa-headphones','video'=>'fas fa-play-circle','texte'=>'fas fa-align-left']; ?>
                <i class="<?= $typeIcons[$v] ?>"></i> <?= $lbl ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <p class="nb-resultats"><?= count($ressources) ?> ressource<?= count($ressources)>1?'s':'' ?></p>

    <!-- Grille -->
    <?php if (empty($ressources)): ?>
        <div class="vide-msg">
            <i class="fas fa-search"></i>
            <p>Aucune ressource ne correspond à votre recherche.</p>
        </div>
    <?php else: ?>
    <div class="ressources-grid">
        <?php
        $icons  = ['pdf'=>'fas fa-file-pdf','audio'=>'fas fa-headphones','video'=>'fas fa-play-circle','texte'=>'fas fa-align-left'];
        foreach ($ressources as $r):
            $icon = $icons[$r['type']] ?? 'fas fa-file';
        ?>
        <a href="bibliotheque.php?id=<?= (int)$r['id'] ?><?= $type?'&type='.urlencode($type):'' ?><?= $search?'&search='.urlencode($search):'' ?>"
           class="ressource-card" style="text-decoration:none;color:inherit;">
            <div class="card-banner banner-<?= e($r['type']) ?>">
                <i class="<?= e($icon) ?>"></i>
                <span class="card-type-badge badge-<?= e($r['type']) ?>"><?= strtoupper($r['type']) ?></span>
            </div>
            <div class="card-body">
                <div class="card-titre"><?= e($r['titre']) ?></div>
                <div class="card-langue"><i class="fas fa-globe-africa"></i> <?= e($r['langue']) ?></div>
                <p class="card-desc"><?= e($r['description'] ?? '') ?></p>
            </div>
            <div class="card-footer">
                <span class="card-taille"><i class="fas fa-weight"></i> <?= e($r['taille'] ?? '—') ?></span>
                <span class="card-btn">Consulter</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Aperçu ressource sélectionnée -->
    <?php if ($ressource): ?>
    <div class="ressource-apercu">
        <div class="apercu-header">
            <div>
                <h2><?= e($ressource['titre']) ?></h2>
                <p class="apercu-meta">
                    <?= e($ressource['langue']) ?> ·
                    <?= strtoupper($ressource['type']) ?> ·
                    <?= e($ressource['taille'] ?? '') ?>
                </p>
            </div>
            <a href="bibliotheque.php<?= $type?'?type='.urlencode($type):'' ?>"
               class="btn btn-secondary btn-sm">
                <i class="fas fa-times"></i> Fermer
            </a>
        </div>

        <?php $c = $ressource['contenu'] ?? []; ?>

        <?php if (($c['type'] ?? '') === 'texte'): ?>
            <div class="apercu-texte"><?= $c['texte'] ?? '' ?></div>

        <?php elseif (($c['type'] ?? '') === 'audio'): ?>
            <div class="apercu-audio">
                <div class="audio-player">
                    <button class="btn-play-audio" id="btn-play" onclick="toggleAudio()">
                        <i class="fas fa-play" id="play-icon"></i>
                    </button>
                    <div class="audio-info">
                        <div class="audio-titre"><?= e($c['titre'] ?? '') ?></div>
                        <div class="audio-duree">Durée : <?= e($c['duree'] ?? '') ?></div>
                    </div>
                </div>
                <div class="audio-barre">
                    <div class="audio-progression" id="audio-prog"></div>
                </div>
                <p style="margin-top:1rem;font-size:.88rem;color:#5f554b;">
                    <?= e($c['description'] ?? '') ?>
                </p>
            </div>

        <?php elseif (($c['type'] ?? '') === 'video'): ?>
            <div class="apercu-audio" style="background:#e3f2fd;border-color:#bbdefb;">
                <div style="text-align:center;padding:1.5rem;">
                    <i class="fas fa-play-circle" style="font-size:3rem;color:#1565c0;display:block;margin-bottom:1rem;"></i>
                    <p style="color:#1565c0;font-weight:500;margin-bottom:.5rem;">Aperçu vidéo non disponible en démo</p>
                    <p style="font-size:.88rem;color:#5f554b;"><?= e($c['description'] ?? '') ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div style="margin-top:1.2rem;">
            <button class="btn btn-primary btn-sm" onclick="showToast('Téléchargement simulé (démo)','info')">
                <i class="fas fa-download"></i>
                <?= $ressource['type']==='audio' ? 'Télécharger Audio' : ($ressource['type']==='video' ? 'Voir la vidéo' : 'Télécharger PDF') ?>
            </button>
        </div>
    </div>

    <script>
    let audioInterval = null, audioPct = 0;
    function toggleAudio() {
        const icon = document.getElementById('play-icon');
        const prog = document.getElementById('audio-prog');
        if (audioInterval) {
            clearInterval(audioInterval); audioInterval = null;
            icon.className = 'fas fa-play';
        } else {
            icon.className = 'fas fa-pause';
            audioInterval = setInterval(() => {
                audioPct = Math.min(audioPct + .5, 100);
                if (prog) prog.style.width = audioPct + '%';
                if (audioPct >= 100) { clearInterval(audioInterval); audioInterval = null; icon.className = 'fas fa-play'; audioPct = 0; }
            }, 100);
        }
    }
    </script>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

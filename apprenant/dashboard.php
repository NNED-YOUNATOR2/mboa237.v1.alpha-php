<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['apprenant']);
$user  = currentUser();
$cours = getCoursWithProgression($user['id'], null);
$prog  = getProgressionGlobale($user['id']);

$pageTitle = 'Mon tableau de bord';
$rootPath  = '../';
$extraCss  = 'dashboard-apprenant.css';
include __DIR__ . '/../includes/header.php';
?>

<div class="container page-wrap">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-title"><i class="fas fa-landmark"></i> Ma Bibliothèque</div>
        <ul class="lib-list">
            <li class="lib-item"><i class="fas fa-book"></i><span>Proverbes Ewondo</span><span class="pill pill-publie">PDF</span></li>
            <li class="lib-item"><i class="fas fa-headphones"></i><span>Contes Duala</span><span class="pill pill-publie">Audio</span></li>
            <li class="lib-item"><i class="fas fa-file-alt"></i><span>Grammaire Fulbé</span><span class="pill pill-publie">PDF</span></li>
            <li class="lib-item"><i class="fas fa-scroll"></i><span>Légendes Bamiléké</span><span class="pill pill-attente">Bientôt</span></li>
        </ul>
        <a href="carte-sentier.php" class="sidebar-cta" style="margin-bottom:.4rem;">
            <i class="fas fa-map-marked-alt"></i> Mon parcours
        </a>
        <a href="choisir-experience.php" class="sidebar-cta" style="margin-bottom:.4rem;">
            <i class="fas fa-sliders-h"></i> Mon niveau
        </a>
        <a href="bibliotheque.php" class="sidebar-cta">
            <i class="fas fa-book-open"></i> Bibliothèque complète
        </a>
    </aside>

    <!-- MAIN -->
    <main class="main-area">

        <!-- Welcome -->
        <div class="welcome-card card">
            <h1 id="userGreeting">Bonjour, <?= e(explode(' ', $user['nom'])[0]) ?> 👋</h1>
            <p style="margin:0;color:#5f554b;">
                Prêt pour votre leçon de <?= e($user['langue']) ?> ?
                <a href="selection-langue.php" style="font-size:.82rem;color:var(--primaire);margin-left:.4rem;">Changer</a>
            </p>
        </div>

        <!-- Exercice du jour -->
        <div class="card" style="margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;padding:1.2rem 1.5rem;">
            <div>
                <h3 style="font-family:'Playfair Display',serif;margin-bottom:.2rem;">
                    Exercice du jour 🔥
                    <?php if ($user['streak'] > 0): ?>
                        <span style="font-size:.82rem;color:#e65100;font-family:'Roboto',sans-serif;">
                            <?= (int)$user['streak'] ?> jour(s) de suite
                        </span>
                    <?php endif; ?>
                </h3>
                <p style="margin:0;font-size:.85rem;color:#7b6e64;">Gardez votre série active !</p>
            </div>
            <a href="exercice-quotidien.php" class="btn btn-primary" style="padding:.6rem 1.3rem;font-size:.88rem;white-space:nowrap;">
                <i class="fas fa-play"></i> Commencer
            </a>
        </div>

        <!-- Progression -->
        <div class="card prog-card">
            <div class="prog-head">
                <h2>Ma progression</h2>
                <span class="prog-pct"><?= $prog['moyenne'] ?>%</span>
            </div>
            <div class="progress-bg">
                <div class="progress-fill" style="width:<?= $prog['moyenne'] ?>%"></div>
            </div>
            <p style="margin-top:.5rem;font-size:.85rem;color:#7b6e64;">
                <?= $prog['terminees'] ?> leçon<?= $prog['terminees']>1?'s':'' ?> terminée<?= $prog['terminees']>1?'s':'' ?>
                sur <?= $prog['total'] ?>
            </p>
        </div>

        <!-- Leçons -->
        <div class="lessons-section">
            <h2 style="font-family:'Playfair Display',serif;margin-bottom:1.2rem;">Mes leçons</h2>
            <?php if (empty($cours)): ?>
                <p style="color:#7b6e64;">Aucune leçon disponible pour le moment.</p>
            <?php else: ?>
            <div class="lessons-grid">
                <?php
                $icons = ['fas fa-drum','fas fa-feather','fas fa-tree','fas fa-mask',
                          'fas fa-leaf','fas fa-scroll','fas fa-music','fas fa-star'];
                foreach ($cours as $i => $c):
                    $pct    = (int)$c['progression'];
                    $locked = (bool)$c['verrouille'];
                    $label  = $pct === 100 ? 'Réviser' : ($pct > 0 ? 'Continuer' : 'Commencer');
                    $icon   = $icons[$i % count($icons)];
                ?>
                <div class="lesson-card">
                    <div class="lesson-icon"><i class="<?= e($icon) ?>"></i></div>
                    <div class="lesson-title"><?= e($c['titre']) ?></div>
                    <div class="lesson-meta"><?= e($c['langue']) ?> · Niveau <?= (int)$c['niveau'] ?></div>
                    <div class="lesson-prog">
                        <div class="lp-bar"><div class="lp-fill" style="width:<?= $pct ?>%"></div></div>
                        <span><?= $pct ?>%</span>
                    </div>
                    <?php if ($locked): ?>
                        <span class="btn-lesson locked" onclick="showToast('Terminez les leçons précédentes d\'abord.','info')">
                            <i class="fas fa-lock"></i> Verrouillé
                        </span>
                    <?php else: ?>
                        <a href="lecon.php?id=<?= (int)$c['id'] ?>" class="btn-lesson">
                            <i class="fas fa-play"></i> <?= e($label) ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

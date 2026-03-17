<?php
// includes/navbar.php — Barre de navigation commune
// Variable $user doit être définie
$user = $user ?? getUser();
$initials = $user ? mb_strtoupper(mb_substr($user['nom'], 0, 1) . (mb_substr($user['nom'], mb_strpos($user['nom'], ' ') + 1, 1) ?: '')) : '?';
$roleLabels = ['admin' => 'Admin', 'createur' => 'Créateur', 'apprenant' => ''];
?>
<header class="app-header">
    <div class="container header-inner">
        <a href="<?= BASE_URL ?>/index.php" class="logo">
            Mboa237
            <?php if ($user && $user['role'] !== 'apprenant'): ?>
            <span class="badge-role badge-<?= $user['role'] ?>"><?= $roleLabels[$user['role']] ?></span>
            <?php endif; ?>
        </a>

        <?php if ($user): ?>
        <div class="user-badge">
            <span class="user-name-display" style="font-size:.9rem; color:#5f554b;">
                <?= htmlspecialchars($user['nom']) ?>
            </span>
            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
            <a href="<?= BASE_URL ?>/deconnexion.php" class="btn-logout">Déconnexion</a>
        </div>
        <?php else: ?>
        <nav style="display:flex; gap:1.5rem; align-items:center;">
            <a href="<?= BASE_URL ?>/connexion.php" style="font-weight:400;">Connexion</a>
            <a href="<?= BASE_URL ?>/inscription.php" class="btn btn-primary" style="padding:.5rem 1.3rem; font-size:.9rem;">S'inscrire</a>
        </nav>
        <?php endif; ?>
    </div>
</header>

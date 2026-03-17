<?php
// ============================================================
//  includes/header.php — En-tête HTML partagé
//  Utilise BASE_URL (absolu) pour tous les liens → zéro problème WAMP
// ============================================================

// $R = racine absolue du projet (ex: http://localhost/mboa237/)
$R = rtrim(BASE_URL, '/') . '/';

$pageTitle = $pageTitle ?? 'Mboa237';
$bodyClass = $bodyClass ?? '';
$user      = currentUser();
$flash     = renderFlash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · Mboa237</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $R ?>assets/css/style.css">
    <?php if (!empty($extraCss)): ?>
        <link rel="stylesheet" href="<?= $R ?>assets/css/<?= e($extraCss) ?>">
    <?php endif; ?>
    <script>const BASE_URL = '<?= rtrim(BASE_URL, '/') ?>';</script>
</head>
<body class="<?= e($bodyClass) ?>">

<?php if (!empty($user)): ?>
<header class="app-header">
    <div class="container header-inner">
        <a href="<?= $R ?>index.php" class="logo">
            Mboa237
            <?php if ($user['role'] !== 'apprenant'): ?>
                <span class="badge-role badge-<?= e($user['role']) ?>"><?= e(ucfirst($user['role'])) ?></span>
            <?php endif; ?>
        </a>
        <div class="user-badge">
            <span class="user-name-display"><?= e($user['nom']) ?></span>
            <div class="user-avatar"><?= e(mb_strtoupper(mb_substr($user['nom'], 0, 2))) ?></div>
            <a href="<?= $R ?>deconnexion.php" class="btn-logout">Déconnexion</a>
        </div>
    </div>
</header>
<?php else: ?>
<header class="app-header">
    <div class="container header-inner">
        <a href="<?= $R ?>index.php" class="logo">Mboa237</a>
        <nav style="display:flex;gap:1.5rem;align-items:center;">
            <a href="<?= $R ?>connexion.php" style="font-weight:400;">Connexion</a>
            <a href="<?= $R ?>connexion.php#inscription" class="btn btn-primary"
               style="padding:.5rem 1.3rem;font-size:.9rem;">S'inscrire</a>
        </nav>
    </div>
</header>
<?php endif; ?>

<?php if ($flash): echo '<div class="container" style="padding-top:1rem;">' . $flash . '</div>'; endif; ?>

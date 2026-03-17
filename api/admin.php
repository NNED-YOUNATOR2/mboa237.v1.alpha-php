<?php
// api/admin.php — Stats et actions admin via AJAX
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || currentRole() !== 'admin') {
    jsonResponse(['erreur' => 'Accès refusé'], 403);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'stats':
        jsonResponse(getAdminStats());
        break;

    case 'utilisateurs':
        jsonResponse(['utilisateurs' => getAllUsers()]);
        break;

    case 'valider':
        $id = (int)($_POST['id'] ?? 0);
        if ($id) { updateCoursStatut($id, 'publie'); jsonResponse(['ok' => true]); }
        jsonResponse(['erreur' => 'ID manquant'], 400);
        break;

    case 'rejeter':
        $id = (int)($_POST['id'] ?? 0);
        if ($id) { deleteCours($id); jsonResponse(['ok' => true]); }
        jsonResponse(['erreur' => 'ID manquant'], 400);
        break;

    default:
        jsonResponse(['erreur' => 'Action inconnue'], 400);
}

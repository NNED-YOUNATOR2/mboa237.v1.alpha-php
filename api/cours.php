<?php
// api/cours.php — API REST pour les cours
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['erreur' => 'Non authentifié'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$user   = currentUser();

// GET /api/cours.php — liste des cours avec progression
if ($method === 'GET') {
    $langue = $_GET['langue'] ?? null;
    $cours  = getCoursWithProgression($user['id'], $langue ?: null);
    jsonResponse(['cours' => $cours]);
}

// POST /api/cours.php — créer un cours (admin/créateur)
if ($method === 'POST') {
    if (!in_array($user['role'], ['admin','createur'])) {
        jsonResponse(['erreur' => 'Accès refusé'], 403);
    }
    $data   = json_decode(file_get_contents('php://input'), true) ?? [];
    $titre  = trim($data['titre']  ?? '');
    $langue = trim($data['langue'] ?? '');
    if (!$titre || !$langue) jsonResponse(['erreur' => 'Titre et langue requis'], 400);

    $statut  = $user['role'] === 'admin' ? ($data['statut'] ?? 'publie') : 'en_attente';
    $id = createCours([
        'titre'       => $titre,
        'langue'      => $langue,
        'niveau'      => (int)($data['niveau'] ?? 1),
        'description' => $data['description'] ?? '',
        'statut'      => $statut,
        'createur_id' => $user['id'],
    ]);
    jsonResponse(['ok' => true, 'id' => $id], 201);
}

// DELETE /api/cours.php?id=X — supprimer (admin seulement)
if ($method === 'DELETE') {
    if ($user['role'] !== 'admin') jsonResponse(['erreur' => 'Accès refusé'], 403);
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['erreur' => 'ID manquant'], 400);
    deleteCours($id);
    jsonResponse(['ok' => true]);
}

jsonResponse(['erreur' => 'Méthode non supportée'], 405);

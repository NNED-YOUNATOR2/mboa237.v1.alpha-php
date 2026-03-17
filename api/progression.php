<?php
// api/progression.php — Sauvegarder/lire la progression
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) jsonResponse(['erreur' => 'Non authentifié'], 401);

$user   = currentUser();
$method = $_SERVER['REQUEST_METHOD'];

// GET — progression globale
if ($method === 'GET') {
    $prog = getProgressionGlobale($user['id']);
    jsonResponse($prog);
}

// POST — mettre à jour
if ($method === 'POST') {
    $data    = json_decode(file_get_contents('php://input'), true) ?? [];
    $coursId = (int)($data['cours_id'] ?? 0);
    $valeur  = min(100, max(0, (int)($data['progression'] ?? 0)));
    if (!$coursId) jsonResponse(['erreur' => 'cours_id manquant'], 400);
    updateProgression($user['id'], $coursId, $valeur);
    jsonResponse(['ok' => true, 'progression' => $valeur]);
}

jsonResponse(['erreur' => 'Méthode non supportée'], 405);

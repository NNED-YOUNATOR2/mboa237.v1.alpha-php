<?php
// ============================================================
//  includes/functions.php — Fonctions métier partagées
// ============================================================

require_once __DIR__ . '/../config/db.php';

// ── COURS ────────────────────────────────────────────────────

function getCoursList(string $statut = 'publie', ?string $langue = null): array {
    $pdo  = getPDO();
    $sql  = 'SELECT * FROM cours WHERE statut = ?';
    $args = [$statut];
    if ($langue) { $sql .= ' AND langue = ?'; $args[] = $langue; }
    $sql .= ' ORDER BY niveau ASC, created_at DESC';
    return $pdo->prepare($sql)->execute($args) ? $pdo->prepare($sql)->fetchAll() :
           (function() use ($sql, $args, $pdo) {
               $s = $pdo->prepare($sql); $s->execute($args); return $s->fetchAll();
           })();
}

function getCoursWithProgression(int $userId, ?string $langue = null): array {
    $pdo  = getPDO();
    $sql  = '
        SELECT c.*, COALESCE(p.progression, 0) AS progression
        FROM cours c
        LEFT JOIN progressions p ON p.cours_id = c.id AND p.user_id = ?
        WHERE c.statut = ?';
    $args = [$userId, 'publie'];
    if ($langue) { $sql .= ' AND c.langue = ?'; $args[] = $langue; }
    $sql .= ' ORDER BY c.niveau ASC, c.id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

function getCoursById(int $id): ?array {
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM cours WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getLeconsByCours(int $coursId): array {
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM lecons WHERE cours_id = ? ORDER BY ordre ASC');
    $stmt->execute([$coursId]);
    $rows = $stmt->fetchAll();
    // Décoder JSON contenu
    foreach ($rows as &$row) {
        if (is_string($row['contenu'])) {
            $row['contenu'] = json_decode($row['contenu'], true) ?? [];
        }
    }
    return $rows;
}

function createCours(array $data): int {
    $pdo  = getPDO();
    $stmt = $pdo->prepare(
        'INSERT INTO cours (titre, langue, niveau, description, statut, createur_id) VALUES (?,?,?,?,?,?)'
    );
    $stmt->execute([
        $data['titre'],
        $data['langue'],
        $data['niveau']      ?? 1,
        $data['description'] ?? '',
        $data['statut']      ?? 'en_attente',
        $data['createur_id'] ?? null,
    ]);
    return (int)$pdo->lastInsertId();
}

function deleteCours(int $id): void {
    $pdo = getPDO();
    $pdo->prepare('DELETE FROM cours WHERE id = ?')->execute([$id]);
}

function updateCoursStatut(int $id, string $statut): void {
    $pdo = getPDO();
    $pdo->prepare('UPDATE cours SET statut = ? WHERE id = ?')->execute([$statut, $id]);
}

// ── PROGRESSIONS ─────────────────────────────────────────────

function getProgression(int $userId, int $coursId): int {
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT progression FROM progressions WHERE user_id = ? AND cours_id = ?');
    $stmt->execute([$userId, $coursId]);
    return (int)($stmt->fetchColumn() ?: 0);
}

function updateProgression(int $userId, int $coursId, int $valeur): void {
    $pdo = getPDO();
    $pdo->prepare('
        INSERT INTO progressions (user_id, cours_id, progression)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE progression = GREATEST(progression, ?)
    ')->execute([$userId, $coursId, $valeur, $valeur]);
}

function getProgressionGlobale(int $userId): array {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("
        SELECT COUNT(c.id) AS total,
               COUNT(p.id) AS avec_prog,
               COALESCE(AVG(p.progression), 0) AS moy,
               SUM(CASE WHEN p.progression = 100 THEN 1 ELSE 0 END) AS terminees
        FROM cours c
        LEFT JOIN progressions p ON p.cours_id = c.id AND p.user_id = ?
        WHERE c.statut = 'publie'
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return [
        'total'     => (int)$row['total'],
        'terminees' => (int)$row['terminees'],
        'moyenne'   => (int)round($row['moy']),
    ];
}

// ── STATS ADMIN ───────────────────────────────────────────────

function getAdminStats(): array {
    $pdo = getPDO();
    $users   = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
    $publie  = $pdo->query("SELECT COUNT(*) FROM cours WHERE statut='publie'")->fetchColumn();
    $attente = $pdo->query("SELECT COUNT(*) FROM cours WHERE statut='en_attente'")->fetchColumn();
    $lecons  = $pdo->query("SELECT COUNT(*) FROM lecons")->fetchColumn();
    return [
        'utilisateurs' => (int)$users,
        'cours_publies'=> (int)$publie,
        'en_attente'   => (int)$attente,
        'lecons'       => (int)$lecons,
    ];
}

function getAllUsers(): array {
    return getPDO()->query('SELECT id, nom, email, role, langue, streak, created_at FROM utilisateurs ORDER BY created_at DESC')->fetchAll();
}

// ── RESSOURCES ────────────────────────────────────────────────

function getRessources(?string $type = null, ?string $search = null): array {
    $pdo  = getPDO();
    $sql  = 'SELECT * FROM ressources WHERE 1=1';
    $args = [];
    if ($type)   { $sql .= ' AND type = ?'; $args[] = $type; }
    if ($search) { $sql .= ' AND (titre LIKE ? OR langue LIKE ? OR description LIKE ?)';
                   $args[] = "%$search%"; $args[] = "%$search%"; $args[] = "%$search%"; }
    $sql .= ' ORDER BY langue, titre';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        if (is_string($row['contenu'])) {
            $row['contenu'] = json_decode($row['contenu'], true) ?? [];
        }
    }
    return $rows;
}

function getRessourceById(int $id): ?array {
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM ressources WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && is_string($row['contenu'])) {
        $row['contenu'] = json_decode($row['contenu'], true) ?? [];
    }
    return $row ?: null;
}

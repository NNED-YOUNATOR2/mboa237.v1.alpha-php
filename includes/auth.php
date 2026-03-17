<?php
// ============================================================
//  includes/auth.php — Fonctions auth (login, register, logout)
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

// ── Connexion ────────────────────────────────────────────────

function loginUser(string $email, string $password): array {
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['ok' => false, 'erreur' => 'Aucun compte avec cet email.'];
    }

    // Vérifier mot de passe (compatible avec les comptes demo)
    $validHash = password_verify($password, $user['mot_de_passe']);
    // Fallback pour les comptes demo si le hash ne correspond pas
    $validDemo = in_array($password, ['admin123','create123','apprend123'])
                 && in_array($email, ['admin@mboa237.com','createur@mboa237.com','apprenant@mboa237.com']);

    if (!$validHash && !$validDemo) {
        return ['ok' => false, 'erreur' => 'Mot de passe incorrect.'];
    }

    // Stocker en session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user']    = [
        'id'     => $user['id'],
        'nom'    => $user['nom'],
        'email'  => $user['email'],
        'role'   => $user['role'],
        'langue' => $user['langue'] ?? 'Ewondo',
        'niveau' => $user['niveau_pref'] ?? 'debutant',
        'streak' => $user['streak'] ?? 0,
    ];
    return ['ok' => true, 'user' => $_SESSION['user']];
}

// ── Inscription ──────────────────────────────────────────────

function registerUser(string $nom, string $email, string $password, string $role): array {
    $pdo = getPDO();

    // Vérifier email unique
    $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
    $stmt->execute([strtolower(trim($email))]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'erreur' => 'Cet email est déjà utilisé.'];
    }

    // Valider rôle
    $rolesValides = ['apprenant', 'createur'];
    if (!in_array($role, $rolesValides)) $role = 'apprenant';

    // Hasher le mot de passe
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        'INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([trim($nom), strtolower(trim($email)), $hash, $role]);
    $id = $pdo->lastInsertId();

    // Auto-connexion
    $_SESSION['user_id'] = $id;
    $_SESSION['user']    = [
        'id'     => $id,
        'nom'    => trim($nom),
        'email'  => strtolower(trim($email)),
        'role'   => $role,
        'langue' => 'Ewondo',
        'niveau' => 'debutant',
        'streak' => 0,
    ];
    return ['ok' => true, 'user' => $_SESSION['user']];
}

// ── Déconnexion ──────────────────────────────────────────────

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

// ── Mise à jour profil ───────────────────────────────────────

function updateUserLangue(int $userId, string $langue): void {
    $pdo = getPDO();
    $pdo->prepare('UPDATE utilisateurs SET langue = ? WHERE id = ?')
        ->execute([$langue, $userId]);
    if (isset($_SESSION['user'])) $_SESSION['user']['langue'] = $langue;
}

function updateUserNiveau(int $userId, string $niveau): void {
    $pdo = getPDO();
    $pdo->prepare('UPDATE utilisateurs SET niveau_pref = ? WHERE id = ?')
        ->execute([$niveau, $userId]);
    if (isset($_SESSION['user'])) $_SESSION['user']['niveau'] = $niveau;
}

function updateStreak(int $userId): int {
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT streak, last_eq_date FROM utilisateurs WHERE id = ?');
    $stmt->execute([$userId]);
    $row  = $stmt->fetch();

    $today     = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $streak    = (int)($row['streak'] ?? 0);
    $lastDate  = $row['last_eq_date'];

    if ($lastDate === $today) return $streak; // Déjà fait aujourd'hui

    $newStreak = ($lastDate === $yesterday) ? $streak + 1 : 1;
    $pdo->prepare('UPDATE utilisateurs SET streak = ?, last_eq_date = ? WHERE id = ?')
        ->execute([$newStreak, $today, $userId]);
    if (isset($_SESSION['user'])) $_SESSION['user']['streak'] = $newStreak;
    return $newStreak;
}

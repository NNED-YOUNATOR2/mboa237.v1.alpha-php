<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth.php';

redirectIfLoggedIn();

$erreurCx  = '';
$erreurIns = '';
$onglet    = isset($_GET['tab']) && $_GET['tab'] === 'inscription' ? 'inscription' : 'connexion';

// ── Traitement POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action_connexion'])) {
        $email = trim($_POST['email'] ?? '');
        $pwd   = $_POST['password'] ?? '';

        if (!$email || !$pwd) {
            $erreurCx = 'Veuillez remplir tous les champs.';
            $onglet   = 'connexion';
        } else {
            $result = loginUser($email, $pwd);
            if (!$result['ok']) {
                $erreurCx = $result['erreur'];
                $onglet   = 'connexion';
            } else {
                $role = $result['user']['role'];
                $routes = [
                    'admin'    => BASE_URL . '/admin/dashboard.php',
                    'createur' => BASE_URL . '/createur/dashboard.php',
                    'apprenant'=> BASE_URL . '/apprenant/dashboard.php',
                ];
                redirect($routes[$role] ?? BASE_URL . '/index.php');
            }
        }
    }

    if (isset($_POST['action_inscription'])) {
        $nom     = trim($_POST['nom']     ?? '');
        $email   = trim($_POST['email']   ?? '');
        $role    = $_POST['role']          ?? 'apprenant';
        $pwd     = $_POST['password']      ?? '';
        $confirm = $_POST['confirm']       ?? '';
        $onglet  = 'inscription';

        if (!$nom || !$email || !$pwd || !$confirm) {
            $erreurIns = 'Tous les champs sont obligatoires.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurIns = 'Format d\'email invalide.';
        } elseif (strlen($pwd) < 8) {
            $erreurIns = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif ($pwd !== $confirm) {
            $erreurIns = 'Les mots de passe ne correspondent pas.';
        } else {
            $result = registerUser($nom, $email, $pwd, $role);
            if (!$result['ok']) {
                $erreurIns = $result['erreur'];
            } else {
                $routes = [
                    'admin'    => BASE_URL . '/admin/dashboard.php',
                    'createur' => BASE_URL . '/createur/dashboard.php',
                    'apprenant'=> BASE_URL . '/apprenant/selection-langue.php',
                ];
                redirect($routes[$result['user']['role']] ?? BASE_URL . '/index.php');
            }
        }
    }
}

$pageTitle = 'Connexion / Inscription';
$bodyClass = 'auth-body';
$rootPath  = '';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
    <div class="auth-logo">
        <a href="index.php" class="logo">Mboa237</a>
        <p class="auth-tagline">Retrouvez le chemin de vos racines</p>
    </div>

    <div class="auth-card">
        <div class="tabs">
            <button class="tab <?= $onglet==='connexion' ? 'active':'' ?>"
                    onclick="switchTab('connexion')">Connexion</button>
            <button class="tab <?= $onglet==='inscription' ? 'active':'' ?>"
                    onclick="switchTab('inscription')">Inscription</button>
        </div>

        <div class="tab-body">

            <!-- ── CONNEXION ── -->
            <div class="panel <?= $onglet==='connexion' ? 'active':'' ?>" id="panel-connexion">
                <form method="POST" action="connexion.php" novalidate>
                    <input type="hidden" name="action_connexion" value="1">

                    <?php if ($erreurCx): ?>
                        <div class="err-msg visible"><?= e($erreurCx) ?></div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="cx-email">Adresse e-mail</label>
                        <input type="email" id="cx-email" name="email"
                               value="<?= e($_POST['email'] ?? '') ?>"
                               placeholder="exemple@domaine.com" required>
                    </div>

                    <div class="form-group">
                        <label for="cx-pwd">Mot de passe</label>
                        <div class="password-wrap">
                            <input type="password" id="cx-pwd" name="password"
                                   placeholder="••••••••" required>
                            <button type="button" class="toggle-pwd" aria-label="Voir le mot de passe">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="forgot-link"><a href="#">Mot de passe oublié ?</a></div>

                    <button type="submit" class="btn btn-primary btn-full">Se connecter</button>

                    <div class="demo-hint">
                        <p><i class="fas fa-info-circle"></i> Comptes de démo :</p>
                        <div class="demo-chips">
                            <span class="chip" onclick="remplirDemo('admin@mboa237.com','admin123')">Admin</span>
                            <span class="chip" onclick="remplirDemo('createur@mboa237.com','create123')">Créateur</span>
                            <span class="chip" onclick="remplirDemo('apprenant@mboa237.com','apprend123')">Apprenant</span>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ── INSCRIPTION ── -->
            <div class="panel <?= $onglet==='inscription' ? 'active':'' ?>" id="panel-inscription">
                <form method="POST" action="connexion.php#inscription" novalidate>
                    <input type="hidden" name="action_inscription" value="1">

                    <?php if ($erreurIns): ?>
                        <div class="err-msg visible"><?= e($erreurIns) ?></div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="ins-nom">Nom complet</label>
                        <input type="text" id="ins-nom" name="nom"
                               value="<?= e($_POST['nom'] ?? '') ?>"
                               placeholder="Jean Kouamé" required>
                    </div>

                    <div class="form-group">
                        <label for="ins-email">Adresse e-mail</label>
                        <input type="email" id="ins-email" name="email"
                               value="<?= e($_POST['email'] ?? '') ?>"
                               placeholder="exemple@domaine.com" required>
                    </div>

                    <div class="form-group">
                        <label for="ins-role">Je suis</label>
                        <select id="ins-role" name="role">
                            <option value="apprenant">Apprenant — je veux apprendre une langue</option>
                            <option value="createur">Créateur de contenu — je crée des cours</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="ins-pwd">Mot de passe</label>
                        <div class="password-wrap">
                            <input type="password" id="ins-pwd" name="password"
                                   placeholder="Minimum 8 caractères" required>
                            <button type="button" class="toggle-pwd" aria-label="Voir le mot de passe">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="pwd-strength">
                            <div class="strength-bar"><div class="strength-fill" id="s-fill"></div></div>
                            <span class="strength-label" id="s-label"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ins-confirm">Confirmer le mot de passe</label>
                        <div class="password-wrap">
                            <input type="password" id="ins-confirm" name="confirm"
                                   placeholder="Confirmez votre mot de passe" required>
                            <button type="button" class="toggle-pwd" aria-label="Voir le mot de passe">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">Créer mon compte</button>
                    <p class="legal-note">En vous inscrivant, vous acceptez nos <a href="#">conditions générales</a>.</p>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.auth-body{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:2rem;}
.auth-wrap{width:100%;max-width:460px;}
.auth-logo{text-align:center;margin-bottom:1.8rem;}
.auth-logo .logo{font-size:2.2rem;display:block;margin-bottom:.3rem;}
.auth-tagline{font-size:.9rem;color:#7b6e64;font-weight:300;}
.auth-card{background:white;border:1px solid var(--bordure);border-radius:16px;overflow:hidden;box-shadow:0 12px 35px rgba(0,0,0,.06);}
.tabs{display:flex;border-bottom:1px solid var(--bordure);background:#fafaf8;}
.tab{flex:1;padding:1.1rem .5rem;text-align:center;font-size:1rem;font-weight:500;color:var(--texte);opacity:.55;cursor:pointer;background:none;border:none;border-bottom:3px solid transparent;font-family:'Roboto',sans-serif;transition:var(--tr);}
.tab.active{opacity:1;border-bottom-color:var(--primaire);color:var(--primaire);}
.tab-body{padding:1.8rem 1.8rem 2rem;}
.panel{display:none;animation:fadeSlide .2s ease;}
.panel.active{display:block;}
@keyframes fadeSlide{from{opacity:.2;transform:translateY(6px)}to{opacity:1;transform:none}}
.btn-full{width:100%;justify-content:center;padding:.9rem;font-size:1rem;border-radius:40px;margin-top:.4rem;display:flex;}
.forgot-link{text-align:right;margin-bottom:1.1rem;}
.forgot-link a{font-size:.85rem;color:var(--secondaire);}
.err-msg{color:#b34a4a;font-size:.83rem;margin-bottom:.8rem;display:none;}
.err-msg.visible{display:block;}
.pwd-strength{display:flex;align-items:center;gap:.6rem;margin-top:.4rem;}
.strength-bar{flex:1;height:4px;background:var(--bordure);border-radius:4px;overflow:hidden;}
.strength-fill{height:100%;width:0;border-radius:4px;transition:width .3s,background .3s;}
.strength-label{font-size:.75rem;color:#7b6e64;white-space:nowrap;min-width:60px;}
.demo-hint{margin-top:1.4rem;padding:1rem;background:#f9f7f4;border-radius:8px;border:1px solid var(--bordure);}
.demo-hint p{font-size:.82rem;color:#7b6e64;margin-bottom:.6rem;}
.demo-chips{display:flex;gap:.5rem;flex-wrap:wrap;}
.chip{padding:.3rem .8rem;background:white;border:1px solid var(--bordure);border-radius:20px;font-size:.8rem;cursor:pointer;transition:var(--tr);}
.chip:hover{background:var(--primaire);color:white;border-color:var(--primaire);}
.legal-note{text-align:center;font-size:.8rem;color:#8c8279;margin-top:1.2rem;}
.legal-note a{color:var(--primaire);}
</style>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    document.querySelector(`.tab:nth-child(${tab==='connexion'?1:2})`).classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
}
function remplirDemo(email, pwd) {
    document.getElementById('cx-email').value = email;
    document.getElementById('cx-pwd').value   = pwd;
    switchTab('connexion');
}
// Ancre #inscription
if (window.location.hash === '#inscription') switchTab('inscription');

// Force mot de passe
document.addEventListener('DOMContentLoaded', () => {
    initPasswordStrength('ins-pwd', 's-fill', 's-label');
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

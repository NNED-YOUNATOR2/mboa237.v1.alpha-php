<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Rediriger si déjà connecté
redirectIfLoggedIn();

$pageTitle = 'Préservez votre héritage';
$rootPath  = '';
include __DIR__ . '/includes/header.php';
?>

<main style="flex:1;">

    <!-- HERO -->
    <section class="hero">
        <div class="container">
            <h1>Préservez votre héritage, parlez votre langue.</h1>
            <p>Un apprentissage immersif et respectueux des traditions camerounaises. Retrouvez le chemin de vos racines.</p>
            <div class="hero-btns">
                <a href="connexion.php#inscription" class="btn btn-primary">Commencer gratuitement</a>
                <a href="#langues" class="btn btn-secondary">Voir les langues</a>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="features">
        <div class="container">
            <h2>Une méthode pensée pour durer</h2>
            <div class="feat-grid">
                <div class="feat-card">
                    <div class="feat-icon"><i class="fas fa-layer-group"></i></div>
                    <h3>Apprentissage par niveaux</h3>
                    <p>Progression fluide du débutant à l'aisance orale avec des paliers clairs.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon"><i class="fas fa-microphone-alt"></i></div>
                    <h3>Pratique orale</h3>
                    <p>Reconnaissance vocale et exercices d'écoute pour maîtriser sons et tons.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon"><i class="fas fa-book-open"></i></div>
                    <h3>Culture & Proverbes</h3>
                    <p>Contes, proverbes et expressions pour une immersion totale.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon"><i class="fas fa-users"></i></div>
                    <h3>Créateurs natifs</h3>
                    <p>Des locuteurs natifs créent et valident chaque leçon.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- LANGUES -->
    <section class="langues" id="langues">
        <div class="container">
            <h2>Langues disponibles</h2>
            <div class="lang-grid">
                <?php
                $langues = [
                    ['nom'=>'Ewondo',   'region'=>'Fang-Béti · Centre'],
                    ['nom'=>'Duala',    'region'=>'Sawa · Littoral'],
                    ['nom'=>'Fulfulde', 'region'=>'Adamaoua · Nord'],
                    ['nom'=>'Féfé',     'region'=>'Grassfields · Ouest'],
                    ['nom'=>'Basaa',    'region'=>'Littoral · Centre'],
                    ['nom'=>'Ghomala', 'region'=>'Grassfields · Ouest'],
                ];
                foreach ($langues as $l): ?>
                <div class="lang-pill">
                    <?= e($l['nom']) ?>
                    <span><?= e($l['region']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-bottom">
        <div class="container">
            <h2>Prêt à commencer ?</h2>
            <p>Rejoignez la communauté Mboa237 et préservez votre héritage linguistique.</p>
            <a href="connexion.php#inscription" class="btn btn-primary">Créer mon compte</a>

            <div style="margin-top:2rem; display:inline-block; text-align:left; max-width:420px; width:100%;">
                <div class="demo-box">
                    <h4><i class="fas fa-flask"></i> Comptes de démonstration</h4>
                    <div class="demo-row"><span>Admin</span><span>admin@mboa237.com / admin123</span></div>
                    <div class="demo-row"><span>Créateur</span><span>createur@mboa237.com / create123</span></div>
                    <div class="demo-row"><span>Apprenant</span><span>apprenant@mboa237.com / apprend123</span></div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
body{display:flex;flex-direction:column;}
.hero{padding:6rem 0 5rem;text-align:center;}
.hero h1{max-width:800px;margin:0 auto 1.5rem;}
.hero p{font-size:1.15rem;max-width:560px;margin:0 auto 2.5rem;color:#5a4e44;}
.hero-btns{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;}
.features{padding:4rem 0 5rem;background:rgba(255,255,255,.5);border-top:1px solid var(--bordure);border-bottom:1px solid var(--bordure);}
.features h2{text-align:center;margin-bottom:2.5rem;}
.feat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:2rem;}
.feat-card{text-align:center;padding:2rem 1.5rem;background:white;border-radius:var(--radius);border:1px solid var(--bordure);transition:var(--tr);}
.feat-card:hover{transform:translateY(-6px);border-color:var(--accent);}
.feat-icon{font-size:2.4rem;color:var(--primaire);margin-bottom:1.1rem;}
.feat-card h3{margin-bottom:.5rem;}
.feat-card p{color:#5f554b;font-size:.93rem;margin:0;}
.langues{padding:4rem 0;}
.langues h2{text-align:center;margin-bottom:2rem;}
.lang-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1.2rem;}
.lang-pill{text-align:center;padding:1.4rem 1rem;background:white;border-radius:var(--radius);border:1px solid var(--bordure);font-family:'Playfair Display',serif;font-size:1.05rem;font-weight:600;transition:var(--tr);}
.lang-pill:hover{background:var(--secondaire);color:white;border-color:var(--secondaire);transform:scale(1.04);}
.lang-pill span{display:block;font-family:'Roboto',sans-serif;font-size:.78rem;font-weight:300;margin-top:.25rem;color:#7b6e64;}
.lang-pill:hover span{color:rgba(255,255,255,.8);}
.cta-bottom{padding:4rem 0;text-align:center;background:var(--texte);color:white;}
.cta-bottom h2{color:white;margin-bottom:1rem;}
.cta-bottom p{color:#c0b5ad;margin-bottom:2rem;}
.demo-box{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:var(--radius);padding:1.2rem 1.5rem;}
.demo-box h4{color:var(--accent);font-family:'Playfair Display',serif;margin-bottom:.8rem;}
.demo-row{display:flex;justify-content:space-between;padding:.3rem 0;font-size:.85rem;border-bottom:1px solid rgba(255,255,255,.08);gap:1rem;}
.demo-row:last-child{border:none;}
.demo-row span:first-child{color:#c0b5ad;}
.demo-row span:last-child{color:white;font-weight:500;}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>

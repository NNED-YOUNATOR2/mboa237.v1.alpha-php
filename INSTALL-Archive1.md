# Archive 1 — Socle PHP (Base de données + Configuration)

## Contenu de cette archive

```
mboa237/
├── .htaccess               ← Sécurité et réécriture d'URL
├── database.sql            ← Script de création de la BD
├── config/
│   ├── db.php              ← Connexion MySQL (PDO)
│   └── session.php         ← Gestion des sessions PHP
└── includes/
    ├── header.php          ← En-tête HTML commun
    ├── footer.php          ← Pied de page commun
    ├── navbar.php          ← Barre de navigation
    └── functions.php       ← Fonctions utilitaires
```

---

## Installation étape par étape

### Étape 1 — Copier le dossier dans WAMP

Copiez le dossier `mboa237` dans :
```
C:\wamp64\www\mboa237\
```
(ou `C:\wamp\www\mboa237\` selon votre version de WAMP)

### Étape 2 — Démarrer WAMP

Lancez WAMP Server. Attendez que l'icône devienne **verte** dans la barre des tâches.

### Étape 3 — Créer la base de données

1. Ouvrez votre navigateur et allez sur : `http://localhost/phpmyadmin`
2. Connectez-vous (utilisateur : `root`, mot de passe : vide par défaut)
3. Cliquez sur **"Importer"** dans le menu du haut
4. Cliquez **"Choisir un fichier"** → sélectionnez `mboa237/database.sql`
5. Cliquez **"Exécuter"**
6. Vous devriez voir le message : *"Base de données Mboa237 créée avec succès !"*

### Étape 4 — Vérifier la configuration

Ouvrez `config/db.php` et vérifiez :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mboa237');
define('DB_USER', 'root');
define('DB_PASS', '');          // Laissez vide si WAMP n'a pas de mot de passe
define('BASE_URL', 'http://localhost/mboa237');
```

Si votre WAMP a un mot de passe MySQL, modifiez `DB_PASS`.

---

## Comptes créés automatiquement

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@mboa237.com | admin123 |
| Créateur | createur@mboa237.com | create123 |
| Apprenant | apprenant@mboa237.com | apprend123 |

---

## Prochaine étape

Attendez l'**Archive 2** (Pages publiques : accueil, connexion, inscription) avant de tester.

La version finale sera accessible sur : `http://localhost/mboa237`

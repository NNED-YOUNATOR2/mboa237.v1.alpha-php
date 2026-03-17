# Archive 1 — Socle PHP/MySQL
## Instructions d'installation (WAMP)

### Étape 1 — Copier le dossier
Copiez le dossier `Mboa237-PHP` dans :
```
C:/wamp64/www/mboa237/
```
Résultat final :
```
C:/wamp64/www/mboa237/
  ├── config/
  │   ├── db.php
  │   └── session.php
  ├── includes/
  │   ├── auth.php
  │   ├── functions.php
  │   ├── header.php
  │   └── footer.php
  ├── assets/
  │   ├── css/   (vide pour l'instant)
  │   └── js/
  │       └── app.js
  └── database.sql
```

### Étape 2 — Créer la base de données
1. Démarrez WAMP (icône verte dans la barre des tâches)
2. Ouvrez votre navigateur → `http://localhost/phpmyadmin`
3. Cliquez sur **"Importer"** (onglet en haut)
4. Cliquez **"Choisir un fichier"** → sélectionnez `database.sql`
5. Cliquez **"Exécuter"**
6. Vérifiez que la BD `mboa237` apparaît à gauche avec 6 tables

### Étape 3 — Vérifier la config
Ouvrez `config/db.php` et vérifiez :
- `DB_HOST` = `'localhost'` ✅
- `DB_USER` = `'root'` ✅
- `DB_PASS` = `''` (vide par défaut sur WAMP) ✅
- `DB_NAME` = `'mboa237'` ✅

### Étape 4 — Tester
Ouvrez `http://localhost/mboa237/` dans votre navigateur.
(La page d'accueil sera disponible après l'Archive 2)

### Comptes de démonstration
| Rôle      | Email                    | Mot de passe |
|-----------|--------------------------|--------------|
| Admin     | admin@mboa237.com        | admin123     |
| Créateur  | createur@mboa237.com     | create123    |
| Apprenant | apprenant@mboa237.com    | apprend123   |

### Structure finale attendue (après toutes les archives)
```
C:/wamp64/www/mboa237/
  ├── config/           ← Archive 1
  ├── includes/         ← Archive 1
  ├── assets/css/       ← Archive 6
  ├── assets/js/        ← Archive 1 + 6
  ├── index.php         ← Archive 2
  ├── connexion.php     ← Archive 2
  ├── deconnexion.php   ← Archive 2
  ├── apprenant/        ← Archives 3 & 4
  ├── admin/            ← Archive 5
  ├── createur/         ← Archive 5
  └── api/              ← Archive 6
```

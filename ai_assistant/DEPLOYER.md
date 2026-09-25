# Guide de Déploiement — Assistant PPS

> Transférer et exécuter l'application sur un autre PC Windows

---

## 1. Copier le projet

Depuis le PC source, copier le dossier `ai_assistant/` vers le PC cible.

**Dossiers à copier (obligatoires) :**

```
ai_assistant/
├── backend/                  ← API Python (tout)
├── laravel/                  ← Interface web (sans vendor/)
│   ├── .env.example
│   └── ...
├── DOCUMENTATION.txt
├── ARCHITECTURE.md
├── PRESENTATION.md
└── DEPLOYER.md
```

**Dossiers à NE PAS copier :** `venv/`, `laravel/vendor/`, `laravel/node_modules/`, `__pycache__/`

---

## 2. Installer l'application — `install.bat` (1 clic)

Le fichier `install.bat` se trouve à la racine `GEMINIE/` (le dossier parent de `ai_assistant/`).

```
GEMINIE/
├── install.bat               ← Double-clic !
├── setup_env.bat             ← Alternative rapide
├── lancer_morad.bat          ← Pour lancer après installation
└── ai_assistant/
    └── ...
```

**Double-clic sur `install.bat`** → il fait tout automatiquement :

| Étape | Action |
|-------|--------|
| 1 | Vérifie/installe **PHP 8.3** (via winget) |
| 2 | Vérifie/installe **Python 3.12** (via winget) |
| 3 | Crée l'environnement virtuel `venv/` |
| 4 | Installe les packages Python (`pip install`) |
| 5 | Vérifie/télécharge **Composer** si absent |
| 6 | Installe les dépendances Laravel (`composer install`) |
| 7 | Génère la clé de sécurité (`APP_KEY`) |
| 8 | Crée la base utilisateurs avec les comptes (`migrate --seed`) |

> ⏱ L'opération prend 2 à 5 minutes selon votre connexion Internet.
> Une fois terminée, il ne reste qu'à **configurer l'adresse IP** (étape suivante).

---

## 3. Adresse IP — rien à faire !

L'application détecte **automatiquement** votre adresse IP à chaque démarrage.

**Vous n'avez rien à modifier :**
- Le fichier `.env` garde `127.0.0.1` (fonctionne toujours en local)
- Le fichier `lancer_morad.bat` détecte l'IP du réseau automatiquement

**Quand le réseau change** (ex: vous passez du bureau à la maison) :
1. L'IP change automatiquement → `lancer_morad.bat` la détecte tout seul
2. Vous n'avez **aucun fichier à modifier**
3. Lancez simplement `lancer_morad.bat` comme d'habitude

**Pour partager l'adresse avec un collègue :**
- Lancez `lancer_morad.bat`
- Regardez la ligne **"Interface reseau"** dans la fenêtre noire
- Donnez cette adresse à vos collègues (ex: `http://192.168.0.50:8080`)

> L'application fonctionne toujours en local sur votre PC via `http://127.0.0.1:8080`, quel que soit le réseau.

---

## 4. Alternative sans configuration IP (usage local uniquement)

Si vous voulez juste tester l'application **sur le même PC** (sans partage réseau) :

1. Ouvrez `lancer_morad.bat` avec le Bloc-notes
2. Remplacez les 3 occurrences de `192.168.0.139` par **`127.0.0.1`**
3. Dans `laravel/.env`, mettez `APP_URL=http://127.0.0.1:8080`

> Avec `127.0.0.1`, seuls les utilisateurs de ce PC peuvent accéder à l'application. Pour partager avec d'autres PC, vous devez utiliser votre vraie IP (192.168.X.X).

---

## 5. Ouvrir les ports du Pare-feu (pour le partage réseau)

### Qu'est-ce qu'un port ?

Un **port** est comme une **porte** sur votre PC :
- **Port 8080** → la porte du **site web** (Laravel) — vos collègues y voient le tableau de bord, les employés, le chat
- **Port 8000** → la porte de **l'API** (FastAPI) — le site web utilise cette porte en coulisse pour interroger la base de données

Quand un autre PC tape `http://192.168.0.50:8080`, il frappe à la **porte 8080**. Windows bloque par défaut. Il faut lui dire "laisse passer".

Le pare-feu Windows bloque les connexions entrantes par sécurité. Pour autoriser les autres PC à accéder à l'application :

**Méthode graphique (recommandée pour débutants) :**

1. Ouvrez le **Panneau de configuration**
2. Cliquez sur **Système et sécurité** → **Pare-feu Windows Defender**
3. Cliquez sur **Paramètres avancés** (dans le menu de gauche)
4. Dans la fenêtre qui s'ouvre, cliquez sur **Règles entrantes** (menu gauche)
5. Cliquez sur **Nouvelle règle...** (menu droit)
6. Choisissez **Port** → Suivant
7. Choisissez **TCP**, puis dans "Ports locaux spécifiques" tapez : `8000, 8080` → Suivant
8. Choisissez **Autoriser la connexion** → Suivant
9. Cochez les 3 profils (Domaine, Privé, Public) → Suivant
10. Donnez un nom : `Assistant PPS` → Terminer

**Méthode PowerShell (si vous préférez les commandes) :**

Faites un clic droit sur **Démarrer** → **Windows PowerShell (Admin)** → tapez ces 2 commandes :

```powershell
New-NetFirewallRule -DisplayName "Assistant API 8000" -Direction Inbound -Protocol TCP -LocalPort 8000 -Action Allow
New-NetFirewallRule -DisplayName "Assistant Web 8080" -Direction Inbound -Protocol TCP -LocalPort 8080 -Action Allow
```

> ⚠ **Important :** Le pare-feu ne se configure qu'**une seule fois**. Si vous changez de réseau (maison → entreprise), les règles restent actives.

---

## 6. Lancer l'application

**Double-clic sur `lancer_morad.bat`.**

La fenêtre affiche les adresses à utiliser :

```
Interface locale  : http://127.0.0.1:8080     ← pour vous
Interface reseau  : http://192.168.0.50:8080   ← pour les autres PC
```

Une fenêtre noire s'ouvre avec les messages de démarrage. Ne la fermez pas !

| Pour accéder depuis... | Tapez dans le navigateur |
|------------------------|--------------------------|
| **Ce PC** | `http://127.0.0.1:8080` (marche toujours, quel que soit le réseau) |
| **Un autre PC** | l'adresse affichée dans la fenêtre sous "Interface reseau" |

---

## 7. Comptes de connexion

| Nom d'utilisateur | Mot de passe | Rôle |
|-------------------|-------------|------|
| `ADMIN` | MK2026 | Accès complet (Dashboard, Employés, PPS, Chat) |
| `MKTANGER` | MK2026 | Employés uniquement |

---

## 8. Mise à jour des données (Excel → SQLite)

Le fichier `recap.xlsx` est la **source unique** des données. Pour ajouter ou modifier un employé :

1. Ouvrez le dossier `ai_assistant/backend/data/`
2. Ouvrez le fichier **`recap.xlsx`** avec Excel
3. Faites vos modifications (ajout d'une ligne, modification d'une date, etc.)
4. Sauvegardez le fichier (Ctrl+S)
5. **Double-clic sur `refresh_Excel_sqlite.bat`** (dans le même dossier)
6. Une fenêtre noire s'ouvre, laisse-la faire (cela prend 2-3 secondes)
7. Appuyez sur une touche pour fermer
8. Relancez `lancer_morad.bat`

**Le script `refresh_Excel_sqlite.bat` est portable** : il trouve automatiquement Python (via `venv/` ou le système).

---

## 9. Changer le nom d'utilisateur ou le mot de passe

Méthode : modifier le fichier seed + réinitialiser la base.

### 9.1. Étape 1 — Changer le nom (ex: ADMIN → SAID)

1. Ouvre `laravel/database/seeders/DatabaseSeeder.php`
2. Remplace `'name' => 'ADMIN'` par `'name' => 'SAID'`
3. Sauvegarde (Ctrl+S)
4. Double-clic sur **`reset_users.bat`** (ou en PowerShell : `php artisan migrate:fresh --seed`)
5. Attends le message "[OK] Utilisateurs mis a jour !"

> Recommence pour `MKTANGER` → `ATELIER` si besoin.

### 9.2. Étape 2 — Changer le mot de passe (ex: MK2026 → MonNouveauMdp)

1. Ouvre le même fichier `DatabaseSeeder.php`
2. Remplace `'password' => 'MK2026'` par `'password' => 'MonNouveauMdp'`
3. Sauvegarde (Ctrl+S)
4. Double-clic sur **`reset_users.bat`**

> Le mot de passe est hashé automatiquement par Laravel via le Model User.

### 9.3. Pourquoi `migrate:fresh --seed` est nécessaire ?

- `migrate:fresh` = **supprime toutes les tables** et les **recrée** à partir des migrations
- `--seed` = exécute `DatabaseSeeder.php` qui insère les utilisateurs dans la table vide

Sans cette commande, modifier le fichier `.php` ne fait rien : le seed ne s'exécute qu'une seule fois lors de la création de la base. En relançant `migrate:fresh --seed`, on repart d'une base vierge et on réinsère les comptes avec les nouvelles valeurs.

---

## 10. Résumé des fichiers `.bat`

| Fichier | Où le trouver ? | Quand l'utiliser ? |
|---------|----------------|-------------------|
| **`install.bat`** | Dossier `GEMINIE/` | **1ère installation** sur un nouveau PC |
| **`setup_env.bat`** | Dossier `GEMINIE/` | Si Python + PHP sont déjà installés |
| **`lancer_morad.bat`** | Dossier `GEMINIE/` | **Tous les jours** pour lancer l'app |
| **`refresh_Excel_sqlite.bat`** | `ai_assistant/backend/data/` | Après chaque modification du fichier Excel |
| **`reset_users.bat`** | Dossier `GEMINIE/` | Après modification des noms/mots de passe dans `DatabaseSeeder.php` |

---

## 11. Dépannage

| Problème | Cause probable | Solution |
|----------|---------------|----------|
| La fenêtre noire se ferme tout de suite | PHP ou Python non installé | Lancer `install.bat` d'abord |
| Page blanche dans le navigateur | `vendor/` manquant | Lancer `install.bat` d'abord |
| Erreur 500 (message serveur) | Clé `APP_KEY` manquante | Lancer `install.bat` d'abord |
| Les autres PC voient "Page impossible à atteindre" | **Pare-feu** bloque les ports, OU **mauvaise IP** dans `.env` et `lancer_morad.bat` | Vérifier le pare-feu (étape 5) et l'IP (étape 3) |
| L'API ne répond pas (`localhost:8000`) | FastAPI n'a pas démarré | Relancer `lancer_morad.bat` |
| Les données Excel ne sont pas à jour | `refresh_Excel_sqlite.bat` pas encore lancé | Double-clic sur `refresh_Excel_sqlite.bat` |
| `winget` n'est pas reconnu | Windows 10/11 ancienne version | Installer Python et PHP manuellement depuis python.org et php.net |

---

*Document généré le 17 juin 2026 — Assistant PPS v1.0*

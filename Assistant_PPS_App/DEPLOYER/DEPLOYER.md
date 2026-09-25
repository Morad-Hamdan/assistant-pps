# Assistant PPS — Guide de Déploiement

## 1. Installation (1 seule fois)

**Double-clic sur `install.bat`**

| Étape | Action |
|-------|--------|
| 1 | Installe **PHP 8.3** (via winget) |
| 2 | Installe **Python 3.12** (via winget) |
| 3 | Crée l'environnement virtuel + packages |
| 4 | Installe les dépendances Laravel (Composer) |
| 5 | Crée la base de données + comptes |

> Prend 2 à 5 min selon votre connexion.

---

## 2. Lancer l'application (tous les jours)

**Double-clic sur `start_assistant.bat`**

La fenêtre affiche :
```
Interface locale  : http://127.0.0.1:8080    ← pour vous
Interface reseau  : http://192.168.0.X:8080   ← pour les autres PC
```

Donnez l'adresse réseau à vos collègues pour qu'ils accèdent depuis leur navigateur.

**Ne fermez pas la fenêtre noire** — elle arrête les serveurs.

---

## 3. Pare-feu (pour le partage réseau)

Pour que les autres PC puissent se connecter, autorisez les ports :

**Méthode rapide** — PowerShell (Admin) :
```powershell
New-NetFirewallRule -DisplayName "Assistant API" -Direction Inbound -Protocol TCP -LocalPort 8000 -Action Allow
New-NetFirewallRule -DisplayName "Assistant Web" -Direction Inbound -Protocol TCP -LocalPort 8080 -Action Allow
```

**Méthode visuelle** — Panneau de configuration → Pare-feu → Règles entrantes → Nouvelle règle → Port → TCP 8000, 8080 → Autoriser.

---

## 4. Comptes de connexion

| Utilisateur | Mot de passe | Rôle |
|-------------|-------------|------|
| `ADMIN` | MK2026 | Accès complet |
| `MKTANGER` | MK2026 | Employés uniquement |

---

## 5. Changer un utilisateur (nom ou mot de passe)

1. Ouvrir `laravel/database/seeders/DatabaseSeeder.php`
2. Modifier `'name' => 'ADMIN'` ou `'password' => 'MK2026'`
3. Sauvegarder (Ctrl+S)
4. Double-clic sur **`reset_users.bat`**

---

## 6. Mettre à jour les données (Excel → SQLite)

1. Ouvrir `backend/data/recap.xlsx` avec Excel
2. Modifier les données
3. Sauvegarder (Ctrl+S)
4. Double-clic sur **`refresh_Excel_sqlite.bat`**
5. Relancer `start_assistant.bat`

> **Changer l'emplacement du fichier** : modifier `backend/core/config.py` → ligne `EXCEL_FILE = "backend/data/recap.xlsx"`. Mettre votre chemin (ex: `R:\dossier\recap.xlsx`). Le `.bat` reste inchangé.

---

## 7. Résumé des fichiers `.bat`

| Fichier | Quand l'utiliser |
|---------|-----------------|
| `install.bat` | **1ère installation** sur un PC |
| `start_assistant.bat` | **Tous les jours** pour lancer l'app |
| `refresh_Excel_sqlite.bat` | Après chaque modification du fichier Excel |
| `reset_users.bat` | Après avoir changé un nom/mot de passe |

---

## 8. Dépannage

| Problème | Cause | Solution |
|----------|-------|----------|
| La fenêtre se ferme tout de suite | PHP/Python manquant | Lancer `install.bat` |
| Page blanche | `vendor/` manquant | Lancer `install.bat` |
| Les autres PCs ne peuvent pas se connecter | Pare-feu bloque les ports | Voir section 3 |
| `winget` introuvable | Windows trop vieux | Installer PHP et Python manuellement |

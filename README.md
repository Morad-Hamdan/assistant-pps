<div align="center">

# 🛡️ Assistant PPS

### *Plateforme de pilotage des Pratiques Professionnelles Sécurité*

**Architecture 3-tiers découplée · FastAPI + Laravel + SQLite · Déploiement 1-clic**

[![Python](https://img.shields.io/badge/Python-3.12-3776AB?style=flat-square&logo=python&logoColor=white)](https://www.python.org/)
[![FastAPI](https://img.shields.io/badge/FastAPI-0.115-009688?style=flat-square&logo=fastapi&logoColor=white)](https://fastapi.tiangolo.com/)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com/)
[![SQLite](https://img.shields.io/badge/SQLite-3-003B57?style=flat-square&logo=sqlite&logoColor=white)](https://www.sqlite.org/)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

<br/>

<img src="https://img.shields.io/badge/Architecture-3--tiers%20d%C3%A9coupl%C3%A9e-ff69b4?style=for-the-badge" alt="3-tiers">
<img src="https://img.shields.io/badge/D%C3%A9ploiement-1--clic-orange?style=for-the-badge" alt="1-click">
<img src="https://img.shields.io/badge/R%C3%A8gles%20m%C3%A9tier-100%25%20pur%20Python-yellow?style=for-the-badge" alt="Business rules">

</div>

---

## 📋 Sommaire

- [Présentation](#-présentation)
- [Architecture](#-architecture)
- [Fonctionnalités](#-fonctionnalités)
- [La règle métier : calcul des statuts PPS](#-la-règle-métier--calcul-des-statuts-pps)
- [Stack technique](#-stack-technique)
- [API REST](#-api-rest)
- [Démarrage rapide](#-démarrage-rapide-1-clic)
- [Cycle de vie des données](#-cycle-de-vie-des-données)
- [Structure du projet](#-structure-du-projet)
- [Sécurité](#-sécurité)
- [Documentation](#-documentation)

---

## 🎯 Présentation

L'**Assistant PPS** est une application web de pilotage RH qui suit les certifications
**PPS** (Pratiques Professionnelles Sécurité) d'un parc d'employés.

Elle transforme un fichier Excel brut en un **tableau de bord interactif** et
**calcule automatiquement** le statut de chaque certification :

| Statut | Signification |
|:------:|---------------|
| 🟢 `VALIDE` | Certificat encore valide |
| 🟠 `A REQUALIFIER` | Période de requalification ouverte (< 3 mois) |
| 🔵 `PREVU` | Échéance dans les ~15 jours |
| 🔴 `SUSPENDU` | Certificat expiré — **danger opérationnel** |
| 🟡 `EN COURS` | Formation en cours (`EC` dans l'Excel) |
| ⚪ `NON APPLICABLE` | Jamais passée |

> **Sans IA, sans LLM.** 100 % de la logique est déterministe, testable et auditée :
> la fonction `compute_statut()` est la source unique de vérité.

---

## 🏗️ Architecture

```
    ┌──────────────────┐   HTTP / JSON   ┌──────────────────┐      SQL       ┌────────────┐
    │    Laravel 11     │ ──────────────▶ │     FastAPI       │ ─────────────▶ │   SQLite   │
    │     (PHP 8.3)     │ ◀────────────── │   (Python 3.12)   │ ◀───────────── │  2 fichiers│
    │   Frontend :8080  │                 │    API REST :8000 │                │   .db      │
    └──────────────────┘                  └──────────────────┘                └────────────┘
          │  Blade · Tailwind · Chart.js            ▲
          │  Auth · Sessions · Middleware            │ pandas / openpyxl
          ▼                                         │
    ┌──────────────────┐                            │
    │   Navigateur      │                  ┌──────────────────┐
    └──────────────────┘                  │   recap.xlsx      │
                                            │  (Excel source)   │
                                            └──────────────────┘
```

**Pourquoi deux langages ?** — Le *polyglot programming* : chaque outil fait ce qu'il
fait de mieux.

| | **Python / FastAPI** | **PHP / Laravel** |
|---|---|---|
| **Rôle** | Logique métier + données | Interface + authentification |
| **Force** | Pandas lit l'Excel en 1 ligne | Blade, sessions, middlewares |
| **Sortie** | JSON | HTML |
| **Base** | SQLite métier (`hr_database.db`) | SQLite utilisateurs (`database.sqlite`) |
| **Dépendances** | `pip` / `requirements.txt` | `composer` / `composer.json` |

> Les deux couches sont **interchangeables** : on peut remplacer le frontend par React,
> ou SQLite par PostgreSQL, sans toucher à l'autre moitié.

---

## ✨ Fonctionnalités

**📊 Tableau de bord** — 5 cartes de statistiques, donut Chart.js de distribution,
section d'alertes (employés suspendus), résumé du jeu de données.

**👥 Gestion des employés** — Liste filtrable/rechercheable, onglet *Non qualifiés*,
détail par employé avec décomposition complète de ses PPS.

**🎯 Suivi PPS** — 3 onglets (Graphiques / Liste / Classement), graphiques
interactifs cliquables qui filtrent par statut, drill-down par PPS,
classement horizontal des PPS.

**🔍 Moteur de recherche** — Recherche côté client insensible à la casse **et aux
accents** (normalisation NFKD : `MÉLANIE` → `melanie`).

**🔐 Authentification** — 2 rôles (`admin` / `employee`) avec middleware dédié :
un employé voit les données collectives mais **ni le dashboard, ni la page PPS**.

**⚡ Déploiement 1-clic** — Scripts batch qui installent tout depuis zéro
(winget → venv → composer → migrations → seed) et lancent l'app avec auto-détection IP.

**📈 Détection d'incohérences** — Quand l'Excel dit `VALIDE` mais que la date
indique une expiration, l'incohérence est **conservée** (`raw_value`) et remontée
dans un rapport d'audit.

---

## 🧮 La règle métier : calcul des statuts PPS

Le cœur du projet — `compute_statut()` dans
[`migrate_excel_to_sqlite.py`](ai_assistant/backend/scripts/migrate_excel_to_sqlite.py) :

```python
def compute_statut(date_val, pps_val):
    if not date_val:                       # Pas de date
        return "en_cours" if "EC" in pps_val else "non_applicable"

    age = (today - date_val).years_floating # âge en années décimales

    if age > 3:        return "suspendu"        # expiré
    if age >= 2.9589:  return "prevu"           # < 15 j avant échéance
    if age >= 2.75:    return "a_requalifier"   # < 3 mois avant échéance
    return "valide"                             # tout va bien
```

**D'où viennent ces seuils ?**

| Seuil | Équivalence | Sens |
|------:|-------------|------|
| `3 ans` | Durée de validité d'un PPS | Au-delà → **suspendu** |
| `2.9589` | 3 ans − 15 jours | Fenêtre de **prévu** |
| `2.75` | 3 ans − 3 mois | Début de **requalification** |

**Exemple concret** — un employé a passé son PPS *Soudure* le `22/03/2018` :

```
2018-03-22  →  ~8 ans  →  🔴 SUSPENDU
```

Si la case Excel contient `VALIDE`, l'application détecte l'incohérence et
l'enregistre dans `rapport_correction_pps.md`.

---

## 🛠️ Stack technique

<table>
<tr><td>

**Backend — données & logique**

| Composant | Version |
|---|---|
| Python | 3.12 |
| FastAPI | 0.115.6 |
| Uvicorn | 0.34.0 |
| Pandas | 2.2.3 |
| NumPy | 2.2.1 |
| openpyxl | 3.1.5 |
| Pydantic | 2.10.4 |

</td><td>

**Frontend — rendu & auth**

| Composant | Version |
|---|---|
| PHP | 8.3 |
| Laravel | 11 |
| Tailwind CSS | via CDN |
| Chart.js | via CDN |
| SQLite | 3 |

</td></tr>
</table>

---

## 🔌 API REST

| Méthode | Endpoint | Description |
|:-------:|---|---|
| `GET` | `/api/employee/meta` | Métadonnées du jeu de données |
| `GET` | `/api/employee/analyze` | Dataset complet (employés + PPS + scores) |
| `GET` | `/health` | Health check (`{"status":"ok"}`) |

Documentation interactive générée automatiquement sur **`http://localhost:8000/docs`** (Swagger UI).

### Routes Laravel

| Méthode | Route | Accès |
|:-------:|---|---|
| `GET` | `/login` | public |
| `POST` | `/login` · `/logout` | public |
| `GET` | `/` | 🔒 admin — dashboard |
| `GET` | `/pps` | 🔒 admin — suivi PPS |
| `GET` | `/employees` | 🔒 admin + employee |
| `GET` | `/employees/{matricule}` | 🔒 admin + employee |

---

## 🚀 Démarrage rapide (1-clic)

### Option A — Installation complète sur un PC vierge

```bat
install.bat
```

Installe PHP 8.3 et Python 3.12 via **winget**, crée le `venv`, lance
`composer install`, génère la `APP_KEY`, crée la base et exécute les seeders.

### Option B — PHP et Python déjà installés

```bat
setup_env.bat
```

### Option C — Lancer au quotidien

```bat
start_assistant.bat
```

Démarre uvicorn (`:8000`) + artisan (`:8080`), auto-détecte l'IP réseau et ouvre
le navigateur.

> **Comptes par défaut** (créés par `DatabaseSeeder`) :
>
> | Rôle | Identifiant | Mot de passe |
> |---|---|---|
> | admin | `ADMIN` | `MK2026` |
> | employee | `MKTANGER` | `MK2026` |
>
> Change-les puis lance `reset_users.bat` (`migrate:fresh --seed`).

### Installation manuelle

```bash
# 1. Backend Python
python -m venv venv
venv\Scripts\activate
pip install -r ai_assistant\backend\requirements.txt

# 2. Frontend Laravel
cd ai_assistant\laravel
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed

# 3. Données Excel -> SQLite
python ai_assistant\backend\scripts\migrate_excel_to_sqlite.py

# 4. Lancer les deux serveurs
uvicorn backend.main:app --reload          # :8000
php artisan serve --port=8080              # :8080
```

---

## 🔄 Cycle de vie des données

```
recap.xlsx  ──(refresh_Excel_sqlite.bat)──▶  migrate_excel_to_sqlite.py
                                                       │
                                                       ▼
                                              SQLite (hr_database.db)
                                                       │
                                                       ▼  FastAPI
                                        GET /api/employee/analyze
                                                       │
                                                       ▼
                                             build_employee_dataset()
                                                       │
                                                       ▼  JSON
                                    Laravel NexusController (Http::get)
                                                       │
                                                       ▼
                                         Vue Blade → HTML + Chart.js
                                                       │
                                                       ▼
                                                   Navigateur
```

**Chaque étape est indépendante** : on peut modifier l'Excel sans toucher au code,
les vues sans toucher à l'API, et la base sans toucher au frontend.

---

## 📁 Structure du projet

```
assistant/
├── ai_assistant/                     # Application principale
│   ├── backend/                      # 🔵 FastAPI
│   │   ├── api/routes/                #   employee.py · health.py
│   │   ├── core/config.py             #   Chemins centralisés (DRY)
│   │   ├── data/                      #   recap.xlsx · hr_database.db  [gitignorés]
│   │   ├── scripts/
│   │   │   └── migrate_excel_to_sqlite.py   # ⭐ compute_statut() — la règle métier
│   │   ├── services/
│   │   │   ├── database.py            # ⭐ cœur métier (CRUD + dataset)
│   │   │   ├── employee_service.py    #   couche service
│   │   │   └── qualification_engine.py#   normalisation texte NFKD
│   │   ├── main.py                    #   point d'entrée + CORS
│   │   └── requirements.txt
│   │
│   ├── laravel/                       # 🔴 Laravel
│   │   ├── app/Http/Controllers/      #   AuthController · NexusController
│   │   ├── app/Http/Middleware/       #   CheckRole.php
│   │   ├── database/seeders/          #   DatabaseSeeder (2 comptes)
│   │   ├── resources/views/           #   layouts · auth · pages
│   │   └── routes/web.php             #   définition des routes
│   │
│   ├── ARCHITECTURE.md · DEPLOYER.md · PRESENTATION.md
│   └── venv/                          # [gitignoré]
│
├── Assistant_PPS_App/                 # 📦 Package de déploiement prêt à zipper
│
├── .gitignore                         # 🔒 protège secrets + données RH
├── install.bat · setup_env.bat        # Installation 1-clic
├── start_assistant.bat                # Lancement quotidien
├── refresh_Excel_sqlite.bat           # Synchro Excel → SQLite
├── reset_users.bat                    # Reset utilisateurs
└── ARCHITECTURE_COMPLETE.txt          # 📖 Guide pédagogique complet
```

---

## 🔒 Sécurité

- **Authentification obligatoire** sur toutes les routes sauf `/login`
- **Middleware de rôle** (`admin` / `employee`) — 403 en cas de non-correspondance
- **Mots de passe hashés** par Laravel (bcrypt)
- **Sessions régénérées / invalidées** à la connexion et déconnexion
- **CORS** configuré (FastAPI sur `:8000`, Laravel sur `:8080`)
- **`.gitignore` strict** : aucun `.env`, aucune base, aucune donnée RH
  (`.env.example` est commité à la place)

---

## 📚 Documentation

| Document | Contenu |
|---|---|
| [`ARCHITECTURE_COMPLETE.txt`](ARCHITECTURE_COMPLETE.txt) | Guide pédagogique pas-à-pas — **à lire en premier** |
| [`ai_assistant/ARCHITECTURE.md`](ai_assistant/ARCHITECTURE.md) | Découpage détaillé des modules |
| [`ai_assistant/PRESENTATION.md`](ai_assistant/PRESENTATION.md) | Support de présentation projet |
| [`ai_assistant/DEPLOYER.md`](ai_assistant/DEPLOYER.md) | Guide de déploiement |
| [`ai_assistant/DOCUMENTATION.txt`](ai_assistant/DOCUMENTATION.txt) | Documentation complète |

---

<div align="center">

**Conçu comme support pédagogique** — architecture découplée · responsabilité unique ·
règles métier séparées des données brutes · déploiement automatisé.

⭐ *Si ce projet t'a servi, laisse une étoile !*

</div>

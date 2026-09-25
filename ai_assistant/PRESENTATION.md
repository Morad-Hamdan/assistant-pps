# Assistant — Application Décisionnelle de Gestion des PPS

---

## Plan

1. [Contexte & Problématique](#1-contexte--problématique)
2. [Objectifs](#2-objectifs)
3. [Architecture Technique](#3-architecture-technique)
4. [Logique du Code](#4-logique-du-code)
5. [Modèle de Données](#5-modèle-de-données)
6. [Fonctionnalités](#6-fonctionnalités)
7. [Authentification & Rôles](#7-authentification--rôles)
8. [Système d'Alertes](#8-système-dalertes)
9. [Déploiement](#9-déploiement)
10. [Captures d'Écran](#10-captures-décran)
11. [Conclusion & Perspectives](#11-conclusion--perspectives)

---

## 1. Contexte & Problématique

| Aspect | Description |
|--------|-------------|
| **Domaine** | Gestion des ressources humaines en environnement industriel |
| **Métier** | Suivi des Permis de Pilotage de Subtracteurs (PPS) — certifications obligatoires pour les opérateurs de production |
| **Problème** | 154 employés, 28 PPS chacun, répartis dans un fichier Excel complexe. Aucune visibilité temps réel, aucun système d'alerte, recherche manuelle fastidieuse |
| **Besoin** | Un outil centralisé, rapide, accessible depuis n'importe quel poste, avec alertes automatiques et requêtes en langage naturel |

---

## 2. Objectifs

1. **Centraliser** les données PPS dans une base SQLite interrogeable en temps réel
2. **Automatiser** le calcul des statuts PPS (valide, à requalifier, prévu à suspendre, suspendu, en cours)
3. **Alerter** instantanément sur les PPS suspendus (ALERTE) et les échéances proches (SURVEILLANCE)
4. **Permettre** la consultation via :
   - Un tableau de bord graphique (Chart.js)
   - Une recherche intelligente par mot-clé
5. **Authentifier** les utilisateurs avec deux rôles (admin/employee)
6. **Partager** l'application sur le réseau local

---

## 3. Architecture Technique

```
┌─────────────────────────────────────────────────────────────────────┐
│                         NAVIGATEUR WEB                               │
│              http://127.0.0.1:8080  (local)                          │
│              http://192.168.X.X:8080  (réseau)                       │
└───────────────────────────┬─────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    LARAVEL 11  (PHP 8.3)                             │
│                    Interface utilisateur                             │
│                    Blade Templates + Tailwind CSS                    │
│                    Chart.js pour les graphiques                      │
│                    AuthController + CheckRole middleware              │
│                    artisan serve --host=0.0.0.0 --port=8080          │
└───────────────────────────┬─────────────────────────────────────────┘
                            │ HTTP (requêtes API côté serveur)
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    FASTAPI  (Python 3.12)                            │
│                    API REST — uvicorn :8000                          │
│                    Endpoints : /api/employee/*                       │
│                    CORS ouvert pour Laravel                          │
└───────────────────────────┬─────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    SERVICE MÉTIER (database.py)                       │
│                                                                     │
│  build_employee_dataset() :                                          │
│    · Requête SQL → employees + pps_assignments                       │
│    · Calcule statut par date (compute_statut)                        │
│    · Agrège compteurs et listes par statut                           │
│    · Retourne JSON → Laravel                                         │
│                                                                     │
└───────────────────────────┬─────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    SQLITE  (hr_database.db)                           │
│                                                                     │
│  employees : ~770 enregistrements                                    │
│  pps_assignments : ~21 560 assignations                              │
│                                                                     │
│  Source : recap.xlsx → migrate_excel_to_sqlite.py                    │
│  Rafraîchissement : refresh_Excel_sqlite.bat (double-clic)                 │
└─────────────────────────────────────────────────────────────────────┘
```

**Choix technologiques :**

| Technologie | Rôle | Justification |
|-------------|------|---------------|
| **FastAPI** (Python 3.12) | API REST | Performant, typage fort, documentation Swagger auto |
| **Laravel 11** (PHP 8.3) | Interface web | Blade, middleware auth, écosystème mature |
| **SQLite** | Base métier | Zéro config, fichier unique, portable |
| **Chart.js** | Graphiques | Léger, responsive, interactif |
| **Tailwind CSS** | Styles | Utilitaire, responsive mobile-first |
| **openpyxl / pandas** | Import Excel | Lecture du recap.xlsx → SQLite |
| **Composer** | PHP dependencies | Gestion des packages Laravel |
| **winget** | Installation | Déploiement 1-clic de PHP + Python |
| **PowerShell** | Scripts batch | Auto-détection IP, lancement services |

---

## 4. Logique du Code

### 4.1 Flux des données

```
recap.xlsx  ──►  migrate_excel_to_sqlite.py  ──►  hr_database.db
                                                          │
                    ┌─────────────────────────────────────┘
                    ▼
          build_employee_dataset()  (database.py)
                    │
                    ▼
              API FastAPI  ──►  Laravel Controller  ──►  Vue Blade
```

### 4.2 Backend FastAPI — détail par fichier

| Fichier | Rôle |
|---------|------|
| `main.py` | Initialise l'app FastAPI, configure CORS, inclut les routes |
| `api/routes/employee.py` | Endpoints : `/api/employee/meta`, `/api/employee/analyze` |
| `services/database.py` | Cœur métier : `build_employee_dataset()` + `init_schema()` |
| `scripts/migrate_excel_to_sqlite.py` | Lecture Excel → insertion SQLite + `compute_statut()` |
| `core/config.py` | Constantes : chemins fichiers, seuils (2.75, 2.96, 3 ans) |

### 4.3 Calcul des statuts PPS (`compute_statut`)

```python
def compute_statut(date_val, pps_val):
    si pas de date :
        si cellule contient "EC" → "en_cours"
        sinon → "non_applicable"
    sinon :
        diff = (today - date_val) en années
        si diff > 3 ans      → "suspendu"
        si diff >= 2.96 ans  → "prevu"
        si diff >= 2.75 ans  → "a_requalifier"
        sinon                → "valide"
```

### 4.4 Frontend Laravel — détail par fichier

| Fichier | Rôle |
|---------|------|
| `NexusController.php` | Appelle l'API FastAPI, formate les données pour les vues |
| `AuthController.php` | Login/logout par `name`, hash bcrypt, session |
| `routes/web.php` | 5 routes : dashboard, employés, détail, PPS (auth+roles) |
| `views/pages/dashboard.blade.php` | Accueil : stats, alertes, tableaux |
| `views/pages/employees.blade.php` | Liste employés avec recherche |
| `views/pages/employee.blade.php` | Détail : compteurs + tableaux par statut |
| `views/pages/pps.blade.php` | Analyse PPS : graphiques + liste |
| `views/layouts/app.blade.php` | Sidebar, responsive mobile, CSS global |
| `Http/Middleware/CheckRole.php` | Middleware : filtre par rôle (admin/employee) |

### 4.5 Logique d'authentification

```
1. POST /login → AuthController::login()
   - Cherche l'utilisateur par 'name' dans SQLite (table users)
   - Vérifie le mot de passe avec Hash::check()
   - Stocke 'name' + 'role' en session

2. Middleware 'auth' → vérifie session active
   Middleware 'role:admin' → vérifie role == 'admin'
   Middleware 'role:admin,employee' → vérifie role dans la liste

3. GET /login → formulaire de connexion (si session absente)
```

### 4.6 Scripts batch — logique

| Script | Action |
|--------|--------|
| `lancer_morad.bat` | Démarre FastAPI (uvicorn) + Laravel (artisan serve) en parallèle |
| `refresh_Excel_sqlite.bat` | Active venv → lance migrate_excel_to_sqlite.py |
| `install.bat` | 1-clic : installe PHP, Python, crée venv, composer install, migrate |
| `setup_env.bat` | Idem sans winget (si PHP/Python déjà installés) |
| `reset_users.bat` | php artisan migrate:fresh --seed (reset comptes) |

---

## 5. Modèle de Données

### 4.1 Diagramme Entité-Relation

```
┌──────────────────┐          ┌─────────────────────────────┐
│    employees     │          │     pps_assignments          │
├──────────────────┤          ├─────────────────────────────┤
│ id (PK)          │◄─────────│ employee_id (FK)            │
│ matricule (UQ)   │    1:N   │ pps_name                    │
│ nom              │          │ date_debut_validite         │
│ prenom           │          │ statut (calculé à la volée)  │
│ processus        │          │ raw_value                   │
│ uap              │          └─────────────────────────────┘
│ eap              │
└──────────────────┘
```

### 4.2 Cycle de vie d'un PPS

```
     Valeur ──► "EC" ───────► EN COURS      [>]
     dans         │
     Excel        ├─► < 2,75 ans ──► VALIDE         [OK]
                  │
                  ├─► 2,75–2,96 ans ──► À REQUALIFIER [!]
                  │
                  ├─► 2,96–3 ans ─────► PRÉVU         [~]
                  │
                  ├─► > 3 ans ────────► SUSPENDU      [X]
                  │
                  └─► vide ──────────► NON APPLICABLE
```

### 4.3 Distribution actuelle

```
Valides            : 514
À requalifier      :  15
Prévus à suspendre :   0
En cours           :  80
Suspendus          :  38
Non applicables    : 3665
─────────────────────────────────
Total              : 4312 assignations
```

---

## 5. Fonctionnalités

### 6.1 Dashboard (page d'accueil)

| Élément | Description |
|---------|-------------|
| **5 cartes stat** | Valides [OK], À requalifier [!], Prévus [~], En cours [>], Suspendus [X] |
| **Donut PPS** | Répartition graphique des statuts |
| **Alertes** | Compteur d'employés avec PPS suspendus + actions requises |
| **Résumé** | Nombre total d'employés dans la base |
| **Tableau suspendus** | Nom, matricule, PPS suspendus et **Date de Validation** (jj/mm/aaaa) |
| **Tableau PPS** | Aperçu de tous les PPS avec effectifs par statut |

### 6.2 Employés

| Fonction | Détail |
|----------|--------|
| **Recherche** | Filtrage instantané par nom ou matricule |
| **Tableau complet** | Matricule, nom, processus, UAP, alerte, compteurs |
| **Onglet "Non qualifiés"** | Employés avec 0 PPS dans tous les statuts (admin) |
| **Liens cliquables** | Clic sur un matricule → page détail |

### 6.3 Détail Employé

```
┌──────────────────────────────────────────┐
│  AYAD BILAL                   ALERTE ⚠   │
│  Matricule : 81041                        │
│  Processus : PRODUIRE │ UAP : CONTRÔLE    │
├──────────────────────────────────────────┤
│  [5]  [1]  [0]  [2]  [3]                 │
│  Valides  Req.  Prévu  Cours  Suspendus   │
├──────────────────────────────────────────┤
│  PPS Suspendus [X]                        │
│  ┌──────────────┬────────────────────┐    │
│  │ PPS          │ Date de Validation │    │
│  ├──────────────┼────────────────────┤    │
│  │ PPS 022      │ 22/03/2018         │    │
│  └──────────────┴────────────────────┘    │
│                                          │
│  PPS Prévus [~]                           │
│  ┌──────────────┬────────────────────┐    │
│  │ PPS          │ Date de Validation │    │
│  └──────────────┴────────────────────┘    │
│                                          │
│  PPS Valides [OK]  (tags)                 │
│  PPS 001  PPS 003  PPS 015  ...          │
└──────────────────────────────────────────┘
```

### 6.4 Analyse PPS (3 onglets)

**Onglet 1 — Graphiques**
- Donut de répartition (clic → filtre le tableau)
- Barres des statuts (clic → filtre)
- Panneau détail PPS avec liste d'employés par statut

**Onglet 2 — Liste PPS**
- Statistiques cliquables
- Recherche par nom de PPS
- Tableau complet avec effectifs par statut
- Clic sur un PPS → détail avec liste employés

**Onglet 3 — Classement**
- Barres horizontales triées par valides
- Nombre sur chaque barre

---

## 7. Authentification & Rôles

### 7.1 Connexion

```
┌──────────────────────┐
│     Assistant         │
│   Connectez-vous      │
│                       │
│  Nom d'utilisateur    │
│  ┌───────────────────┐│
│  │ ADMIN             ││
│  └───────────────────┘│
│                       │
│  Mot de passe         │
│  ┌───────────────────┐│
│  │ ••••••            ││
│  └───────────────────┘│
│                       │
│  ┌───────────────────┐│
│  │  Se connecter     ││
│  └───────────────────┘│
└──────────────────────┘
```

### 7.2 Périmètres d'accès

```
                    ┌──────────┐
                    │  ADMIN   │
                    ├──────────┤
                    │ Dashboard│
                    │ Employés │
                    │ PPS      │
                    └────┬─────┘
                         │
                    ┌────▼─────┐
                    │EMPLOYEE  │
                    ├──────────┤
                    │ Employés │
                    └──────────┘
```

---

## 8. Système d'Alertes

```
┌─────────────────────────────────────────────────────────────────┐
│                        ALERTES                                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ⚠  3 employé(s) avec suspension(s)      [ALERTE — Rouge]       │
│      Action requise : au moins un PPS suspendu                   │
│                                                                  │
│  ┌──────┬──────────────────┬──────────┬──────────────────────┐   │
│  │  #   │ Nom              │ Matricule│ PPS Suspendus        │   │
│  ├──────┼──────────────────┼──────────┼──────────────────────┤   │
│  │  1   │ AYAD BILAL       │ 81041    │ PPS 022              │   │
│  │      │                  │          │ Date: 22/03/2018     │   │
│  │  2   │ BENALI SOFIANE   │ 82101    │ PPS 009, PPS 015     │   │
│  │      │                  │          │ Date: 15/01/2020     │   │
│  │  3   │ CHERIFI MOHAMED  │ 83005    │ PPS 042              │   │
│  │      │                  │          │ Date: 30/11/2017     │   │
│  └──────┴──────────────────┴──────────┴──────────────────────┘   │
│                                                                  │
│  SURVEILLANCE [Orange] — PPS à requalifier ou prévus             │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 9. Déploiement

### 9.1 Architecture réseau

```
┌───────────────────────────────────────────────────────────┐
│                   MACHINE SERVEUSE                          │
│                   (192.168.0.139)                           │
│                                                             │
│  ┌──────────────┐     ┌──────────────────────┐              │
│  │  FastAPI      │     │  Laravel (artisan)   │              │
│  │  :8000        │◄────│  :8080               │              │
│  │  API REST     │     │  Interface Web        │              │
│  └──────────────┘     └──────────────────────┘              │
│         │                        │                           │
│         └─────── SQLite ─────────┘                           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
                              │
         ┌────────────────────┼────────────────────┐
         ▼                    ▼                    ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  PC Admin 1   │    │  PC Employé  │    │  PC Admin 2  │
│  Navigateur   │    │  Navigateur  │    │  Navigateur  │
└──────────────┘    └──────────────┘    └──────────────┘
```

### 9.2 Fiche de déploiement

| Étape | Commande |
|-------|----------|
| **Lancer** | Double-clic sur `lancer_morad.bat` |
| **API** | `uvicorn backend.main:app --reload --host 0.0.0.0 --port 8000` |
| **Interface** | `php artisan serve --host=0.0.0.0 --port=8080` |
| **Accès local** | `http://127.0.0.1:8080` |
| **Accès réseau** | `http://192.168.0.139:8080` |
| **Actualiser données** | Double-clic sur `backend/data/refresh_Excel_sqlite.bat` |

### 9.3 Comptes de test

| Utilisateur | Mot de passe | Rôle |
|-------------|--------------|------|
| `ADMIN` | MK2026 | Administrateur (accès complet) |
| `MKTANGER` | MK2026 | Employé (Employés uniquement) |

---

## 10. Captures d'Écran

### 10.1 Dashboard

```
┌─────────────────────────────────────────────────────────────────┐
│  Dashboard                                                       │
│                                                                  │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐                  │
│  │ 514  │ │  15  │ │  0   │ │  80  │ │  38  │                  │
│  │Verde │ │Jaune │ │Violet│ │ Cyan │ │Rouge │                  │
│  │Valides││Req.  │ │Prévu │ │Cours │ │Susp. │                  │
│  └──────┘ └──────┘ └──────┘ └──────┘ └──────┘                  │
│                                                                  │
│  ┌────────────────────┐  ┌────────────────────┐                  │
│  │   Donut PPS        │  │   Alertes          │                  │
│  │   [Chart.js]       │  │   ⚠ 3 employés     │                  │
│  └────────────────────┘  └────────────────────┘                  │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Employés avec PPS suspendus                            │    │
│  │  ┌────┬──────────────┬──────────┬──────────┬──────────┐ │    │
│  │  │ #  │ Nom          │ Matr.    │ PPS      │ Date     │ │    │
│  │  ├────┼──────────────┼──────────┼──────────┼──────────┤ │    │
│  │  │ 1  │ AYAD BILAL   │ 81041    │ PPS 022  │22/03/2018│ │    │
│  │  └────┴──────────────┴──────────┴──────────┴──────────┘ │    │
│  └──────────────────────┴──────────────────────────────────┘    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 10.2 Interface Mobile

```
┌───────────────────────┐
│ ☰  Assistant          │
├───────────────────────┤
│                       │
│  Dashboard             │
│                       │
│  ┌────┐ ┌────┐        │
│  │514 │ │ 15 │        │
│  │Val │ │Req │        │
│  └────┘ └────┘        │
│  ┌────┐ ┌────┐        │
│  │ 0  │ │ 80 │        │
│  │Prév│ │Cour│        │
│  └────┘ └────┘        │
│  ┌────┐               │
│  │ 38 │               │
│  │Susp│               │
│  └────┘               │
│                       │
│  [Donut chart]        │
│  [Alertes]            │
│                       │
│  ← tableau scroll —→  │
│                       │
└───────────────────────┘
```

---

## 11. Conclusion & Perspectives

### Points forts

- ✅ **Rapidité** : réponses en < 1s pour toutes les intentions (sans IA)
- ✅ **Simplicité** : un double-clic pour lancer, partage réseau immédiat
- ✅ **Visibilité** : alertes visuelles, graphiques interactifs, tableaux clairs
- ✅ **Autonomie** : zéro dépendance externe (ni cloud, ni abonnement)
- ✅ **Fiabilité** : base SQLite locale, pas de latence réseau
- ✅ **Accessibilité** : interface responsive (mobile + desktop), partage LAN

### Limitations actuelles

- ⚠ Pas de synchronisation multi-utilisateurs en temps réel (SQLite fichier unique)
- ⚠ Pas d'export PDF ou Excel depuis l'interface
- ⚠ Pas de modification des données depuis l'interface (Excel restant source)
- ⚠ Authentification basique (pas de SSO, 2FA, ou gestion des sessions avancée)

### Perspectives d'évolution

1. **Migration PostgreSQL** pour un accès concurrentiel multi-utilisateur
2. **Export PDF/Excel** des tableaux et graphiques
3. **Édition en ligne** des statuts PPS (validation/requalification directe)
4. **Notifications par email** pour les échéances PPS
5. **API REST documentée Swagger** pour intégration avec d'autres systèmes
6. **Historique des modifications** (traçabilité des actions admin)
7. **Tableau de bord personnalisé** par employé (widgets configurables)

---

## Crédits

**Projet** : PFE — Assistant Décisionnel de Gestion des PPS
**Stack** : FastAPI (Python 3.12) / Laravel 11 (PHP 8.3) / SQLite / Chart.js / Tailwind CSS
**Auteur** : Projet de fin d'études
**Date** : Juin 2026

---

*Documentation générée le 17 juin 2026*

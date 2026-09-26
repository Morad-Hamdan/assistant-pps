# Mise en ligne — démo publique

Ce document décrit comment publier l'Assistant PPS sur une URL accessible à
tout le monde. Pour installer l'application **en local** (réseau d'entreprise),
voir `DEPLOYER.md`.

---

## 1. Ce qu'il y a dans l'image

Un **seul service Docker** réunit les deux applications :

| Composant | Technologie | Rôle | Exposé ? |
|---|---|---|---|
| Interface | PHP 8.3 / Laravel | Dashboard, pages, authentification | **oui** — sur `$PORT` |
| API | Python 3 / FastAPI | Lecture des données RH | **non** — interne uniquement |

Laravel appelle FastAPI via `FASTAPI_URL` (voir `NexusController`). FastAPI
n'est jamais joignable de l'extérieur : aucun port n'est ouvert pour lui.

> **Pourquoi un seul service ?** FastAPI et Laravel doivent tourner ensemble.
> Séparés, il faudrait payer et surveiller deux instances.

---

## 2. Construire et tester en local

```bash
docker build -t assistant-pps .
docker run -p 8080:8080 -e APP_URL=http://localhost:8080 assistant-pps
```

Puis ouvrir <http://localhost:8080> et se connecter avec les comptes de
démonstration (§6).

Au démarrage, le conteneur :

1. crée `laravel/.env` à partir du modèle `laravel/.env.docker` (sans secret)
   et génère une `APP_KEY` fraîche ;
2. applique les migrations Laravel et crée les comptes **si la base n'existe
   pas** ;
3. génère le jeu de données RH **fictif** ;
4. démarre FastAPI et **attend qu'elle réponde** (Laravel n'a aucun
   `try/catch` autour de ses appels HTTP : une API absente produirait une
   page 500) ;
5. lance l'interface Laravel.

---

## 3. Variables d'environnement

| Variable | Obligatoire | Défaut | Rôle |
|---|---|---|---|
| `APP_URL` | **recommandée** | `http://localhost:$PORT` | URL publique réelle |
| `PORT` | fournie par l'hébergeur | `8080` | Port d'écoute de l'interface |
| `API_PORT` | non | `8010` | Port interne de FastAPI |
| `APP_DEBUG` | non | `false` | Ne **jamais** activer en ligne |
| `PHP_CLI_SERVER_WORKERS` | non | `2` | Processus PHP simultanés |

Toutes les URLs internes du site sont **relatives** (`/login`, `/employees`…),
donc une `APP_URL` imprécise ne casse pas la navigation — mais elle est
recommandée pour que les liens absolus générés par Laravel restent corrects.

---

## 4. Déploiement sur Render

> Vérifier les conditions actuelles sur <https://render.com/docs/free> avant
> de vous engager : les offres gratuites évoluent.

1. Créer un compte Render (aucune carte bancaire demandée).
2. **New + → Web Service**.
3. Connecter le dépôt GitHub `Morad-Hamdan/assistant-pps`.
4. Choisir le runtime **Docker** — le `Dockerfile` étant à la racine, il est
   détecté automatiquement.
5. Choisir l'instance **Free** (0.1 CPU / 512 Mo).
6. Dans *Advanced → Environment Variables*, ajouter :
   - `APP_URL` = l'URL publique affichée par Render (ex.
     `https://assistant-pps-xxxx.onrender.com`).
7. Définir le *Health Check Path* sur **`/login`** — la racine `/` redirige
   vers la page de connexion et renvoie un code 302.
8. **Create Web Service**, puis patienter pendant la construction.

### Limites de l'offre gratuite (Render, vérifiées en 2026)

| Limite | Valeur | Conséquence pour ce projet |
|---|---|---|
| Heures d'instance | **750 h/mois par workspace**, partagées | Deux services toujours allumés = 1 440 h → dépassement en ~15 jours |
| Veille | après **15 min** d'inactivité | Les services en veille **ne consomment pas** d'heures |
| Réveil | **30 à 60 s** | Premier clic parfois lent — normal |
| RAM / CPU | 512 Mo / 0.1 CPU | D'où les 2 workers PHP et le retrait de pandas |
| Disque persistant | **interdit** sur l'offre gratuite | Voir §5 |

**Conséquence directe et voulue :** l'offre gratuite ne conserve aucun fichier.
C'est exactement pour cela que le conteneur **reconstruit toutes les données à
chaque démarrage** — l'application se répare toute seule, sans volume, sans
base externe.

> ⚠️ **Ne pas configurer de surveillance d'uptime** (UptimeRobot, etc.) qui
> relancerait l'application toutes les 15 minutes : cela la maintiendrait
> éveillée en permanence et consumerait les 750 heures en ~31 jours.

---

## 5. Données : entièrement fictives

Aucune donnée RH réelle n'est présente dans ce dépôt, ni dans l'image :

| Fichier | Présent sur disque | Versionné | Dans l'image Docker |
|---|---|---|---|
| `backend/data/recap.xlsx` | oui (100 Ko) | **non** | **non** |
| `backend/data/hr_database.db` | oui (1,3 Mo) | **non** | **non** |
| `laravel/.env` | oui | **non** | **non** |

Le conteneur fabrique lui-même ses données au démarrage via
`backend/scripts/seed_demo.py` :

- **45 employés**, matricules `DEMO-001` … `DEMO-045` ;
- **491 affectations PPS**, réparties pour couvrir tous les statuts
  (`valide`, `a_requalifier`, `prevu`, `suspendu`, `en_cours`,
  `non_applicable`) ;
- noms, processus, UAP/EAP et intitulés de PPS **inventés** ;
- déterministe (graine fixe) : deux démarrages produisent les mêmes données.

Le script **refuse de s'exécuter** si la base contient déjà des lignes, sauf
passage explicite de `--force`. Il ne peut donc pas écraser de vraies données
par accident.

---

## 6. Comptes de démonstration

| Utilisateur | Mot de passe | Rôle | Accès |
|---|---|---|---|
| `ADMIN` | `MK2026` | admin | Dashboard, Employés, PPS |
| `MKTANGER` | `MK2026` | employé | Employés uniquement |

Ces comptes sont déjà documentés publiquement dans `DEPLOYER.md`. Ils
n'accèdent qu'aux données de démonstration fictives.

---

## 7. Ce qui n'est pas couvert

- **`recap.xlsx` → SQLite** : la migration (`scripts/migrate_excel_to_sqlite.py`)
  nécessite `pandas` et le vrai fichier Excel, deux choses volontairement
  absentes de l'image. Le conteneur utilise directement le seed SQL.
- **`Assistant_PPS_App/`** : doublon à l'identique de `ai_assistant/` (81
  fichiers identiques). Le lanceur `start_assistant.bat` utilise
  `ai_assistant/`. La construction Docker l'exclut pour éviter de doubler le
  temps de build.
- **Base de données externe** : uniquement SQLite, en fichiers temporaires.

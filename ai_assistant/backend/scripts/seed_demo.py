"""Genere un jeu de donnees RH de demonstration — 100 % fictif.

Aucune donnee reelle n'est lue ni reutilisee : matricules (DEMO-*), noms,
processus, UAP/EAP et intitules PPS sont inventes.

Ecrit directement dans SQLite, contrairement a
``scripts/migrate_excel_to_sqlite.py`` qui a besoin de ``recap.xlsx``
(fichier contenant de vraies donnees RH, non versionne a juste titre).

Le schema et la logique de statut sont identiques a la migration :

    diff > 3 ans       -> suspendu
    diff >= 2.9589     -> prevu
    diff >= 2.75       -> a_requalifier
    sinon              -> valide
    pas de date + "EC" -> en_cours
    pas de date        -> non_applicable

GARDE : si la base contient deja des lignes, le script refuse de continuer
sans ``--force``. Il ne doit jamais pouvoir ecraser des donnees reelles par
accident.

Usage (depuis la racine ai_assistant/) :
    python backend/scripts/seed_demo.py            # refuse si donnees presentes
    python backend/scripts/seed_demo.py --force    # ecrase explicitement
"""
import random
import sys
import os
from datetime import datetime, timedelta

sys.path.insert(0, ".")

from backend.core.config import SQLITE_PATH
from backend.services.database import get_connection, init_schema

# ── Inventarioire purement fictif ───────────────────────────────────────────

NOMS = [
    "Martin", "Bernard", "Dubois", "Thomas", "Robert", "Richard", "Petit",
    "Durand", "Leroy", "Moreau", "Simon", "Laurent", "Lefebvre", "Michel",
    "Garcia", "David", "Bertrand", "Roux", "Vincent", "Fournier", "Girard",
    "Alaoui", "Bennani", "Tazi", "Ouazzani", "Kettani", "Sabri", "Mansouri",
    "Berrada", "Chraibi", "Lahlou", "Naciri", "Bennis", "Haddad", "Zerhouni",
    "Alami", "Kadiri", "El Fassi", "Ouahbi", "Frikh",
]

PRENOMS_F = [
    "Fatima", "Khadija", "Amina", "Salma", "Nadia", "Leila", "Hajar",
    "Imane", "Sara", "Yasmine", "Hind", "Meryem", "Amal", "Soukaina", "Ghita",
]

PRENOMS_M = [
    "Mohamed", "Youssef", "Hamza", "Karim", "Rachid", "Mehdi", "Anas",
    "Ayoub", "Othmane", "Bilal", "Adil", "Hicham", "Nabil", "Reda", "Samir",
]

PROCESSUS = [
    "Usinage", "Assemblage", "Soudure", "Controle qualite", "Logistique",
    "Maintenance", "Montage", "Peinture", "Traitements de surface",
]

UAP = ["UAP-1", "UAP-2", "UAP-3", "UAP-4"]
EAP = ["EAP-A", "EAP-B", "EAP-C"]

PPS = [
    "Soudure MIG", "Soudure TIG", "Usinage CN", "Controle dimensionnel",
    "Montage mecanique", "Lecture de plan", "Controle visuel",
    "Maintenance preventive", "Manutention cariste", "Peinture industrielle",
    "Assemblage electrique", "Mesure 3D", "Securite au travail",
    "Habilitation electrique", "Controle qualite final", "Emballage export",
    "Programmation robot", "Reglage machine", "Preparation commande",
    "Controle recette client",
]

# Repartition cible — volontairement melangee pour que le dashboard montre
# des cas ALERTE / SURVEILLANCE plutot qu'un etat uniforme "valide".
PROFILS = {
    "propre":    0.45,   # tout est valide
    "surveille": 0.25,   # un ou deux a_requalifier / prevu
    "alerte":    0.18,   # au moins un suspendu -> risque ALERTE
    "en_cours":  0.12,   # quelques PPS "EC" + non_applicable
}

NB_EMPLOYES = 45


def _age_date(years, rng):
    """Date situee il y a ~`years` ans (borne largeur pour rester realiste)."""
    jitter = rng.uniform(-0.12, 0.12)
    days = int(max(0.05, years + jitter) * 365.25)
    return (datetime.now() - timedelta(days=days)).strftime("%Y-%m-%d")


def _pps_for_profil(profil, rng):
    """Renvoie une liste de tuples (pps_name, date, raw_value)."""
    out = []
    for pps in rng.sample(PPS, rng.randint(8, 14)):
        r = rng.random()

        if profil == "propre":
            date, raw = _age_date(rng.uniform(0.1, 2.6), rng), None

        elif profil == "surveille":
            if r < 0.70:
                date, raw = _age_date(rng.uniform(0.1, 2.5), rng), None
            elif r < 0.86:
                date, raw = _age_date(rng.uniform(2.76, 2.95), rng), None   # a_requalifier
            elif r < 0.94:
                date, raw = _age_date(rng.uniform(2.96, 2.99), rng), None    # prevu
            else:
                date, raw = None, ("EC" if r < 0.97 else None)

        elif profil == "alerte":
            if r < 0.62:
                date, raw = _age_date(rng.uniform(0.1, 2.6), rng), None
            elif r < 0.80:
                date, raw = _age_date(rng.uniform(3.2, 6.0), rng), None      # suspendu
            elif r < 0.90:
                date, raw = _age_date(rng.uniform(2.76, 2.95), rng), None
            else:
                date, raw = None, ("EC" if r < 0.95 else None)

        else:  # en_cours
            if r < 0.55:
                date, raw = _age_date(rng.uniform(0.1, 2.6), rng), None
            elif r < 0.80:
                date, raw = None, "EC"                                       # en_cours
            elif r < 0.92:
                date, raw = None, None                                       # non_applicable
            else:
                date, raw = _age_date(rng.uniform(3.2, 5.5), rng), None

        out.append((pps, date, raw))
    return out


def seed(force=False):
    rng = random.Random(20260926)  # deterministe : deux seeds = memes donnees

    init_schema()
    conn = get_connection()

    existing = conn.execute("SELECT COUNT(*) AS c FROM employees").fetchone()["c"]
    if existing and not force:
        conn.close()
        print(f"ABANDON : {existing} employes deja presents dans {SQLITE_PATH}.")
        print("          Relancer avec --force pour les remplacer par des")
        print("          donnees de demonstration fictives.")
        return 1

    conn.execute("DELETE FROM pps_assignments")
    conn.execute("DELETE FROM employees")

    # Choix du profil, en respectant approximativement la repartition cible
    cibles = []
    for profil, part in PROFILS.items():
        cibles += [profil] * max(1, round(part * NB_EMPLOYES))
    cibles = (cibles + ["propre"] * NB_EMPLOYES)[:NB_EMPLOYES]
    rng.shuffle(cibles)

    total_assign = 0
    statuts = {}

    for i, profil in enumerate(cibles, start=1):
        prenom = rng.choice(PRENOMS_M if i % 2 else PRENOMS_F)
        nom = rng.choice(NOMS)
        cur = conn.execute(
            "INSERT INTO employees (matricule, nom, prenom, processus, uap, eap) "
            "VALUES (?, ?, ?, ?, ?, ?)",
            (f"DEMO-{i:03d}", nom, prenom,
             rng.choice(PROCESSUS), rng.choice(UAP), rng.choice(EAP)),
        )
        emp_id = cur.lastrowid

        for pps_name, date, raw in _pps_for_profil(profil, rng):
            statut = "en_cours" if (date is None and raw == "EC") else (
                "non_applicable" if date is None else _statut_from_date(date))
            conn.execute(
                "INSERT INTO pps_assignments "
                "(employee_id, pps_name, date_debut_validite, statut, raw_value) "
                "VALUES (?, ?, ?, ?, ?)",
                (emp_id, pps_name, date, statut, raw),
            )
            total_assign += 1
            statuts[statut] = statuts.get(statut, 0) + 1

    conn.commit()

    n_emp = conn.execute("SELECT COUNT(*) AS c FROM employees").fetchone()["c"]
    rows = conn.execute(
        "SELECT e.matricule, e.nom, e.prenom, "
        "SUM(CASE WHEN p.statut='suspendu' THEN 1 ELSE 0 END) AS suspendu, "
        "SUM(CASE WHEN p.statut IN ('a_requalifier','prevu') THEN 1 ELSE 0 END) AS veille "
        "FROM employees e JOIN pps_assignments p ON p.employee_id = e.id "
        "GROUP BY e.id ORDER BY suspendu DESC, veille DESC LIMIT 5"
    ).fetchall()
    conn.close()

    print("=" * 64)
    print("  JEU DE DONNEES RH DE DEMONSTRATION — 100 % FICTIF")
    print("=" * 64)
    print(f"  base               : {SQLITE_PATH}")
    print(f"  employes           : {n_emp}   (matricules DEMO-*)")
    print(f"  assignations PPS   : {total_assign}")
    print("-" * 64)
    print("  repartition des statuts :")
    for k in sorted(statuts, key=lambda x: -statuts[x]):
        print(f"    {k:<18} {statuts[k]:>4}")
    print("-" * 64)
    print("  employes les plus a risque :")
    for r in rows:
        tag = "ALERTE" if r["suspendu"] else ("SURVEILLANCE" if r["veille"] else "VALID")
        print(f"    {r['matricule']}  {r['nom']} {r['prenom']:<12} "
              f"suspendu={r['suspendu']} veille={r['veille']}  -> {tag}")
    print("=" * 64)
    return 0


def _statut_from_date(date_str):
    """Reproduit backend.scripts.migrate_excel_to_sqlite.compute_statut."""
    d = datetime.strptime(date_str, "%Y-%m-%d")
    diff = (datetime.now() - d).days / 365.25
    if diff > 3:
        return "suspendu"
    if diff >= 2.9589:
        return "prevu"
    if diff >= 2.75:
        return "a_requalifier"
    return "valide"


if __name__ == "__main__":
    force = "--force" in sys.argv
    sys.exit(seed(force=force))

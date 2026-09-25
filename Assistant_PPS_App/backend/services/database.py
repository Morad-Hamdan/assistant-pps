import sqlite3
import os
from datetime import datetime
from typing import List, Dict, Any

from backend.core.config import SQLITE_PATH


def get_connection():
    os.makedirs(os.path.dirname(SQLITE_PATH), exist_ok=True)
    conn = sqlite3.connect(SQLITE_PATH)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA journal_mode=WAL")
    conn.execute("PRAGMA foreign_keys=ON")
    return conn


def init_schema():
    conn = get_connection()
    cursor = conn.cursor()
    cursor.executescript("""
        CREATE TABLE IF NOT EXISTS employees (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            matricule TEXT UNIQUE,
            nom TEXT,
            prenom TEXT,
            processus TEXT,
            uap TEXT,
            eap TEXT
        );

        CREATE TABLE IF NOT EXISTS pps_assignments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL REFERENCES employees(id),
            pps_name TEXT NOT NULL,
            date_debut_validite DATE,
            statut TEXT NOT NULL,
            raw_value TEXT
        );

        CREATE INDEX IF NOT EXISTS idx_pps_employee ON pps_assignments(employee_id);
        CREATE INDEX IF NOT EXISTS idx_pps_statut ON pps_assignments(statut);
        CREATE INDEX IF NOT EXISTS idx_pps_name ON pps_assignments(pps_name);
    """)
    conn.commit()
    conn.close()


def get_all_employees() -> List[Dict[str, Any]]:
    conn = get_connection()
    rows = conn.execute("SELECT * FROM employees ORDER BY nom, prenom").fetchall()
    conn.close()
    return [dict(r) for r in rows]


def get_employee_by_matricule(matricule: str) -> Dict[str, Any]:
    conn = get_connection()
    row = conn.execute(
        "SELECT * FROM employees WHERE matricule = ?", (matricule,)
    ).fetchone()
    conn.close()
    return dict(row) if row else {}


def get_employee_by_name(search: str) -> List[Dict[str, Any]]:
    conn = get_connection()
    rows = conn.execute(
        "SELECT * FROM employees WHERE LOWER(nom || ' ' || prenom) LIKE ?",
        (f"%{search}%",)
    ).fetchall()
    conn.close()
    return [dict(r) for r in rows]


def get_pps_assignments(employee_id: int) -> List[Dict[str, Any]]:
    conn = get_connection()
    rows = conn.execute(
        "SELECT * FROM pps_assignments WHERE employee_id = ?", (employee_id,)
    ).fetchall()
    conn.close()
    return [dict(r) for r in rows]


def get_pps_by_name(pps_name: str) -> List[Dict[str, Any]]:
    conn = get_connection()
    rows = conn.execute(
        "SELECT pa.*, e.matricule, e.nom, e.prenom "
        "FROM pps_assignments pa JOIN employees e ON pa.employee_id = e.id "
        "WHERE pa.pps_name LIKE ?",
        (f"%{pps_name}%",)
    ).fetchall()
    conn.close()
    return [dict(r) for r in rows]


def get_all_distinct_pps_names() -> List[str]:
    conn = get_connection()
    rows = conn.execute(
        "SELECT DISTINCT pps_name FROM pps_assignments ORDER BY pps_name"
    ).fetchall()
    conn.close()
    return [r["pps_name"] for r in rows]


def build_employee_dataset() -> List[Dict[str, Any]]:
    conn = get_connection()
    emp_rows = conn.execute("SELECT * FROM employees").fetchall()
    dataset = []
    for emp in emp_rows:
        emp_id = emp["id"]
        assign_rows = conn.execute(
            "SELECT * FROM pps_assignments WHERE employee_id = ?", (emp_id,)
        ).fetchall()

        statut_counts = {"valide": 0, "a_requalifier": 0, "prevu": 0, "suspendu": 0, "en_cours": 0, "non_applicable": 0}
        pps_lists = {"valid_pps": [], "requalify_pps": [], "prevu_pps": [], "suspended_pps": [], "en_cours_pps": []}
        pps_dates = {"suspended_pps_dates": [], "prevu_pps_dates": []}
        status_map = {
            "valide": ("valid_pps", "valid"),
            "a_requalifier": ("requalify_pps", "requalify"),
            "prevu": ("prevu_pps", "prevu"),
            "suspendu": ("suspended_pps", "suspended"),
            "en_cours": ("en_cours_pps", "en_cours"),
            "non_applicable": (None, "not_applicable"),
        }
        date_statuses = {"suspendu": "suspended_pps_dates", "prevu": "prevu_pps_dates"}

        for a in assign_rows:
            s = a["statut"]
            statut_counts[s] = statut_counts.get(s, 0) + 1
            list_key, count_key = status_map.get(s, (None, "not_applicable"))
            if list_key:
                pps_lists[list_key].append(a["pps_name"])
            if s in date_statuses:
                d = a["date_debut_validite"]
                pps_dates[date_statuses[s]].append({
                    "pps": a["pps_name"],
                    "date": d if d else ""
                })

        total_applicable = sum(statut_counts.get(k, 0) for k in ["valide", "a_requalifier", "prevu", "suspendu"])
        valid_count = statut_counts.get("valide", 0)
        requalify_count = statut_counts.get("a_requalifier", 0)
        suspended_count = statut_counts.get("suspendu", 0)
        prevu_count = statut_counts.get("prevu", 0)
        en_cours_count = statut_counts.get("en_cours", 0)
        na_count = statut_counts.get("non_applicable", 0)

        strict = round((valid_count / total_applicable) * 100, 2) if total_applicable > 0 else 0
        operational = round(((valid_count + requalify_count + prevu_count) / total_applicable) * 100, 2) if total_applicable > 0 else 0

        risk = ""
        global_status = "valid"
        if suspended_count > 0:
            risk = "ALERTE"
            global_status = "suspended"
        elif requalify_count > 0 or prevu_count > 0:
            risk = "SURVEILLANCE"
            global_status = "requalify"

        nom = emp["nom"] or ""
        prenom = emp["prenom"] or ""
        dataset.append({
            "matricule": emp["matricule"],
            "name": f"{nom} {prenom}".strip(),
            "processus": emp["processus"],
            "uap": emp["uap"],
            "eap": emp["eap"],
            "valid": valid_count,
            "requalify": requalify_count,
            "suspended": suspended_count,
            "prevu": prevu_count,
            "en_cours": en_cours_count,
            "not_applicable": na_count,
            "valid_pps": pps_lists["valid_pps"],
            "requalify_pps": pps_lists["requalify_pps"],
            "suspended_pps": pps_lists["suspended_pps"],
            "prevu_pps": pps_lists["prevu_pps"],
            "en_cours_pps": pps_lists["en_cours_pps"],
            "suspended_pps_dates": pps_dates["suspended_pps_dates"],
            "prevu_pps_dates": pps_dates["prevu_pps_dates"],
            "strict_score": strict,
            "operational_score": operational,
            "risk": risk,
            "global_status": global_status
        })
    conn.close()
    return dataset


def get_dataset_meta():
    conn = get_connection()
    emp_count = conn.execute("SELECT COUNT(*) as c FROM employees").fetchone()["c"]
    pps_count = conn.execute("SELECT COUNT(*) as c FROM pps_assignments").fetchone()["c"]
    columns = conn.execute("PRAGMA table_info(employees)").fetchall()
    statut_dist = conn.execute(
        "SELECT statut, COUNT(*) as c FROM pps_assignments GROUP BY statut ORDER BY c DESC"
    ).fetchall()
    sample = conn.execute(
        "SELECT * FROM employees LIMIT 5"
    ).fetchall()
    conn.close()
    return {
        "rows": emp_count,
        "columns_count": len(columns),
        "columns": [c["name"] for c in columns],
        "statut_distribution": {r["statut"]: r["c"] for r in statut_dist},
        "sample": [dict(r) for r in sample],
    }

"""Migrate Excel data to SQLite, handling EC values as 'en_cours' status."""
import sys; sys.path.insert(0, '.')
import pandas as pd
import numpy as np
from datetime import datetime

from backend.core.config import EXCEL_FILE
from backend.services.database import get_connection, init_schema

PREVU_THRESHOLD = 2.9589
today = datetime.now()


def is_ec_value(val) -> bool:
    if pd.isna(val) or not isinstance(val, str):
        return False
    return 'EC' in val.strip().upper()


def compute_statut(date_val, pps_val):
    if pd.isna(date_val) or not isinstance(date_val, (datetime, pd.Timestamp)):
        if is_ec_value(pps_val):
            return "en_cours"
        return "non_applicable"
    diff = (today - date_val).days / 365.25
    if diff > 3:
        return "suspendu"
    elif diff >= PREVU_THRESHOLD:
        return "prevu"
    elif diff >= 2.75:
        return "a_requalifier"
    else:
        return "valide"


def migrate():
    print("Lecture du fichier Excel...")
    df = pd.read_excel(EXCEL_FILE)

    date_cols = [c for c in df.columns if str(c).strip().startswith("Date")]
    pps_cols = []
    for col in date_cols:
        idx = list(df.columns).index(col)
        if idx + 1 < len(df.columns):
            pps_cols.append(df.columns[idx + 1])
        else:
            pps_cols.append("")

    print(f"  Employes: {len(df)}")
    print(f"  PPS: {len(date_cols)} colonnes")
    print()

    init_schema()
    conn = get_connection()
    cursor = conn.cursor()

    # Vider les anciennes donnees avant re-insertion
    cursor.execute("DELETE FROM pps_assignments")
    cursor.execute("DELETE FROM employees")
    print("  Anciennes donnees videes.")

    inserted_employees = 0
    inserted_assignments = 0
    en_cours_count = 0

    for _, row in df.iterrows():
        matricule = str(row.get("MATRICULE", "")).strip()
        if not matricule or matricule == "nan":
            continue

        def clean(val):
            v = str(val).strip()
            return v if v and v != "nan" else ""

        cursor.execute(
            "INSERT OR IGNORE INTO employees (matricule, nom, prenom, processus, uap, eap) VALUES (?, ?, ?, ?, ?, ?)",
            (matricule, clean(row.get("NOM")), clean(row.get("PRENOM")),
             clean(row.get("PROCESSUS")), clean(row.get("UAP")), clean(row.get("EAP")))
        )
        if cursor.rowcount > 0:
            inserted_employees += 1

        emp_id = cursor.execute(
            "SELECT id FROM employees WHERE matricule = ?", (matricule,)
        ).fetchone()["id"]

        for i, dc in enumerate(date_cols):
            pc = pps_cols[i] if i < len(pps_cols) else ""
            date_val = row[dc]
            pps_name = str(pc).replace("\n", " ").strip() if pc else f"PPS {i}"
            pps_val = row[pc] if pc else None

            statut = compute_statut(date_val, pps_val)
            raw_value = str(pps_val).strip() if pd.notna(pps_val) and isinstance(pps_val, str) else None

            if statut == "en_cours":
                en_cours_count += 1

            date_str = date_val.strftime("%Y-%m-%d") if pd.notna(date_val) and isinstance(date_val, (pd.Timestamp, datetime)) else None
            cursor.execute(
                "INSERT INTO pps_assignments (employee_id, pps_name, date_debut_validite, statut, raw_value) VALUES (?, ?, ?, ?, ?)",
                (emp_id, pps_name, date_str, statut, raw_value)
            )
            inserted_assignments += 1

    conn.commit()
    conn.close()

    print(f"Migration terminee !")
    print(f"  Employes inseres    : {inserted_employees}")
    print(f"  Assignations PPS    : {inserted_assignments}")
    print(f"  Dont 'en_cours' (EC): {en_cours_count}")

    # Verification
    from backend.services.database import build_employee_dataset
    ds = build_employee_dataset()
    total_en_cours = sum(e.get("en_cours", 0) for e in ds)
    print(f"  Verification dataset : {total_en_cours} PPS en_cours")


if __name__ == "__main__":
    migrate()

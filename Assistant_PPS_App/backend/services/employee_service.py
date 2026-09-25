from datetime import datetime

from backend.services.database import build_employee_dataset, get_dataset_meta as db_meta
from backend.services.qualification_engine import normalize_text


def get_meta_data():
    return db_meta()


def analyze_employees():
    data = build_employee_dataset()
    return {
        "total_employees": len(data),
        "data": data
    }

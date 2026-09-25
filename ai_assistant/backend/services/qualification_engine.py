import unicodedata
from backend.services.database import (
    build_employee_dataset, get_dataset_meta as db_get_meta
)


def normalize_text(text: str) -> str:
    text = unicodedata.normalize('NFKD', text).encode('ASCII', 'ignore').decode('ASCII')
    return text.lower().strip()


def load_data():
    return build_employee_dataset()


def get_dataset_meta():
    return db_get_meta()

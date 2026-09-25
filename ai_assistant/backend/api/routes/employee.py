from fastapi import APIRouter

from backend.services.employee_service import (
    get_meta_data,
    analyze_employees
)

router = APIRouter(
    prefix="/api/employee",
    tags=["Employee"]
)


@router.get("/meta")
def meta():

    return get_meta_data()


@router.get("/analyze")
def analyze():

    return analyze_employees()
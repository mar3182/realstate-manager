from fastapi import APIRouter, Depends, Request
from sqlalchemy.orm import Session
from typing import List

from ...core.database import get_db
from ...models.property import Property
from ...schemas.property import PropertyCreate, PropertyRead
from ...core.tenant import resolve_current_agency
from ...core.auth import try_get_current_user
from ...models.user import User

router = APIRouter()


@router.post("/", response_model=PropertyRead)
async def create_property(
    payload: PropertyCreate,
    request: Request,
    db: Session = Depends(get_db),
    current_user: User | None = Depends(try_get_current_user),  # optional; if token provided use agency
):
    if current_user:
        agency = current_user.agency  # type: ignore[assignment]
    else:
        agency = resolve_current_agency(request, db)  # type: ignore[arg-type]
    prop = Property(
        title=payload.title,
        description=payload.description,
        price=payload.price,
        address=payload.address,
        agency_id=agency.id,
    )
    db.add(prop)
    db.commit()
    db.refresh(prop)
    return prop


@router.get("/", response_model=List[PropertyRead])
async def list_properties(
    request: Request,
    db: Session = Depends(get_db),
    current_user: User | None = Depends(try_get_current_user),
):
    if current_user:
        agency_id = current_user.agency_id
    else:
        agency = resolve_current_agency(request, db)  # type: ignore[arg-type]
        agency_id = agency.id
    return db.query(Property).filter(Property.agency_id == agency_id).all()


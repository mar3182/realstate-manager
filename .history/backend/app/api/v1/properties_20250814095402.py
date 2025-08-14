from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from typing import List

from ...core.database import get_db
from ...models.property import Property
from ...models.agency import Agency
from ...schemas.property import PropertyCreate, PropertyRead
from ...core.tenant import get_current_agency

router = APIRouter()


@router.post("/", response_model=PropertyRead)
async def create_property(payload: PropertyCreate, db: Session = Depends(get_db), agency: Agency = Depends(get_current_agency)):

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
async def list_properties(db: Session = Depends(get_db), agency: Agency = Depends(get_current_agency)):
    return db.query(Property).filter(Property.agency_id == agency.id).all()


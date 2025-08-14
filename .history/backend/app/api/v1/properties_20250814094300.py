from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from typing import List

from ...core.database import get_db, Base, engine
from ...models.property import Property
from ...models.agency import Agency
from ...schemas.property import PropertyCreate, PropertyRead

router = APIRouter()


@router.on_event("startup")
def startup_create_tables():  # Replace with Alembic migrations later
    Base.metadata.create_all(bind=engine)


@router.post("/", response_model=PropertyRead)
async def create_property(payload: PropertyCreate, db: Session = Depends(get_db)):
    agency = db.query(Agency).filter(Agency.id == (payload.agency_id or 1)).first()
    if not agency:
        agency = Agency(id=payload.agency_id or 1, name="Default Agency")
        db.add(agency)
        db.commit()
        db.refresh(agency)

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
async def list_properties(db: Session = Depends(get_db)):
    return db.query(Property).all()


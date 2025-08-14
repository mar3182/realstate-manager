from fastapi import APIRouter
from pydantic import BaseModel
from typing import List

router = APIRouter()

class PropertyIn(BaseModel):
    title: str
    description: str | None = None
    price: float | None = None
    address: str | None = None

class PropertyOut(PropertyIn):
    id: int

_FAKE_DB: list[PropertyOut] = []

@router.post("/", response_model=PropertyOut)
async def create_property(payload: PropertyIn):
    new = PropertyOut(id=len(_FAKE_DB)+1, **payload.dict())
    _FAKE_DB.append(new)
    return new

@router.get("/", response_model=List[PropertyOut])
async def list_properties():
    return _FAKE_DB

from pydantic import BaseModel

class PropertyBase(BaseModel):
    title: str
    description: str | None = None
    price: float | None = None
    address: str | None = None

class PropertyCreate(PropertyBase):
    agency_id: int | None = None  # deprecated; tenant derived automatically

class PropertyRead(PropertyBase):
    id: int
    agency_id: int
    class Config:
        orm_mode = True

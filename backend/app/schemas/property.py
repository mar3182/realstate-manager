from pydantic import BaseModel

try:  # pydantic v2
    from pydantic import ConfigDict  # type: ignore
    _HAS_V2 = True
except ImportError:  # pydantic v1
    ConfigDict = None  # type: ignore
    _HAS_V2 = False

class PropertyBase(BaseModel):
    title: str
    description: str | None = None
    price: float | None = None
    address: str | None = None
    cover_image_url: str | None = None
    images: list[str] | None = None

class PropertyCreate(PropertyBase):
    agency_id: int | None = None  # deprecated; tenant inferred

class PropertyRead(PropertyBase):
    id: int
    agency_id: int
    if _HAS_V2:
        model_config = ConfigDict(from_attributes=True)  # type: ignore
    else:  # v1 fallback
        class Config:  # type: ignore
            orm_mode = True

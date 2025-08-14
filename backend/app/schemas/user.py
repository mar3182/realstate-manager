from pydantic import BaseModel, EmailStr
from typing import Optional

# Shared properties
class UserBase(BaseModel):
    email: EmailStr


class UserCreate(UserBase):
    password: str
    # Optional agency name to provision an agency at registration time if needed
    agency_name: Optional[str] = None


class UserLogin(BaseModel):
    email: EmailStr
    password: str


class UserRead(UserBase):
    id: int
    role: str
    agency_id: int

    class Config:
        from_attributes = True  # enable ORM mode (Pydantic v2 style)


class Token(BaseModel):
    access_token: str
    token_type: str = "bearer"


class TokenPayload(BaseModel):
    sub: int  # user id
    agency_id: int
    role: str
    exp: Optional[int] = None  # epoch expiration timestamp

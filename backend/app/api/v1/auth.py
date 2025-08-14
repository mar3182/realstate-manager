from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from fastapi.security import OAuth2PasswordRequestForm

from ...core.database import get_db
from ...core.auth import register_user, authenticate_user, create_access_token, get_current_user
from ...schemas.user import UserCreate, UserRead, Token, UserLogin
from ...models.user import User

router = APIRouter()


@router.post("/register", response_model=UserRead)
def register(payload: UserCreate, db: Session = Depends(get_db)):
    user = register_user(db, email=payload.email, password=payload.password, agency_name=payload.agency_name)
    return user


@router.post("/login", response_model=Token)
def login(form_data: OAuth2PasswordRequestForm = Depends(), db: Session = Depends(get_db)):
    user = authenticate_user(db, email=form_data.username, password=form_data.password)
    if not user:
        raise HTTPException(status_code=400, detail="Incorrect email or password")
    token = create_access_token(user=user)
    return Token(access_token=token)


@router.get("/me", response_model=UserRead)
def me(current_user: User = Depends(get_current_user)):
    return current_user

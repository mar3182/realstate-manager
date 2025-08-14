from datetime import datetime, timedelta, timezone
from typing import Optional, Any
from jose import jwt, JWTError
from fastapi import Depends, HTTPException, status
from fastapi.security import OAuth2PasswordBearer
from sqlalchemy.orm import Session

from .config import get_settings
from .database import get_db
from .security import verify_password, hash_password
from ..models.user import User
from ..models.agency import Agency
from ..schemas.user import TokenPayload

settings = get_settings()

oauth2_scheme = OAuth2PasswordBearer(tokenUrl=f"{settings.api_v1_prefix}/auth/login")
# Optional scheme (no automatic 401) for endpoints that allow anonymous + header-based tenancy
optional_oauth2_scheme = OAuth2PasswordBearer(tokenUrl=f"{settings.api_v1_prefix}/auth/login", auto_error=False)

ACCESS_TOKEN_EXPIRE_MINUTES = 60 * 8  # 8 hours for now


def create_access_token(*, user: User, expires_delta: Optional[timedelta] = None) -> str:
    expire = datetime.now(timezone.utc) + (expires_delta or timedelta(minutes=ACCESS_TOKEN_EXPIRE_MINUTES))
    payload = {
        # JWT spec expects sub as string
        "sub": str(user.id),
        "agency_id": user.agency_id,
        "role": user.role,
        "exp": int(expire.timestamp()),
    }
    return jwt.encode(payload, settings.jwt_secret_key, algorithm=settings.jwt_algorithm)


def get_current_user(token: str = Depends(oauth2_scheme), db: Session = Depends(get_db)) -> User:
    credentials_exception = HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail="Could not validate credentials",
        headers={"WWW-Authenticate": "Bearer"},
    )
    try:
        raw_payload: dict[str, Any] = jwt.decode(token, settings.jwt_secret_key, algorithms=[settings.jwt_algorithm])
    except JWTError:
        raise credentials_exception
    # Coerce types
    if "sub" not in raw_payload:
        raise credentials_exception
    try:
        raw_payload["sub"] = int(raw_payload["sub"])
    except (TypeError, ValueError):
        raise credentials_exception
    try:
        token_data = TokenPayload(**raw_payload)
    except Exception:  # Pydantic validation error
        raise credentials_exception
    user = db.get(User, token_data.sub)
    if not user:
        # print(f"[DEBUG get_current_user] user id {token_data.sub} not found")
        raise credentials_exception
    return user


def try_get_current_user(token: Optional[str] = Depends(optional_oauth2_scheme), db: Session = Depends(get_db)) -> Optional[User]:  # noqa: D401
    """Return current user or None if no/invalid bearer token provided."""
    if not token:
        return None
    try:
        return get_current_user(token=token, db=db)
    except HTTPException:
        return None


def register_user(db: Session, *, email: str, password: str, agency_name: Optional[str] = None) -> User:
    existing = db.query(User).filter(User.email == email).first()
    if existing:
        raise HTTPException(status_code=400, detail="Email already registered")

    # If agency_name given create agency else reuse first agency or create generic
    if agency_name:
        agency = Agency(name=agency_name)
        db.add(agency)
        db.flush()  # get id
    else:
        agency = db.query(Agency).first()
        if not agency:
            agency = Agency(name="Default Agency")
            db.add(agency)
            db.flush()

    user = User(email=email, hashed_password=hash_password(password), agency_id=agency.id)
    db.add(user)
    db.commit()
    db.refresh(user)
    return user


def authenticate_user(db: Session, *, email: str, password: str) -> Optional[User]:
    user = db.query(User).filter(User.email == email).first()
    if not user:
        return None
    if not verify_password(password, user.hashed_password):
        return None
    return user

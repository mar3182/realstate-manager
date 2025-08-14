from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker, DeclarativeBase, scoped_session
from ..core.config import get_settings

settings = get_settings()

class Base(DeclarativeBase):
    pass

engine = create_engine(settings.database_url, future=True, echo=settings.debug)
SessionLocal = scoped_session(sessionmaker(autocommit=False, autoflush=False, bind=engine, future=True))

def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

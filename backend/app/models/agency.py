from sqlalchemy import String
from sqlalchemy.orm import Mapped, mapped_column, relationship
from ..core.database import Base

class Agency(Base):
    __tablename__ = "agencies"

    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    name: Mapped[str] = mapped_column(String(200), unique=True, index=True)
    plan: Mapped[str] = mapped_column(String(50), default="starter")

    users: Mapped[list["User"]] = relationship(back_populates="agency")
    properties: Mapped[list["Property"]] = relationship(back_populates="agency")

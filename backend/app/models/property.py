from sqlalchemy import String, ForeignKey, Numeric, Text, JSON
from sqlalchemy.orm import Mapped, mapped_column, relationship
from ..core.database import Base

class Property(Base):
    __tablename__ = "properties"

    id: Mapped[int] = mapped_column(primary_key=True, index=True)
    title: Mapped[str] = mapped_column(String(255))
    description: Mapped[str | None] = mapped_column(Text(), nullable=True)
    price: Mapped[float | None] = mapped_column(Numeric(12, 2), nullable=True)
    address: Mapped[str | None] = mapped_column(String(255), nullable=True)
    cover_image_url: Mapped[str | None] = mapped_column(String(500), nullable=True)
    images: Mapped[list[str] | None] = mapped_column(JSON(), nullable=True)
    agency_id: Mapped[int] = mapped_column(ForeignKey("agencies.id", ondelete="CASCADE"), index=True)

    agency: Mapped["Agency"] = relationship(back_populates="properties")

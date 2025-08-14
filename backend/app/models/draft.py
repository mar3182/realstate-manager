from sqlalchemy import String, Text, ForeignKey
from sqlalchemy.orm import Mapped, mapped_column
from ..core.database import Base


class Draft(Base):
    __tablename__ = "drafts"

    id: Mapped[int] = mapped_column(primary_key=True)
    type: Mapped[str] = mapped_column(String(50))
    source_text: Mapped[str | None] = mapped_column(Text(), nullable=True)
    content: Mapped[str] = mapped_column(Text())
    status: Mapped[str] = mapped_column(String(30), default="draft")
    agency_id: Mapped[int] = mapped_column(ForeignKey("agencies.id", ondelete="CASCADE"), index=True)

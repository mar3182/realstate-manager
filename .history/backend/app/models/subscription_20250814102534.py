from sqlalchemy import String, ForeignKey
from sqlalchemy.orm import Mapped, mapped_column
from ..core.database import Base


class Subscription(Base):
    __tablename__ = "subscriptions"

    id: Mapped[int] = mapped_column(primary_key=True)
    agency_id: Mapped[int] = mapped_column(ForeignKey("agencies.id", ondelete="CASCADE"), index=True)
    plan: Mapped[str] = mapped_column(String(50), default="starter")
    status: Mapped[str] = mapped_column(String(30), default="active")
    stripe_customer_id: Mapped[str | None] = mapped_column(String(100), nullable=True)

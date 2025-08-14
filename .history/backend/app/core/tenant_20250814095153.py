from fastapi import Request, HTTPException, Depends
from starlette.middleware.base import BaseHTTPMiddleware
from sqlalchemy.orm import Session
from .database import get_db
from ..models.agency import Agency

TENANT_HEADER = "X-Tenant-ID"

class TenantMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next):
        tenant_id = request.headers.get(TENANT_HEADER)
        if tenant_id is not None:
            try:
                tenant_id_int = int(tenant_id)
            except ValueError:
                raise HTTPException(status_code=400, detail="Invalid tenant header")
            request.state.tenant_id = tenant_id_int
        else:
            request.state.tenant_id = 1
        return await call_next(request)

def get_current_agency(request: Request, db: Session = Depends(get_db)) -> Agency:
    tenant_id: int = getattr(request.state, "tenant_id", None)
    if tenant_id is None:
        raise HTTPException(status_code=500, detail="Tenant not resolved")
    agency = db.query(Agency).filter(Agency.id == tenant_id).first()
    if not agency:
        agency = Agency(id=tenant_id, name=f"Agency {tenant_id}")
        db.add(agency)
        db.commit()
        db.refresh(agency)
    return agency

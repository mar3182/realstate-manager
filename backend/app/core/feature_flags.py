from fastapi import Depends, HTTPException
from .tenant import get_current_agency
from ..models.agency import Agency

FEATURES = {
    "starter": {"properties.max": 100, "crm.enabled": False, "payments.enabled": False},
    "pro": {"properties.max": 500, "crm.enabled": False, "payments.enabled": False},
    "business": {"properties.max": 2000, "crm.enabled": True, "payments.enabled": True},
    "enterprise": {"properties.max": 10000, "crm.enabled": True, "payments.enabled": True},
}

def require_feature(flag: str):
    def dependency(agency: Agency = Depends(get_current_agency)):
        plan = agency.plan or "starter"
        plan_features = FEATURES.get(plan, {})
        if flag.endswith(".enabled"):
            if not plan_features.get(flag, False):
                raise HTTPException(status_code=403, detail=f"Feature '{flag}' not available for plan {plan}")
        else:
            if flag not in plan_features:
                raise HTTPException(status_code=403, detail=f"Feature '{flag}' not defined for plan {plan}")
    return dependency

from fastapi import APIRouter
from pydantic import BaseModel

router = APIRouter()

class DraftRequest(BaseModel):
    raw_text: str

class DraftResponse(BaseModel):
    summary: str
    description: str
    social_posts: list[str]

@router.post("/draft", response_model=DraftResponse)
async def draft_content(req: DraftRequest):
    # Placeholder AI logic
    base = req.raw_text[:50]
    return DraftResponse(
        summary=f"Summary: {base}...",
        description=f"Detailed description generated from: {req.raw_text}",
        social_posts=[
            f"New Property: {base}... #realestate",
            f"Check this out: {base}..."
        ]
    )

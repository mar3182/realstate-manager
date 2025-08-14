try:
    from alembic import command  # type: ignore
    from alembic.config import Config  # type: ignore
    _ALEMBIC_AVAILABLE = True
except ImportError:  # pragma: no cover
    _ALEMBIC_AVAILABLE = False
import os
import pathlib
import sys
import pytest
from sqlalchemy import text

backend_dir = pathlib.Path(__file__).resolve().parents[1]
if str(backend_dir) not in sys.path:
    sys.path.insert(0, str(backend_dir))

from app.core.database import engine  # noqa: E402

def pytest_sessionstart(session):
    if not _ALEMBIC_AVAILABLE:
        # Provide a clear actionable error early
        session.exit("Alembic not installed in current interpreter. Activate the project's virtualenv (source backend/.venv/bin/activate) and install requirements: pip install -r backend/requirements.txt")
    alembic_ini = backend_dir / 'alembic.ini'
    cfg = Config(str(alembic_ini))
    db_url = os.getenv('DATABASE_URL')
    if db_url:
        cfg.set_main_option('sqlalchemy.url', db_url)
    command.upgrade(cfg, 'head')


@pytest.fixture(autouse=True)
def _clean_db():
    # Order matters due to FKs
    with engine.begin() as conn:
        for table in [
            'properties',
            'drafts',
            'subscriptions',
            'users',
            'agencies',
        ]:
            conn.execute(text(f'DELETE FROM {table}'))
    yield

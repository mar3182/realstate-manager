from alembic import command
from alembic.config import Config
import os
import pathlib
import pytest
from sqlalchemy import text
from app.core.database import engine

def pytest_sessionstart(session):
    backend_dir = pathlib.Path(__file__).resolve().parents[1]
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

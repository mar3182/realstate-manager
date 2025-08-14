from alembic import command
from alembic.config import Config
import os
import pathlib

def pytest_sessionstart(session):
    backend_dir = pathlib.Path(__file__).resolve().parents[1]
    alembic_ini = backend_dir / 'alembic.ini'
    cfg = Config(str(alembic_ini))
    db_url = os.getenv('DATABASE_URL')
    if db_url:
        cfg.set_main_option('sqlalchemy.url', db_url)
    command.upgrade(cfg, 'head')

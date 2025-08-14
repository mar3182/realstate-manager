# Simple project automation
# Usage: make <target>

PYTHON ?= python
PIP ?= pip
VENV ?= .venv
ACTIVATE = . $(VENV)/bin/activate

.PHONY: help install upgrade dev test lint format clean db-revision db-up run

help:
	@echo "Common targets:"
	@echo "  make install     Create venv & install dependencies"
	@echo "  make dev         Run API with reload"
	@echo "  make test        Run pytest (unit tests)"
	@echo "  make lint        Run ruff lint + type check placeholder"
	@echo "  make format      Run ruff format"
	@echo "  make db-revision msg=...  Create Alembic revision"
	@echo "  make db-up       Apply Alembic migrations"
	@echo "  make clean       Remove caches & build artifacts"

$(VENV)/bin/activate: requirements.txt
	@test -d $(VENV) || $(PYTHON) -m venv $(VENV)
	@$(ACTIVATE); $(PIP) install --upgrade pip
	@$(ACTIVATE); $(PIP) install -r requirements.txt
	@touch $(VENV)/bin/activate

install: $(VENV)/bin/activate

upgrade: $(VENV)/bin/activate
	@$(ACTIVATE); $(PIP) install --upgrade -r requirements.txt

dev: install
	@$(ACTIVATE); uvicorn app.main:app --reload

test: install
	@$(ACTIVATE); pytest -q

lint: install
	@$(ACTIVATE); ruff check .

format: install
	@$(ACTIVATE); ruff format .

clean:
	rm -rf __pycache__ **/__pycache__ .pytest_cache build dist *.egg-info .mypy_cache .ruff_cache

# Alembic helpers (expects env var DB_URL or config default)
# Usage: make db-revision msg="add users table"

db-revision: install
	@$(ACTIVATE); alembic revision --autogenerate -m "$(msg)"

db-up: install
	@$(ACTIVATE); alembic upgrade head

run: dev

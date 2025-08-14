"""initial tables

Revision ID: 20240814_0001
Revises: 
Create Date: 2024-08-14 00:00:00
"""
from alembic import op
import sqlalchemy as sa

revision = '20240814_0001'

down_revision = None
branch_labels = None
depends_on = None

def upgrade():
    op.create_table(
        'agencies',
        sa.Column('id', sa.Integer(), primary_key=True),
        sa.Column('name', sa.String(length=200), nullable=False, unique=True),
        sa.Column('plan', sa.String(length=50), nullable=False, server_default='starter'),
    )
    op.create_index('ix_agencies_id', 'agencies', ['id'])
    op.create_index('ix_agencies_name', 'agencies', ['name'])

    op.create_table(
        'users',
        sa.Column('id', sa.Integer(), primary_key=True),
        sa.Column('email', sa.String(length=255), nullable=False, unique=True),
        sa.Column('hashed_password', sa.String(), nullable=False),
        sa.Column('role', sa.String(length=30), nullable=False, server_default='agent'),
        sa.Column('agency_id', sa.Integer(), sa.ForeignKey('agencies.id', ondelete='CASCADE'), nullable=False),
    )
    op.create_index('ix_users_id', 'users', ['id'])
    op.create_index('ix_users_email', 'users', ['email'])

    op.create_table(
        'subscriptions',
        sa.Column('id', sa.Integer(), primary_key=True),
        sa.Column('agency_id', sa.Integer(), sa.ForeignKey('agencies.id', ondelete='CASCADE'), nullable=False),
        sa.Column('plan', sa.String(length=50), nullable=False, server_default='starter'),
        sa.Column('status', sa.String(length=30), nullable=False, server_default='active'),
        sa.Column('stripe_customer_id', sa.String(length=100)),
    )

    op.create_table(
        'properties',
        sa.Column('id', sa.Integer(), primary_key=True),
        sa.Column('title', sa.String(length=255), nullable=False),
        sa.Column('description', sa.Text()),
        sa.Column('price', sa.Numeric(12, 2)),
        sa.Column('address', sa.String(length=255)),
        sa.Column('agency_id', sa.Integer(), sa.ForeignKey('agencies.id', ondelete='CASCADE'), nullable=False),
    )
    op.create_index('ix_properties_id', 'properties', ['id'])
    op.create_index('ix_properties_agency_id', 'properties', ['agency_id'])

    op.create_table(
        'drafts',
        sa.Column('id', sa.Integer(), primary_key=True),
        sa.Column('type', sa.String(length=50), nullable=False),
        sa.Column('source_text', sa.Text()),
        sa.Column('content', sa.Text(), nullable=False),
        sa.Column('status', sa.String(length=30), nullable=False, server_default='draft'),
        sa.Column('agency_id', sa.Integer(), sa.ForeignKey('agencies.id', ondelete='CASCADE'), nullable=False),
    )
    op.create_index('ix_drafts_type', 'drafts', ['type'])
    op.create_index('ix_drafts_agency_id', 'drafts', ['agency_id'])


def downgrade():
    op.drop_table('drafts')
    op.drop_table('properties')
    op.drop_table('subscriptions')
    op.drop_table('users')
    op.drop_table('agencies')

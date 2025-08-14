"""add property image fields

Revision ID: 20250814_0002
Revises: 20240814_0001
Create Date: 2025-08-14 00:00:00
"""
from alembic import op
import sqlalchemy as sa

revision = '20250814_0002'
down_revision = '20240814_0001'
branch_labels = None
depends_on = None

def upgrade():
    with op.batch_alter_table('properties') as batch_op:
        batch_op.add_column(sa.Column('cover_image_url', sa.String(length=500)))
        batch_op.add_column(sa.Column('images', sa.JSON()))


def downgrade():
    with op.batch_alter_table('properties') as batch_op:
        batch_op.drop_column('images')
        batch_op.drop_column('cover_image_url')

# Realty Assistant WordPress Plugin (MVP)

This plugin integrates the FastAPI backend with a WordPress site to:

- Register a custom post type (rai_property)
- Sync properties from the backend (manual button for now)
- Use either a stored JWT auth token or X-Tenant-ID header for multi-tenancy
- Pull cover image & set as featured image (downloads when URL changes)
- Store gallery image URLs (no download yet) as post meta

## Setup

1. Generate a JWT via backend (register/login) OR decide on a tenant id.
2. Copy `wordpress-plugin/realty-assistant` folder into your WP `wp-content/plugins/` directory.
3. Activate the plugin in WP Admin.
4. Go to Realty Assistant -> Settings and fill:
   - API Base URL (e.g. `https://your-backend-domain/api/v1`)
   - Auth Token (JWT) OR leave blank and set Tenant ID.
5. Use the Sync Properties Now button on the dashboard page.

## Roadmap Next

- AI description generation button per property.
- Background cron sync (hourly).
- Mapping backend property IDs to post meta to avoid title collisions.
- Error logging & transient cache of last response.
- Download gallery images and attach to media library.
- WP REST endpoint to trigger sync remotely.

## Security Notes

- Keep JWT private; only admins should access settings.
- Future improvement: issue short-lived tokens instead of static.


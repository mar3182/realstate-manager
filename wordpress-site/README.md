# Realty Assistant Vendor Site

This directory will host a local WordPress instance (dockerized) to:

- Serve as the marketing / SaaS sales site.
- Provide a place to install & test the Realty Assistant plugin.
- Allow local development with reproducible environment.

## Stack

- WordPress (php-fpm + nginx) via Docker Compose
- MariaDB
- Mailhog (optional) for email capture

## Quick Start

1. Copy the plugin into the `wp-content/plugins` directory (mounted).
2. Run `docker compose up -d`.
3. Visit `http://localhost:8080` to complete WP install.
4. Activate the Realty Assistant plugin in WP Admin.

## Planned

- Add a child theme for marketing pages.
- Add landing page templates & pricing section.
- Add simple purchase CTA linking to app onboarding (future).

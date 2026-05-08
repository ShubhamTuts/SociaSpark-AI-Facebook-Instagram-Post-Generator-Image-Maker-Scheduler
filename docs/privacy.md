# Privacy

SociaSpark AI is designed as a WordPress.org-friendly free core.

## No Tracking

The plugin does not collect telemetry, track admin behavior, or send usage analytics to Codefreex.

## No Automatic External Requests

External requests happen only when an administrator:

- Generates AI captions.
- Generates an AI image.
- Generates a video script.
- Runs AI Brand Intelligence analysis.
- Publishes or schedules content to Meta platforms.
- Tests a configured AI provider or Meta connection.

## Brand Intelligence

Brand Intelligence stores selected source excerpts locally in custom database tables. It is not model fine-tuning. Admins choose sources and explicitly start analysis.

Supported free-core sources are WordPress posts, pages, WooCommerce products when active, taxonomies, site settings, Media Library metadata, pasted examples, and uploaded TXT, MD, CSV, or JSON files. Unsupported upload types are rejected.

The free core does not scrape arbitrary URLs.

## Secrets

API keys and access tokens are encrypted in the database. Decrypted values are used only server-side for provider requests and publishing.

Secrets are not returned to the React dashboard. Logs redact likely API keys, bearer tokens, access tokens, and unusually long provider responses.

## Uninstall

By default, plugin data is retained on uninstall to prevent accidental content loss. If "Delete data on uninstall" is enabled in settings, uninstall removes SociaSpark custom tables and options.

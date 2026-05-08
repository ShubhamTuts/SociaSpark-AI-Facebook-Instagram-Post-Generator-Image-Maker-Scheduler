# Release Checklist

## Build Commands

```bash
npm ci
npm run lint:js
npm run lint:css
npm run build
composer install
composer phpcs
npm run plugin-zip
```

## Testing Checklist

- Activate on WordPress 6.6 and latest 6.9.x.
- Confirm all custom tables are created.
- Confirm admin dashboard loads only on SociaSpark AI pages.
- Save settings and verify secrets are encrypted in the database.
- Use provider tests for OpenAI text, OpenAI image, Gemini text, and Claude text when keys are available.
- Generate captions with each configured text provider.
- Generate an OpenAI image and verify it saves to Media Library.
- Build a local Brand Intelligence profile from WP sources and uploaded text.
- Create a post draft from generated output.
- Save leftover ideas to Idea Bank.
- Schedule Facebook and Instagram jobs.
- Test Meta connections before publishing.
- Confirm WP-Cron publishes due jobs.
- Confirm failed jobs are logged without secrets.
- Confirm uninstall respects the delete-data setting.

## WordPress.org Submission Checklist

- `readme.txt` validates.
- Main plugin header matches release version.
- `Tested up to` remains `6.9` until WordPress 7.0 final is actually tested.
- No frontend assets are enqueued.
- No external CDN scripts, styles, or fonts.
- No tracking or phone-home behavior.
- External services are documented in `readme.txt`.
- Zip excludes `node_modules`, `vendor`, logs, caches, and dev junk.
- Production zip includes `admin/build/index.js`, `admin/build/index.css`, `admin/build/index-rtl.css`, and `admin/build/index.asset.php`.
- Plugin Check "Plugin repo" category passes or all findings are reviewed.

<p align="center">
  <img src="assets/svg/sociaspark-mark.svg" alt="SociaSpark AI logo" width="96">
</p>

# SociaSpark AI for WordPress

**AI social media post generator, Facebook Page publisher, Instagram scheduler, and brand-aware content workflow for WordPress.**

![WordPress 6.6+](https://img.shields.io/badge/WordPress-6.6%2B-21759B?logo=wordpress&logoColor=white)
![PHP 8.0+](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)
![License GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-blue.svg)

SociaSpark AI is a WordPress plugin for AI social media post generation, WordPress content repurposing, Facebook Page publishing, and Instagram scheduling without leaving `wp-admin`.

This repository contains the free-core WordPress plugin source, admin dashboard, publishing integrations, and setup docs for a privacy-friendly social media automation workflow.

## Why SociaSpark AI

- WordPress-first workflow instead of copying content into separate AI tools
- Brand-aware caption generation using your own site content and approved examples
- Bring-your-own-key AI setup with OpenAI, Gemini, and Claude support for text
- OpenAI image generation with Media Library storage and reuse
- Facebook Page publishing and Instagram Business/Creator image publishing
- Draft, schedule, retry, and activity-log flows built directly into the plugin
- Free core with no Codefreex SaaS requirement, no telemetry, and no frontend bloat

## Core Features

- AI caption generator for Facebook and Instagram
- AI social post generator with multiple content variations
- WordPress post, page, and WooCommerce product repurposing
- Brand Intelligence Profile built from selected WordPress content and admin sources
- OpenAI image generator for social creatives
- Native image composer for quick branded graphics
- AI video script generator for short-form content ideas
- Content calendar, drafts, scheduled posts, and failed-job retry flows
- Facebook Page text and image posting
- Instagram image publishing for Business and Creator accounts
- Safe diagnostics for provider failures, publishing errors, and scheduler events
- Encrypted API key and Meta token storage

## Best For

- Small business owners managing Facebook and Instagram in-house
- Coaches, consultants, and creators repurposing blog content into social posts
- Agencies building lightweight social workflows inside WordPress
- Ecommerce stores creating promotional content from products
- Site owners who want an AI social media scheduler without another SaaS dashboard

## How The Product Flow Works

1. Build a Brand Intelligence Profile from selected WordPress content, examples, and approved assets.
2. Generate captions, hooks, hashtags, image prompts, or video scripts with your chosen AI provider.
3. Edit the selected draft, attach media, and save it as a draft or queue it for publishing.
4. Publish now or schedule for Facebook Pages and Instagram image posts.
5. Review dashboard metrics, activity logs, failed jobs, and retries inside WordPress admin.

## Supported Platforms And Providers

### Publishing

- Facebook Pages
- Instagram Business accounts
- Instagram Creator accounts

### Text AI Providers

- OpenAI
- Google Gemini
- Anthropic Claude

### Image Generation

- OpenAI image models

## Requirements

- WordPress 6.6 or newer
- PHP 8.0 or newer
- Outbound HTTPS access for AI provider and Meta API requests
- Optional WooCommerce support for product-based content repurposing

## Installation

### For Site Owners

1. Download this repository or create a plugin ZIP.
2. Upload the plugin folder to `/wp-content/plugins/` or install the ZIP from WordPress admin.
3. Activate **SociaSpark AI**.
4. Open the SociaSpark AI dashboard in `wp-admin`.
5. Add your AI provider credentials and Meta connections before generating or publishing.

### For Developers

```bash
npm install
npm run build
```

Optional checks:

```bash
npm run lint:js
npm run lint:css
```

## Setup Guides

- [AI provider setup](docs/ai-provider-setup.md)
- [Meta setup for Facebook and Instagram](docs/meta-setup.md)
- [Privacy notes](docs/privacy.md)
- [Release checklist](docs/release-checklist.md)

## Repository Structure

```text
admin/       React-based WordPress admin app
assets/      Brand assets and static resources
docs/        Setup, privacy, and release documentation
includes/    PHP plugin core, schedulers, publishers, and providers
languages/   Translation-related files
tests/       Testing notes and placeholders
```

## What Makes This Different

Most AI social tools start with a blank prompt and live outside your site. SociaSpark AI starts with your WordPress content, your business voice, and your publishing workflow. That makes it useful for teams who want an AI social media post generator, a WordPress social scheduler, and a simple Facebook and Instagram content pipeline in one place.

## Frequently Asked Questions

### What does SociaSpark AI do?

It is a WordPress plugin for generating social media captions, AI images, and short-form content ideas, then publishing or scheduling Facebook and Instagram posts from inside WordPress.

### Can I schedule Facebook and Instagram posts from WordPress?

Yes. The plugin includes draft, schedule, publish, retry, and activity-log flows for supported Meta destinations.

### Does it support OpenAI, Gemini, and Claude?

Yes for text generation. OpenAI is also used for image generation in the free core.

### Does it require a Codefreex SaaS account?

No. The free core is designed to be useful without a separate Codefreex SaaS account.

### Does Instagram publishing need a public image URL?

Yes. Meta requires a publicly reachable HTTPS image URL for Instagram image publishing.

## Contributing

Issues and pull requests are welcome. If you are planning a large feature or workflow change, open an issue first so the direction stays aligned with the product goals and WordPress plugin constraints.

## License

GPL-2.0-or-later. See [readme.txt](readme.txt) and the plugin header for project licensing details.

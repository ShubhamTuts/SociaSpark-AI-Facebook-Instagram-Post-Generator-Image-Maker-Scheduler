=== SociaSpark AI - Facebook & Instagram Post Generator, Image Maker & Scheduler ===
Contributors: codefreex
Tags: social media scheduler, facebook auto poster, instagram scheduler, ai content, ai image generator
Requires at least: 6.6
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate brand-aware AI captions, OpenAI images, video scripts, Media Library graphics, and scheduled Facebook/Instagram posts from WordPress.

== Description ==

SociaSpark AI is an AI social media post generator and social media scheduler for WordPress.
It helps small businesses, creators, agencies, coaches, course creators, local businesses, ecommerce stores, and site owners create brand-aware Facebook and Instagram content directly inside wp-admin.

Instead of copying WordPress posts into separate AI tools, SociaSpark AI turns your existing WordPress content into ready-to-edit social posts.
It also creates Instagram captions, Facebook Page posts, AI images, short-form video scripts, drafts, and scheduled campaigns.

The free core is built to be useful on its own. There is no paid license system, no trialware lock, no tracking, no frontend bloat, and no required SaaS account from Codefreex.

= Why SociaSpark AI? =

Most AI writing tools start with a blank prompt. SociaSpark AI starts with your WordPress site.

Use Brand Intelligence to create a local brand profile from approved sources such as posts, pages, WooCommerce products, categories, tags, site settings, Media Library metadata, pasted examples, and uploaded text files.
The profile can guide captions, hooks, CTAs, hashtags, image prompts, and video script ideas so your social content feels closer to your business voice.

Brand Intelligence is not AI model fine-tuning. It is a local structured profile used as prompt context only when an administrator chooses to generate content.

= Core Features =

* AI caption generator for Facebook and Instagram.
* AI social post generator with 3 distinct content variations.
* OpenAI, Google Gemini, and Anthropic Claude text generation support.
* Separate text provider/model and image provider/model selection.
* AI provider model catalog and credential test tools before generation.
* OpenAI image generator with images saved to the WordPress Media Library.
* AI video script generator for Reels-style and short-form social videos.
* WordPress post, page, and WooCommerce product repurposing.
* Brand Intelligence Profile from selected WordPress content and admin examples.
* Manual post composer for non-AI social posts.
* Facebook Page text and image publishing.
* Instagram Business and Creator image publishing.
* Content calendar for scheduled social posts.
* Drafts and editable platform-specific captions.
* Idea Bank for content ideas, unused AI outputs, and future posts.
* Basic WP-Cron scheduler for due Facebook and Instagram jobs.
* Dashboard analytics for drafts, scheduled posts, published posts, failed jobs, and connected accounts.
* Activity log for publishing results, API errors, and scheduler events.
* Media Library sync for generated and uploaded images.
* WordPress Media Library picker for existing social images.
* Secure encrypted storage for API keys and Meta access tokens.
* Pro-ready hooks for future add-ons without limiting the free plugin.

= AI Caption Generator =

Create platform-native captions from a topic, audience, tone, CTA, platform focus, and content type.

The Create Post flow supports audience and niche fields, pain points, desired outcomes, AI-assisted audience ideas, provider/model choice, three generated variations, editable selected drafts, and one-click saving of unused variations to the Idea Bank.

Supported content styles include:

* Standard posts.
* Promotional posts.
* Educational posts.
* Storytelling posts.
* Carousel copy.
* Reel script ideas.

SociaSpark AI asks providers for structured output that can include a title, hook, caption, hashtags, CTA, angle, and platform notes. The goal is practical content that sounds human, useful, and business-ready.

= WordPress Content Repurposing =

Repurpose existing WordPress content into social media assets:

* Blog posts into Facebook captions.
* Pages into Instagram captions.
* WooCommerce products into promotional social posts.
* Existing content into hooks, CTAs, image prompts, and video ideas.

WooCommerce product support appears automatically when WooCommerce is active.

= Brand Intelligence =

Brand Intelligence helps the AI understand your business before writing.

Approved source types include:

* Published or draft WordPress posts.
* WordPress pages.
* WooCommerce products when WooCommerce is active.
* Categories, tags, and public taxonomies.
* Site title and site description.
* Media Library titles, captions, descriptions, and alt-style metadata.
* Pasted brand examples.
* Uploaded TXT, MD, CSV, or JSON brand files.

The generated Brand Intelligence Profile may include:

* Brand voice.
* Audience segments.
* Offers and services.
* Proof points.
* Approved phrases.
* Phrases to avoid.
* CTA style.
* Hashtag banks.
* Visual direction.
* Content pillars.
* Compliance cautions.
* Facebook and Instagram writing rules.

= AI Image Generator =

Generate image prompts and images for social posts using OpenAI image generation. Generated images are saved into the WordPress Media Library so they can be reused, edited, or attached to scheduled posts.

Image generation uses configurable model names, size, and quality settings, so the plugin is not locked to old fixed DALL-E model names.

= Native Image Composer =

The dashboard includes a simple native image composer for creating clean social graphics without a Canva dependency.

Use it for:

* Square social posts.
* Branded quote-style graphics.
* Promotional graphics.
* Educational social images.
* CTA graphics.

Generated graphics can be saved to the WordPress Media Library.

= Model Selection and Troubleshooting =

SociaSpark AI lets administrators choose text and image models separately.

Text generation can use OpenAI, Gemini, or Claude. Image generation uses OpenAI.

The dashboard includes provider test tools so site owners can check credentials and model access before generating content. When a provider rejects a request, SociaSpark AI shows safe diagnostics such as provider, endpoint type, selected model, HTTP status, provider error code when available, and remediation guidance. API keys, bearer tokens, and long raw responses are redacted from logs.

= Facebook and Instagram Scheduler =

SociaSpark AI includes a basic WordPress social media scheduler powered by WP-Cron.

You can:

* Save a post as a draft.
* Publish now.
* Schedule posts for Facebook Pages.
* Schedule image posts for Instagram Business or Creator accounts.
* Track published and failed jobs.
* Retry failed jobs.
* Review logs from the dashboard.

The scheduler processes due jobs, handles transient errors with retries, and marks permanent errors so administrators can fix account or token issues.

= Facebook Page Publishing =

The free core supports publishing to Facebook Pages through the Meta Graph API.

Supported Facebook publishing formats:

* Text posts.
* Image posts with captions.

= Instagram Business and Creator Publishing =

The free core supports Instagram image publishing for Instagram Business and Creator accounts through the Meta Graph API.

Supported Instagram publishing formats:

* Image post.
* Caption.

Instagram publishing requires a public HTTPS image URL. Private local development images cannot be published by Meta.

= Privacy and Security =

SociaSpark AI is built for WordPress.org compliance and site-owner trust.

* No user tracking.
* No telemetry sent to Codefreex.
* No frontend scripts or styles loaded by default.
* No external CDN scripts, styles, or fonts.
* No arbitrary URL scraping in the free core.
* No secret values returned to the React dashboard.
* API keys and access tokens are encrypted before storage.
* AI provider requests happen only after an administrator starts a generation action.
* Brand source selection is explicit.
* Logs redact likely secrets.
* Admin REST routes require capability checks and nonces.

= Who Is This For? =

SociaSpark AI is useful for:

* Small business owners who need regular Facebook and Instagram posts.
* Coaches and consultants who want to repurpose blog content.
* Creators who need hooks, captions, and short-form video scripts.
* Agencies managing social drafts for WordPress clients.
* Course creators promoting lessons, launches, and lead magnets.
* Ecommerce founders creating product social posts.
* Local businesses posting offers, tips, updates, and announcements.
* WordPress site owners who want fewer separate social media tools.

= What SociaSpark AI Does Not Do In The Free Core =

To keep the free plugin useful, privacy-friendly, and WordPress.org-ready, some advanced capabilities are planned for future add-ons rather than bundled into the first release.

The free core does not:

* Render finished videos.
* Publish Instagram Reels video.
* Scrape arbitrary landing page URLs.
* Analyze competitor accounts.
* Add team approval workflows.
* Include LinkedIn, X/Twitter, Pinterest, TikTok, or YouTube Shorts publishing.
* Require a paid license key.
* Lock core Facebook or Instagram posting behind an upgrade screen.

== External Services ==

This plugin connects to third-party services only after an administrator provides credentials and starts an action.

= OpenAI API =

OpenAI is used for text generation and image generation.

Data sent may include prompts, selected Brand Intelligence context, selected WordPress content excerpts, site title, site description, and user-provided generation fields.

Service URL: https://api.openai.com/
Terms and policies: https://openai.com/policies/

= Google Gemini API =

Google Gemini is used for text generation.

Data sent may include prompts, selected Brand Intelligence context, selected WordPress content excerpts, site title, site description, and user-provided generation fields.

Service URL: https://generativelanguage.googleapis.com/
Terms: https://ai.google.dev/terms

= Anthropic Claude API =

Anthropic Claude is used for text generation.

Data sent may include prompts, selected Brand Intelligence context, selected WordPress content excerpts, site title, site description, and user-provided generation fields.

Service URL: https://api.anthropic.com/
Terms: https://www.anthropic.com/legal

= Meta Graph API =

Meta Graph API is used for Facebook Page publishing and Instagram Business/Creator publishing.

Data sent may include post captions, selected image URLs, platform account IDs, Page IDs, Instagram account IDs, and access tokens required by Meta.

Service URL: https://graph.facebook.com/
Terms: https://developers.facebook.com/terms/

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install the plugin zip from WordPress admin.
2. Activate "SociaSpark AI".
3. Open "SociaSpark AI" in wp-admin.
4. Add AI provider keys in Settings.
5. Add Meta account details in Connections.
6. Build a Brand Intelligence Profile from approved sources.
7. Create your first AI social post, save it as a draft, then publish or schedule it.

== Getting Started ==

= 1. Configure AI providers =

Open SociaSpark AI > Settings and add the API keys for the providers you want to use.

You can use:

* OpenAI for text and images.
* Gemini for text.
* Claude for text.

Choose default text and image models, then use "Test provider" to confirm access before generating.

= 2. Build Brand Intelligence =

Open Brand Intelligence, scan available WordPress sources, choose the content that best represents your business, and build a local or AI-assisted profile.

= 3. Create a post =

Open Create Post and enter an idea, audience, tone, CTA, content type, and provider. Generate variations, edit the best option, attach media, and save the draft.

= 4. Connect Meta =

Open Connections and add Facebook or Instagram account details. The free core uses manual Meta credentials so site owners stay in control of their app and tokens.

Use the connection test to confirm account IDs, token permissions, and basic Graph API access before publishing.

= 5. Schedule or publish =

Choose a connected account, select a schedule time, and let WP-Cron process due jobs.

== Frequently Asked Questions ==

= Is SociaSpark AI a social media scheduler for WordPress? =

Yes. SociaSpark AI includes a basic WordPress social media scheduler for Facebook Pages and Instagram Business/Creator image posts.

= Is SociaSpark AI an AI caption generator? =

Yes. It generates Facebook captions, Instagram captions, hooks, CTAs, hashtags, and platform notes using OpenAI, Gemini, or Claude.

= Does SociaSpark AI generate images? =

Yes. The free core supports OpenAI image generation and saves generated images to the WordPress Media Library.

= Does SociaSpark AI create video? =

It creates short-form video scripts, shot lists, hooks, captions, and editing notes as text. It does not render finished video files in the free core.

= Does SociaSpark AI publish to Facebook? =

Yes. It can publish text posts and image posts to connected Facebook Pages.

= Does SociaSpark AI publish to Instagram? =

Yes. It can publish image posts with captions to Instagram Business and Creator accounts connected through Meta.

= Can SociaSpark AI publish Instagram Reels? =

No. The free core generates Reels-style scripts but does not publish Instagram Reels video.

= Can I use my existing WordPress posts? =

Yes. You can repurpose posts, pages, and WooCommerce products into social captions, hooks, CTAs, image prompts, and video script ideas.

= Does it work with WooCommerce? =

Yes. When WooCommerce is active, product content can be used for content repurposing and Brand Intelligence sources.

= What is Brand Intelligence? =

Brand Intelligence is a local profile built from approved WordPress and admin-provided sources. It helps guide generated content with your voice, audience, offers, phrases, visual direction, and platform rules.

= Is Brand Intelligence the same as fine-tuning? =

No. It does not fine-tune OpenAI, Gemini, Claude, or any other AI model. It stores a local profile and uses it as prompt context when an administrator chooses to generate content.

= Does the plugin automatically send my site content to AI providers? =

No. Site content is only sent when an administrator explicitly uses an AI generation or AI brand analysis action.

= Can I build Brand Intelligence without sending data to AI? =

Yes. The plugin includes local profile building from selected sources. AI-assisted analysis is optional.

= Does the plugin scrape landing pages or external URLs? =

No. The free core does not scrape arbitrary URLs.

= Are API keys stored securely? =

API keys and Meta access tokens are encrypted before storage. They are decrypted only server-side when needed for provider requests or publishing.

= Can React or browser users see my API keys or Meta tokens? =

No. Secrets are never returned in REST responses to the dashboard.

= Does SociaSpark AI track my usage? =

No. The plugin does not send telemetry, tracking data, or analytics to Codefreex.

= Does SociaSpark AI load assets on the frontend? =

No. The dashboard assets load only on SociaSpark AI admin pages.

= Which AI providers are supported? =

The free core supports OpenAI, Google Gemini, and Anthropic Claude for text generation. OpenAI is supported for image generation.

= Can I change model names? =

Yes. Model names are configurable in settings so administrators can update text and image models as provider offerings change.

= Can I test AI keys before generating? =

Yes. SociaSpark AI includes provider test tools for configured providers and selected models. OpenAI image model checks use a model-access test instead of generating a paid image.

= Why did an AI provider return an error? =

Common reasons include an invalid API key, a model name your account cannot access, missing billing or quota, provider rate limits, or a temporary provider outage. SociaSpark AI shows the provider, mode, model, HTTP status, provider error code when available, and safe remediation text without exposing secrets.

= Why does Instagram require an HTTPS image URL? =

Meta requires Instagram publishing media to be available at a public HTTPS URL. Localhost, private intranet URLs, and inaccessible media URLs cannot be published by Instagram.

= What happens if a token expires? =

The scheduler marks the job or connection as failed or expired, logs a safe error, and lets administrators reconnect the account or retry the job.

= Does the scheduler run exactly at the scheduled minute? =

The scheduler uses WP-Cron, which depends on site traffic or a real server cron trigger. For high-volume publishing, configure a real cron job to call WordPress cron reliably.

= Is this a replacement for every social media SaaS tool? =

SociaSpark AI is designed as a focused WordPress-first creation and scheduling tool for Facebook and Instagram. Advanced team workflows, extra platforms, and video rendering are planned for future add-ons.

= Is there a paid version? =

The free plugin does not include paid licensing or trialware. It includes hooks and architecture for future add-ons, but the free Facebook and Instagram workflow is intended to be useful on its own.

= Will this guarantee more followers, traffic, or sales? =

No plugin can guarantee those outcomes. SociaSpark AI helps create and schedule better-organized, brand-aware content, but results depend on offer quality, audience, consistency, creative strategy, and platform behavior.

== Screenshots ==

1. Dashboard with drafts, scheduled posts, published posts, failed jobs, connected accounts, recent activity, and Brand Intelligence status.
2. AI post composer with idea input, audience, tone, provider/model selection, generated variations, image composer, Media Library picker, and scheduling controls.
3. Brand Intelligence source manager for WordPress content, products, taxonomies, media metadata, manual examples, and uploaded text sources.
4. AI Studio for captions, image generation, and short-form video script generation.
5. Connections screen for Facebook Page and Instagram Business/Creator account setup and connection testing.
6. Content calendar and activity log for scheduled posts, published posts, retries, and errors.

== Changelog ==

= 1.0.0 =
* Initial release of the WordPress.org free core.
* Added AI caption generation for OpenAI, Gemini, and Claude.
* Added separate text and image model settings plus provider test tools.
* Added OpenAI image generation with Media Library saving.
* Added AI short-form video script generation as text output.
* Added Brand Intelligence from selected WordPress and admin-provided sources.
* Added Facebook Page text and image publishing.
* Added Instagram Business/Creator image publishing.
* Added drafts, Idea Bank, content calendar, scheduler, logs, and dashboard analytics.

== Upgrade Notice ==

= 1.0.0 =
Initial release. Configure AI provider keys and Meta connections before publishing.

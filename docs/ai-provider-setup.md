# AI Provider Setup

SociaSpark AI supports bring-your-own-key providers.

The free core keeps text generation and image generation separate. You can pick a default text provider/model for captions, repurposing, Brand Intelligence, and video-script text, then pick a default image provider/model for OpenAI image generation.

## OpenAI

OpenAI is used for text generation and image generation in the free core.

Settings:

- OpenAI API key
- Default text model, with presets for `gpt-5.5`, `gpt-5.4-mini`, and `gpt-5.4-nano`
- Default image model, with presets for `gpt-image-2` and `gpt-image-1.5`
- Image size and quality defaults

Use the provider test before generating. OpenAI image tests check model access without creating a paid image.

## Google Gemini

Gemini is used for text generation through the Gemini `generateContent` API.

Settings:

- Gemini API key
- Gemini model, defaulting to `gemini-2.5-flash`

## Anthropic Claude

Claude is used for text generation through the Anthropic Messages API.

Settings:

- Claude API key
- Claude model, defaulting to `claude-sonnet-4-6`

## Troubleshooting Provider Errors

If generation fails, check:

- API key validity and billing status.
- Whether the selected model exists for your account.
- Provider quota or rate limits.
- Whether your server can make outbound HTTPS requests.
- The Activity screen for safe diagnostics, including provider, endpoint type, model, HTTP status, and provider error code when available.

## Data Sent To Providers

Data is sent only after an admin clicks a generation or AI brand analysis action. Depending on the action, prompts may include:

- Manual idea fields
- Selected WordPress post/page/product excerpts
- Selected Brand Intelligence profile context
- Site title and description
- Admin-provided brand examples
- Uploaded TXT, MD, CSV, or JSON brand source excerpts selected by an administrator

Secrets are never sent to React responses and are redacted from logs.

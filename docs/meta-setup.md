# Meta Setup

SociaSpark AI uses manually provided Meta credentials in the free core.

## Facebook Page Posting

1. Create or use a Meta developer app.
2. Generate a Page access token with the required Page publishing permissions for your app and Page.
3. Copy the Facebook Page ID.
4. In WordPress admin, open SociaSpark AI > Connections.
5. Add a `facebook` connection with the Page ID and access token.
6. Run the connection test before publishing.

Facebook image publishing uses the Page Photos endpoint when a media URL is present and the Page Feed endpoint for text-only posts.

## Instagram Image Publishing

1. Use an Instagram Business or Creator account connected to a Facebook Page.
2. Generate an access token with Instagram content publishing permissions.
3. Copy the Instagram Business/Creator account ID.
4. Add an `instagram` connection in SociaSpark AI > Connections.
5. Run the connection test before publishing.

Instagram publishing requires a publicly reachable HTTPS image URL. Private local development URLs cannot be published to Instagram.

## Publishing Flow

- Text-only Facebook posts use the Page feed endpoint.
- Facebook image posts use the Page photos endpoint.
- Instagram image posts create a media container, then publish that container.
- SociaSpark AI preserves drafts and logs safe remediation messages if Meta rejects a request.

## Token Expiry

If Meta returns token errors, SociaSpark marks the connection expired or failed and shows the issue in Activity. Reconnect the account with a fresh token.

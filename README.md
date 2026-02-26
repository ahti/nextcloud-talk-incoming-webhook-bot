# Nextcloud Talk Webhooks

A Nextcloud Talk Bot (packaged as Nextcloud app) that allows external tools to send messages to Talk channels via incoming webhooks, without needing to implement the whole bot API themselves.

Disclaimer: This was mostly vibe-coded. I have reviewed and cleaned up anything that looked wrong to me, but I have little experience with developing Nextcloud apps, so use at your own peril :) Reviews and PRs by more experienced eyes welcome.

## Features

- **Incoming Webhooks**: Create webhook URLs for specific Talk channels
- **Permissions**: Only moderators and owners can manage webhooks
- **Bring your own secret**: Let the bot auto-generate a secret, or provide your own hashed secret to hide it from other channel members

## Usage

1. Enable the app. The bot auto-installs.
2. In Talk, enable the bot for a channel, then use `/webhook create <name> [hashed_secret]` to create a webhook
3. Use the webhook URL with the secret to post messages:

    ```bash
    curl <webhook_url> \
    -H 'X-Webhook-Secret: <secret>' \
    --json '{"message":"Hello!"}'
    ```

## Commands

- `/webhook create <name> [hashed_secret]` - Create a new webhook
- `/webhook list` - List webhooks for the channel
- `/webhook delete <hook_id>` - Delete a webhook

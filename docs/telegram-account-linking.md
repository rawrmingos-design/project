# Telegram Account Linking

Telegram order history is available only after an authenticated website session links one Telegram account to the application User. A Telegram numeric ID, username, display name, or synthetic Telegram email is not account ownership proof by itself.

## Website flow

1. Sign in and open **Pengaturan**.
2. In **Telegram Gateway**, select **Buat Link Telegram**.
3. Open the one-time Telegram launch link and press **Start** in the configured bot.
4. The bot consumes the opaque challenge from `/start <token>`. The challenge is single-use and expires according to the configured lifetime.
5. Return to settings and select **Cek Status** when needed.
6. To cancel an unused challenge, select **Batalkan Link**. To remove an existing Telegram link, enter the current password and select **Lepas Telegram**.

The launch URL is returned only to the authenticated settings request that creates the challenge. Challenge storage contains only a SHA-256 token hash. Plaintext tokens, full launch URLs, and raw Telegram IDs must not be logged.

## Website endpoints

All endpoints use the authenticated web session, CSRF protection, and security throttling:

- `GET /id/settings/telegram/status` — returns linked state, safe Telegram display metadata, verification timestamp, and pending challenge expiry.
- `POST /id/settings/telegram/link` — creates a bot-scoped one-time challenge and launch URL.
- `POST /id/settings/telegram/revoke` — revokes pending challenges for the signed-in account.
- `POST /id/settings/telegram/unlink` with `current_password` — revokes pending challenges and the active Telegram identity.

Only one active Telegram identity is allowed for a User and configured bot scope. An identity already linked to another User, or a User already linked to another Telegram account, is rejected without disclosing ownership details.

## Telegram commands

- `/start <token>` consumes a website challenge.
- `LINK <token>` is the manual linking fallback for Telegram.
- `TELEGRAM_STATUS` or `STATUS_AKUN` reports only linked or unlinked state and next steps.
- `RIWAYAT`, `HISTORY`, and `PESANAN` show personal order history after linking.
- Telegram menu shows `🏆 Leaderboard`, `📜 Riwayat Order`, and Deposit only when `TELEGRAM_DEPOSIT_ENABLED=true`.
- `DEPOSIT <jumlah> <metode>` creates a Deposit only for an active bot-scoped linked application User. The capability remains disabled by default for staged rollout.

Order history and Deposit resolve the sender using the bot scope and immutable `callback_query.from.id` or `message.from.id`. Every list, detail, and Deposit request resolves the identity again, rejects an identity whose tenant no longer matches its linked User, and passes the linked application User explicitly to its domain service. Telegram usernames, display names, synthetic email addresses, callback payloads, and raw Telegram IDs remain informational transport data and never prove application ownership.

History output is tenant-scoped and redacted. It does not show email addresses, phone numbers, balances, game credentials, payment codes, VA values, checkout URLs, provider secrets, or raw gateway metadata. Unlinked, revoked, conflicting, expired, malformed, and cross-user requests receive generic responses.

## Security and recovery

- Telegram webhook requests require the configured `X-Telegram-Bot-Api-Secret-Token`. A missing secret fails closed.
- Challenge tokens are high-entropy, bounded, hashed at rest, expiry-limited, attempt-limited, and consumed inside a row-locked transaction.
- Challenges and identities are scoped by the configured bot scope and tenant-aware models.
- Unlinking immediately blocks future Telegram history access and does not change historical order ownership.
- If a link is lost or the wrong Telegram account was linked, unlink it from settings and create a new challenge. Do not send tokens through public support channels.
- Telegram webhook `update_id` values are persisted per bot scope in `telegram_update_receipts`; duplicate updates return a duplicate response before adapter processing. Retention/cleanup should be scheduled operationally.
- Telegram Deposit requires stable bot-scoped sender and message identities for state isolation and idempotency. Deposit and payment records inherit the resolved User tenant explicitly. Stored payment metadata includes correlation fields and an HMAC chat fingerprint, not the raw chat ID.
- Keep `TELEGRAM_DEPOSIT_ENABLED=false` until staging has verified linked/unlinked identities, QR and VA methods, duplicate updates, provider failures, and cross-account attempts.

# WhatsApp Account Linking

WhatsApp deposits require a verified phone number. A Fonnte sender is not treated as account ownership proof until the account owner completes the linking challenge.

## Website flow

1. Sign in and open **Pengaturan**.
2. In **WhatsApp Gateway**, enter the Indonesian WhatsApp number that should be linked.
3. Select **Buat Kode Linking**.
4. Send `LINK <kode>` from that WhatsApp number. The code is single-use and expires according to the configured challenge lifetime.
5. Use **Cek Status** to confirm the number is verified.
6. To cancel an unused code, select **Batalkan Kode**. To remove an existing link, enter the current password and select **Lepas WhatsApp**.

Changing the profile WhatsApp number clears `whatsapp_verified_at`; the new number must complete linking again.

## Website endpoints

All endpoints use the authenticated web session, CSRF protection, and security throttling:

- `GET /id/settings/whatsapp/status` — returns the canonical number, verification timestamp, and active challenge expiry.
- `POST /id/settings/whatsapp/link` with `no_wa` — creates a challenge and attempts to send the instruction through the configured WhatsApp provider.
- `POST /id/settings/whatsapp/revoke` — revokes active challenges for the signed-in account.
- `POST /id/settings/whatsapp/unlink` with `current_password` — revokes active challenges and clears the verified timestamp.

The link endpoint returns the plaintext challenge code only in the authenticated response that creates it. Challenge storage contains only `code_hash`; hashes, provider credentials, and device tokens must not be exposed or logged.

A number already verified for another account is rejected with a generic error. The response does not reveal the owning account.

## Operational notes

- Numbers are stored canonically as Indonesian international digits, for example `6281234567890`.
- Legacy or duplicate `users.no_wa` values must be audited before adding a database uniqueness constraint.
- An unverified or ambiguous sender must not be authorized for WhatsApp deposits.
- From WhatsApp, send `LINK <kode>` to consume the pending website challenge. Codes are six digits and must be sent from the same canonical number.
- Send `STATUS_AKUN` or `📲 Status WhatsApp` to see whether the sender is linked, registered but unverified, unregistered, or unavailable. The response does not reveal account details.
- A verified WhatsApp sender can use `RIWAYAT`, `HISTORY`, `PESANAN`, or `📜 Riwayat Order` to view up to five latest orders belonging to the linked account. Each detail request repeats owner and tenant authorization; invoice IDs are masked in the list and historical payment codes are not replayed.
- Order history is WhatsApp-only in this phase. Telegram does not infer account ownership from a Telegram ID or username.
- Order history uses the legacy `pembelians.username` owner field. `pembelians.user_id` remains the target game account ID and is not used for authorization. Detail buttons carry a bounded numeric order reference; the server resolves it again under the verified owner scope.
- History pagination and detail reads are rate-limited per sender and return generic responses for unregistered, unverified, ambiguous, tenant-mismatch, or unavailable identities.

## WhatsApp order history

Use one of these commands after linking the WhatsApp number:

- `RIWAYAT`
- `HISTORY`
- `PESANAN`
- `📜 Riwayat Order`

The bot shows the five latest orders with masked invoice IDs, product, date, amount, and normalized status. Select a detail entry or use the next/previous page. Detail lookup is constrained to the verified account and tenant; an invoice ID from another account is treated as unavailable. Payment codes, VA values, checkout URLs, game credentials, balances, and raw gateway metadata are not shown in history. History detail does not replay payment instructions or invoke the generic status flow.

The current phase supports WhatsApp only. Telegram account linking is a separate feature and must not be inferred from Telegram usernames or IDs.

- A verified sender can create a deposit with `DEPOSIT <jumlah> <metode>`, for example `DEPOSIT 15000 BCA`. The webhook message ID is required for retry-safe idempotency.
- Deposit requests resolve the sender to the verified `User` before validating payment or calling a gateway. Unregistered, unverified, ambiguous, and tenant-mismatch senders are denied.
- QR deposits send the payment text followed by QR media. VA and payment-code methods remain text. Website invoice URLs are not sent for QR deposits.
- If the provider returns a raw QR payload, the bot uses a QR image URL generated from that payload. Provider credentials and internal exceptions are never returned to the sender.
- Deposit state is bounded to the request; no plaintext challenge or payment secret is stored in bot state.
- Fonnte webhook requests require both a configured device token and a matching request token. Missing credentials fail closed.
- Bot webhooks use an IP limit, while `LINK` and `DEPOSIT` attempts use separate per-sender limits. Sender keys are HMAC fingerprints and raw numbers are not logged.
- Sensitive bot failures include a correlation ID in server logs without logging challenge codes, provider credentials, or raw sender numbers.
- Fonnte webhook linking is implemented in Phase 4; deposit authorization still requires the later deposit-service and conversation phases.
- `LINK username`, `LINK email`, and `LINK user_id` are not supported. Linking uses only the single-use challenge.
- Deposit creation is centralized in `DepositService`, which receives the resolved `User` explicitly; the web controller remains an `Auth::user()` adapter.
- WhatsApp deposit requests use `source=whatsapp_gateway`, an external sender identity, and the inbound message ID for idempotency. Repeated delivery of the same message replays the existing deposit instead of creating another one.
- Payment metadata preserves QR media fields (`qr_link`, `qr_payload`) separately from text payment codes and checkout URL fallbacks. The WhatsApp conversation must use these fields when Phase 6 is implemented.
- Fonnte webhook linking is implemented in Phase 4; deposit authorization still requires the later deposit-service and conversation phases.
- Production rollout requires a duplicate-number audit, verified Fonnte token and inbound whitelist, shared Redis cache, backup, migration review, and rollback plan. See the README rollout checklist before enabling WhatsApp deposits.
- `docs/` is ignored by the current repository policy; retain this document locally or force-add it during the documentation phase.

# Phase 6 — WhatsApp Auto-Registration on Deposit

## Objective

Remove the hard-reject for unregistered and unverified WhatsApp senders when
they attempt `deposit`. Instead:

- **Unverified account** (`STATUS_REGISTERED_UNVERIFIED`): auto-verify in-place
  and ask the user to retry deposit.
- **Unregistered number** (`STATUS_UNREGISTERED`): enter a two-step inline
  registration flow (YA/TIDAK → email/SKIP) before creating an account.

Scope: **main tenant / WhatsApp gateway only**. Telegram deposit path unchanged.

## Delivered files

| File | Change |
|---|---|
| `app/Services/Bot/BotCommandHandler.php` | `handleWhatsappDeposit()` branches on `STATUS_REGISTERED_UNVERIFIED` (auto-verify) and `STATUS_UNREGISTERED` (set state); `handleUnknownInput()` intercepts `waiting_wa_register_confirm` and `waiting_wa_register_email` before `waiting_game_id`; new private helpers `handleWaRegisterConfirm()`, `handleWaRegisterEmail()`, `createWhatsappAccount()` |
| `app/Services/Bot/BotMessageFormatter.php` | New methods: `formatWaRegisterPrompt()`, `formatWaRegisterEmailPrompt()`, `formatWaRegisterEmailRetry()`, `formatWaRegisterSuccess()`, `formatWaAutoVerified()` |
| `app/Services/Bot/BotCommandParser.php` | Added `YA`, `TIDAK`, `SKIP` as reply-keyboard-style aliases |
| `tests/Feature/Bot/WhatsappDepositBotTest.php` | Updated 2 existing tests to reflect new flows; added 9 new registration tests |

## Conversation flow

```
User: deposit
  ↓
[STATUS_LINKED] → existing deposit flow (unchanged)

[STATUS_REGISTERED_UNVERIFIED]
  → set users.whatsapp_verified_at = now()
  → "✅ Nomor berhasil diverifikasi! Silakan ulangi perintah deposit."

[STATUS_UNREGISTERED]
  → Cache: waiting_wa_register_confirm (15 min)
  → "Untuk deposit, kamu perlu punya akun. Ketik YA atau TIDAK."

  User: TIDAK  → clear state, "Oke, pendaftaran dibatalkan."
  User: YA     → Cache: waiting_wa_register_email (15 min, attempt_count=0)
               → "Mau daftarkan email? Ketik email atau SKIP."

    User: valid unique email → create account with email
    User: invalid/duplicate  → attempt_count++
      if attempt_count < 3: retry prompt
      if attempt_count >= 3: auto-SKIP → create account without email
    User: SKIP → create account without email (synthetic placeholder email)

  Create account:
    username = wa_<E164number>  (e.g. wa_6281234567890)
    On collision: wa_<number>_<4-char random suffix>
    password = Str::password(12, symbols: false)  — sent once, never logged
    role = Member
    email = provided email, or wa_<number>@wa.bot placeholder
    whatsapp_verified_at = now()

[STATUS_AMBIGUOUS / STATUS_TENANT_MISMATCH / STATUS_UNAVAILABLE]
  → fail closed (unchanged)
```

## State machine

Reuses `checkoutStateKey()` cache (scoped to `source|external_user_id`).

| Step key | Payload | TTL |
|---|---|---|
| `waiting_wa_register_confirm` | `{step, wa_number}` | 15 min |
| `waiting_wa_register_email` | `{step, wa_number, attempt_count}` | 15 min |

`handleUnknownInput()` checks registration states **before** `waiting_game_id`
so that `YA`/`TIDAK`/`SKIP`/email inputs are not misrouted.

## Security invariants

- Password is generated in `createWhatsappAccount()`, sent once in the bot
  reply, and **never stored in cache or logs**. The `Log::notice` call records
  only `username`.
- The synthetic `@wa.bot` placeholder email is not a valid login credential
  and has no `email_verified_at`.
- Registration is rate-limited by the existing `bot-deposit:` fingerprint key
  applied before the identity check — no separate rate limit needed.
- `STATUS_AMBIGUOUS` and `STATUS_TENANT_MISMATCH` still fail closed; only
  `STATUS_UNREGISTERED` and `STATUS_REGISTERED_UNVERIFIED` enter the new path.
- State is cleared on: TIDAK, successful registration, auto-SKIP. An expired
  cache entry falls through to `handleUnknownInput()`'s default "perintah tidak
  dikenali" response — no side-effect.

## Migration order

This phase is self-contained and requires no schema migration.

Deploy after Phase 1 (inbound trust) is live, since identity resolution depends
on the correct Fonnte sender field.

## Rollback notes

Rolling back restores the hard-reject messages for unregistered and unverified
senders. Any in-flight `waiting_wa_register_*` cache state expires harmlessly
within 15 minutes. No data loss; any accounts created before rollback remain
valid.

## Acceptance evidence

### `tests/Feature/Bot/WhatsappDepositBotTest` — 16 passed (50 assertions)

| Test | Assertion |
|---|---|
| `verified_sender_creates_deposit_for_resolved_user` | Linked sender → deposit created; unchanged |
| `deposit_requires_message_id` | Missing message_id → rejected; unchanged |
| `qr_link_is_sent_as_media_without_invoice_url` | QR link deposit → photo_url set; unchanged |
| `raw_qr_payload_is_converted_to_media` | QR payload → generated photo_url; unchanged |
| `duplicate_webhook_message_replays_the_same_deposit` | Duplicate message → single deposit; unchanged |
| `unregistered_user_sees_registration_prompt_on_deposit` | Unregistered → prompt contains YA/TIDAK; no user created |
| `unregistered_user_can_cancel_registration` | TIDAK → "dibatalkan"; no user created |
| `unregistered_user_can_skip_email_and_account_is_created` | SKIP → account created with placeholder email; verified |
| `unregistered_user_can_register_with_email` | Valid email → account created with that email |
| `duplicate_email_triggers_retry_prompt` | Existing email → retry prompt; no new user |
| `invalid_email_format_triggers_retry_prompt` | Bad format → retry prompt; no new user |
| `email_retry_max_three_then_auto_skip` | 3 bad inputs → auto-SKIP; account created without real email |
| `registered_unverified_user_is_auto_verified_on_deposit` | Unverified → `whatsapp_verified_at` set; no deposit yet |
| `username_collision_gets_suffix` | Existing `wa_628xxx` username → suffixed username generated |
| `unregistered_sender_is_denied_before_gateway_work` | Unregistered → prompt (not hard deny); no deposit |
| `registered_unverified_sender_is_denied` | Unverified → auto-verify message (not hard deny); no deposit |

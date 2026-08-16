# Phase 7 — Telegram Auto-Registration on Deposit

## Objective

Replace the hard-reject for unlinked Telegram senders (`STATUS_UNLINKED`, `STATUS_REVOKED`) when they attempt `deposit`. Provide an in-chat registration flow to create a new website account and link it immediately.

Unlike WhatsApp, Telegram doesn't provide a deterministically safe and verified phone number by default, so we cannot auto-generate a username. The user is prompted to choose one.

Scope: **main tenant / Telegram gateway only**.

## Delivered files

| File | Change |
|---|---|
| `app/Services/Bot/BotCommandHandler.php` | `handleTelegramDeposit()` branches on `STATUS_UNLINKED` and `STATUS_REVOKED` to initiate state machine; `handleUnknownInput()` intercepts `waiting_tg_register_confirm`, `waiting_tg_register_username`, `waiting_tg_register_email`; new private helpers `handleTgRegisterConfirm()`, `handleTgRegisterUsername()`, `handleTgRegisterEmail()`, `createTelegramAccount()` |
| `app/Services/Bot/BotMessageFormatter.php` | New methods: `formatTgRegisterPrompt()`, `formatTgRegisterUsernamePrompt()`, `formatTgRegisterUsernameRetry()`, `formatTgRegisterEmailPrompt()`, `formatTgRegisterEmailRetry()`, `formatTgRegisterSuccess()` |
| `tests/Feature/Bot/TelegramDepositBotTest.php` | Updated existing `test_unlinked_telegram_identity_is_denied_before_gateway_work` test; added 5 new registration tests |

## Conversation flow

```
User: deposit
  ↓
[STATUS_LINKED] → existing deposit flow (unchanged)

[STATUS_UNLINKED] / [STATUS_REVOKED]
  → Cache: waiting_tg_register_confirm (15 min)
  → "Akun Telegram belum tertaut... Ketik YA atau TIDAK."

  User: TIDAK  → clear state, "Oke, pendaftaran dibatalkan."
  User: YA     → Cache: waiting_tg_register_username (15 min, attempt_count=0)
               → "Ketik username yang ingin kamu gunakan..."

    User: (types username)
      [invalid / taken] → attempt_count++
        if attempt_count < 3: retry prompt
        if attempt_count >= 3: clear state, cancel registration
      
      [valid and unique]
        → Cache: waiting_tg_register_email (15 min, tg_username=...)
        → "Username diterima. Mau daftarkan email? Ketik email atau SKIP."

        User: valid unique email → create account with email
        User: invalid/duplicate  → attempt_count++
          if attempt_count < 3: retry prompt
          if attempt_count >= 3: auto-SKIP → create account without email
        User: SKIP → create account without email (synthetic placeholder email)

  Create account:
    username = provided username
    password = Str::password(12, symbols: false)  — sent once, never logged
    role = Member
    email = provided email, or <username>@tg.bot placeholder
    Create TelegramIdentity linked to the new User

[STATUS_AMBIGUOUS / STATUS_TENANT_MISMATCH / STATUS_UNAVAILABLE]
  → fail closed (unchanged)
```

## State machine

Reuses `checkoutStateKey()` cache (scoped to `source|telegram_user_id`).

| Step key | Payload | TTL |
|---|---|---|
| `waiting_tg_register_confirm` | `{step, telegram_user_id, telegram_bot_scope, telegram_chat_id}` | 15 min |
| `waiting_tg_register_username` | `{step, telegram_user_id, ..., attempt_count}` | 15 min |
| `waiting_tg_register_email` | `{step, telegram_user_id, ..., tg_username, attempt_count}` | 15 min |

## Security invariants

- Password is generated in `createTelegramAccount()`, sent once in the bot reply, and **never stored in cache or logs**.
- The synthetic `@tg.bot` placeholder email is not a valid login credential and has no `email_verified_at`.
- Registration is rate-limited by the existing `bot-telegram-deposit:` fingerprint key.
- `STATUS_AMBIGUOUS` and `STATUS_TENANT_MISMATCH` still fail closed.
- State is cleared on: TIDAK, too many username attempts, successful registration, auto-SKIP.

## Migration order

This phase is self-contained and requires no schema migration.

## Rollback notes

Rolling back restores the hard-reject messages for unlinked senders. Any in-flight cache state expires harmlessly within 15 minutes. No data loss; any accounts created before rollback remain valid.

## Acceptance evidence

### `tests/Feature/Bot/TelegramDepositBotTest` — 15 passed (62 assertions)

| Test | Assertion |
|---|---|
| `unlinked_telegram_user_can_cancel_registration` | TIDAK → "dibatalkan"; no user created |
| `unlinked_telegram_user_can_skip_email_and_account_is_created` | SKIP → account created with placeholder email; `TelegramIdentity` linked |
| `unlinked_telegram_user_can_register_with_email` | Valid email → account created with that email |
| `telegram_username_validation_and_uniqueness` | Invalid format or taken username → retry prompt |
| `telegram_username_retry_max_three_then_cancel` | 3 bad username inputs → registration cancelled, state cleared |

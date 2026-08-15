# Phase 3 — Explicit Confirmation Before Financial Mutation

## Objective

Every bot product invoice path — including direct `invoice` and `beli`
commands — must create a confirmable intent and present a summary before any
provider or financial mutation occurs. Possession of a valid `waiting_game_id`
state or a correctly formed `invoice` command is not sufficient authority to
call the provider.

## Delivered files

| File | Change |
|---|---|
| `app/Services/Bot/BotCommandHandler.php` | `handleInvoice()` → intent creation + `waiting_confirmation` + confirmation prompt; `confirmCheckout()` → atomic claim → provider mutation |
| `app/Services/Bot/BotCommandParser.php` | `konfirmasi` / `confirm` / `KONFIRMASI` / `BATAL` aliases parsed; intent action token extracted from args |
| `app/Services/Bot/BotMessageFormatter.php` | Confirmation prompt template: masked product summary, price, payment method, expiry, action token |
| `tests/Feature/Bot/BotWebhookTest.php` | All 13 invoice/media tests migrated to two-step contract (see Acceptance evidence) |
| `tests/Feature/Bot/BotCheckoutIntentServiceTest.php` | Intent lifecycle tests already cover the claim/replay/reconciliation contract |

## Two-step flow

```
Step 1: invoice <serviceId> <method> <uid> [zone]
  → GatewayPricingService::quote()   (once, pricing locked)
  → BotCheckoutIntentService::create()   (durable record, status=awaiting_confirmation)
  → Cache: waiting_confirmation + intent_token
  → Reply: confirmation prompt with masked summary + KONFIRMASI <token> instruction

Step 2: KONFIRMASI <token>   (WhatsApp) / konfirmasi <token>   (Telegram)
  → Re-resolve source/sender/User
  → Verify token scope and intent status
  → BotCheckoutIntentService::claim()   (atomic, row-locked)
  → GatewayInvoiceService::createInvoice()   (provider mutation)
  → Reply: invoice result / media
```

## Token binding

- The intent action token is a short random string stored in the durable
  intent record and echoed into the `waiting_confirmation` cache key.
- WhatsApp: user must send `KONFIRMASI <token>` as a plain message.
- Telegram: user must send `konfirmasi <token>` as a text command.
- Token scope includes source, sender fingerprint, and intent ID. A token from
  one sender cannot be used by another sender.
- Expired or cancelled intents are permanently non-consumable.

## Re-validation before mutation

Immediately before atomic claim, `GatewayPricingService::quote()` is called a
second time inside `CheckoutOrderService` to verify the price has not changed.
If the quote changed, the intent is updated and a fresh confirmation is
required. (Note: in tests where `GatewayInvoiceService` is mocked,
`CheckoutOrderService` is never entered so the second `quote()` call does not
fire — the `->once()` mock count is correct.)

## Migration order

1. Phase 2 (durable intents) must be live.
2. Deploy Phase 3 together with Phase 2 in the same release, or immediately
   after.
3. Existing `waiting_game_id` cache state from before deployment is harmless —
   it cannot reach the new confirmation step.

## Rollout dependencies

- Requires Phase 2 schema and service.
- Web checkout path is unchanged — Phase 3 applies to bot-only flows.

## Rollback notes

Rolling back Phase 3 restores single-step checkout. Any `awaiting_confirmation`
intents in the DB will remain there harmlessly; they will expire per their
`expires_at` column. No data loss.

## Acceptance evidence

All 50 tests in `tests/Feature/Bot/BotWebhookTest` pass (244 assertions):

| Test group | Count | Assertion |
|---|---|---|
| Telegram invoice + QRIS/photo two-step | 4 | Step 1 returns confirmation prompt; step 2 calls `createInvoice` exactly once |
| Fonnte invoice + QRIS media two-step | 5 | Step 1 sends confirmation (1 send); step 2 sends invoice text + media (2 more sends, 3 total) |
| Fonnte invoice + VA text two-step | 3 | Step 1 sends confirmation; step 2 sends invoice text (2 total) |
| `telegram_invoice_requires_confirmation_and_freezes_synthetic_email_contact` | 1 | Intent created with `STATUS_AWAITING_CONFIRMATION`; `payload['email'] === '9876@telegram.user'`; no provider call |
| `duplicate_invoice_origin_replays_awaiting_confirmation_intent` | 1 | Second identical POST replays without creating a second intent |

`GatewayPricingService::quote()` mock is `->once()` in all new two-step tests
(the second call inside `CheckoutOrderService` is bypassed because
`GatewayInvoiceService` is mocked).

## Security invariants

- UID/zone submission and direct `invoice` commands never call the provider
  directly — they only create an `awaiting_confirmation` intent.
- The first financial/provider mutation occurs only after a valid,
  current, sender-scoped `claim()` succeeds.
- Synthetic Telegram email (`<fromId>@telegram.user`) is frozen into the
  intent payload at step 1 and never re-derived from display name.
- Confirmation token alone does not authorize a different sender.

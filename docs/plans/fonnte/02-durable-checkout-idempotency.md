# Phase 2 — Durable DB-Backed Checkout Idempotency

## Objective

Replace cache-only checkout idempotency with a durable database record.
Cache loss, concurrent delivery, or a process crash must never cause a second
provider mutation for the same originating message. A completed intent must
replay its result without calling the provider again.

## Delivered files

| File | Change |
|---|---|
| `database/migrations/*_create_bot_checkout_intents_table.php` | New table: `bot_checkout_intents` |
| `app/Models/BotCheckoutIntent.php` | Eloquent model with status constants, encrypted payload cast, and scopes |
| `app/Services/Checkout/BotCheckoutIntentService.php` | create-or-replay, atomic claim under row lock, sender/source/User/tenant scope |
| `app/Services/Gateway/GatewayInvoiceService.php` | Passes `intent_id` explicitly; rejects bot mutations without a valid scoped intent |
| `app/Services/Checkout/CheckoutOrderService.php` | Marks `processing` before dispatch; marks `requires_reconciliation` on indeterminate outcome; completed-replay returns cached result |
| `tests/Feature/Bot/BotCheckoutIntentServiceTest.php` | Focused idempotency tests (see Acceptance evidence) |

## Schema

```
bot_checkout_intents
  id                         bigint unsigned PK
  intent_id                  uuid unique
  tenant_id                  bigint unsigned nullable FK
  user_id                    bigint unsigned nullable FK
  source                     varchar(64)
  sender_fingerprint         varchar(64)   -- HMAC of normalized sender
  origin_message_fingerprint varchar(64)   -- HMAC of message-ID
  payload_fingerprint        varchar(64)   -- SHA-256 of canonical payload
  payload                    text encrypted
  status                     varchar(32)
  merchant_ref               varchar(128) nullable
  result_order_id            varchar(128) nullable
  failure_code               varchar(64) nullable
  expires_at                 timestamp nullable
  confirmed_at               timestamp nullable
  dispatched_at              timestamp nullable
  completed_at               timestamp nullable
  created_at / updated_at    timestamps

  UNIQUE (tenant_id, source, sender_fingerprint, origin_message_fingerprint)
```

## Status lifecycle

```
awaiting_confirmation
  → processing          (atomic claim under SELECT … FOR UPDATE)
  → completed           (provider success)
  → cancelled           (user or system cancel before claim)
  → expired             (TTL elapsed before claim)
  → failed_retryable    (pre-dispatch failure only)
  → requires_reconciliation  (post-dispatch outcome unknown)
```

## Migration order

1. Run migration (safe on empty table — no existing data).
2. Deploy application code.
3. Phase 3 (confirmation state machine) requires this phase to be deployed
   first.

## Rollout dependencies

- Phase 1 must be deployed before Phase 2 (inbound trust correction is a
  prerequisite for any further bot-flow work).
- Phase 3 must not be enabled until Phase 2 migration is live.

## Rollback notes

The migration can be rolled back with `php artisan migrate:rollback`. The
application code falls back gracefully — no bot checkout will succeed without
the table present, which is the desired fail-closed behaviour.

## Acceptance evidence

All tests in `tests/Feature/Bot/BotCheckoutIntentServiceTest` pass:

| Test | Assertion |
|---|---|
| `same_origin_message_replays_one_intent` | Duplicate origin returns same intent without creating a second |
| `same_origin_message_rejects_a_different_payload` | Different payload on same origin throws |
| `separate_messages_allow_identical_purchases` | Two distinct origin messages may carry identical payload |
| `claim_is_sender_scoped_and_duplicate_claim_stays_processing` | Cross-sender claim rejected; same-sender duplicate claim returns `processing` |
| `provider_dispatch_is_single_use_and_unknown_failure_requires_reconciliation` | Post-dispatch crash marks `requires_reconciliation`, not retried |
| `pre_dispatch_failure_can_be_claimed_again` | Pre-dispatch failure marks `failed_retryable`; next claim proceeds |
| `completed_intent_replays_as_completed` | Completed intent returns cached result, no second provider call |

## Security invariants

- Raw sender numbers, UID/zone values, and provider payloads are never stored
  in plaintext columns — only HMAC fingerprints and encrypted payload.
- Cache may remain as a fast lookup layer; the DB record is the financial
  authority.
- An indeterminate provider result is never blindly retried — it requires
  manual reconciliation.

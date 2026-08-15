# Phase 1 — Correct the Fonnte Inbound Trust Boundary

## Objective

Remove all inbound credential parsing from the Fonnte webhook controller.
Fonnte does not send an `Authorization` header on inbound requests; the
configured token is an outbound-only credential used when calling Fonnte's
API. Inbound Fonnte requests must be accepted or rejected exclusively by the
enforce-mode source-IP policy already in place.

## Delivered files

| File | Change |
|---|---|
| `app/Http/Controllers/Api/BotWebhookController.php` | Removed `Authorization` / `device_token` parsing from `fonnte()`. Correlation-ID creation and adapter dispatch are preserved. |
| `app/Services/WhatsappNotificationService.php` | Outbound `Http::withHeaders(['Authorization' => $api->wa_key])` retained. Hardened to fail-closed when `wa_key` is absent rather than sending an unauthenticated request. |
| `tests/Feature/Bot/BotWebhookSecurityTest.php` | Replaced obsolete inbound-token tests with direction-specific tests (see Acceptance evidence). |

## Routes unchanged

```
inbound.whitelist:bot_webhook,fonnte,enforce
```

The middleware stack is unmodified. No controller fallback bypasses
enforce-mode IP validation.

## Migration order

Phase 1 is independent — it removes code rather than adding schema. Deploy
before any other phase. No database migration required.

## Rollout dependencies

None. Phase 1 can be deployed and rolled back without affecting Phase 2–5.

## Rollback notes

Reverting `BotWebhookController::fonnte()` to a version that parses inbound
`Authorization` re-introduces the obsolete behaviour but does not break
anything functionally — Fonnte will never send that header. Rollback is safe
at any time.

## Acceptance evidence

All tests in `tests/Feature/Bot/BotWebhookSecurityTest` pass:

| Test | Assertion |
|---|---|
| `fonnte_accepts_allowed_ip_without_authorization` | 200 with no `Authorization` header from allowed IP |
| `fonnte_rejects_unauthorized_ip` | 403 before adapter work |
| `fonnte_ignores_inbound_authorization_header` | 200 regardless of arbitrary `Authorization` value |
| `fonnte_ignores_inbound_device_token_payload` | 200 regardless of arbitrary `device_token` body field |
| `fonnte_inbound_acceptance_does_not_depend_on_configured_outbound_token` | Inbound acceptance unaffected by missing outbound token config |

Full suite: **50 BotWebhookTest + 9 BotWebhookSecurityTest** — all passed,
244 assertions.

## Security invariants

- Inbound Fonnte `Authorization` is ignored, not treated as optional
  defense-in-depth.
- Outbound Fonnte requests always carry `Authorization: <configured token>`.
- Failing to configure the outbound token causes a fail-closed delivery error,
  not a silent unauthenticated send.

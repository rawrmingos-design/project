# Phase 4 — Stable WhatsApp Numeric Menus and Stale-State Handling

## Objective

Replace flattened positional numeric selection (`$items[$selection - 1]`) with
an explicit semantic entry map. Navigation numbers must never shift when
content count changes. Stale, expired, or corrupt state must fail safely
without performing any financial mutation.

## Delivered files

| File | Change |
|---|---|
| `app/Services/Bot/Adapters/FonnteAdapter.php` | Numeric input resolved via semantic entry map keyed by rendered number; corrupt/missing state cleared with recovery message |
| `app/Services/Bot/BotMessageFormatter.php` | WhatsApp menus carry `numeric_type` per entry (`content`, `navigation_previous`, `navigation_next`, `back`); 15-item content limit for WhatsApp, existing size for Telegram |
| `app/Services/Bot/BotGatewayCapabilities.php` | `WHATSAPP_MAX_MENU_ITEMS = 15` constant; source-aware limit used by formatter |
| `tests/Feature/Bot/BotWebhookTest.php` | Tests for numeric map, pagination, corrupt state, stale state, `waiting_confirmation` precedence |

## Semantic entry map

| Input | `numeric_type` | Meaning |
|---|---|---|
| 1–15 | `content` | Selectable content item |
| 98 | `navigation_previous` | Previous window |
| 99 | `navigation_next` | Next window |
| 0 | `back` | Return to parent menu |

Navigation keys are only present in the rendered entry map when the
corresponding cursor/action is available. An input of 98 when no previous page
exists is treated as an unrecognised entry (no side-effect).

## State schema (versioned)

```json
{
  "schema_version": 1,
  "revision": "<16-char random>",
  "menu": "<menu-key>",
  "source": "whatsapp_gateway",
  "entries": {
    "1": {"numeric_type": "content", "callback": "…"},
    "99": {"numeric_type": "navigation_next", "callback": "…"},
    "0":  {"numeric_type": "back", "callback": "…"}
  },
  "parent_menu": "<menu-key or null>",
  "created_at": "<ISO-8601>",
  "expires_at": "<ISO-8601>"
}
```

TTL: 15 minutes. Every menu transition writes a new revision and invalidates
the prior snapshot.

## Safe failure rules

| Condition | Behaviour |
|---|---|
| Missing / expired state | Reply: "Menu sudah kedaluwarsa. Ketik MENU untuk menampilkan pilihan terbaru." No side-effect. |
| Unknown schema version or corrupt JSON | Same recovery message. State key cleared. |
| Input number not in entry map | Reply: out-of-range message. State preserved. |
| `waiting_confirmation` cache present | Numeric input routed to confirmation flow, not menu. Numeric menu state NOT consumed. |
| `waiting_game_id` cache present | Numeric input treated as UID/zone reply, not menu. |

## Migration order

Phase 4 can be deployed independently of Phase 2/3 for the menu-formatting
side. The `waiting_confirmation` precedence rule requires Phase 3 to be live
to be meaningful, but deploying Phase 4 before Phase 3 is safe (precedence
check is a no-op when there is no confirmation state).

## Rollout dependencies

- Phase 1 (inbound trust) is a prerequisite.
- Phase 4 should be deployed before enabling 15-item cursor history UI
  (Phase 5), so the numeric map contract is stable when history entries are
  first presented.

## Rollback notes

Rolling back the entry-map logic restores positional selection. Any in-flight
numeric menu state uses the old positional scheme — these sessions will either
expire (15 min TTL) or the user will re-invoke the menu. No data loss.

## Acceptance evidence

Tests in `tests/Feature/Bot/BotWebhookTest` cover:

| Test | Assertion |
|---|---|
| `fonnte_numeric_menu_maps_selection_and_rejects_out_of_range_choice` | Entry-map resolution; out-of-range input ignored |
| `fonnte_numeric_menu_maps_pagination_on_the_active_page` | `98`/`99` navigate prev/next |
| `fonnte_numeric_menu_rejects_corrupt_state_and_clears_it` | Corrupt state → recovery message; state cleared |
| `fonnte_numeric_input_preserves_waiting_checkout_precedence` | `waiting_confirmation` takes precedence over numeric menu |
| `fonnte_numeric_input_without_state_returns_expired_menu_recovery` | No state → recovery message |
| `fonnte_long_command_still_works_and_telegram_does_not_create_numeric_state` | Telegram never writes numeric menu state |
| WhatsApp history 15-item window tests | `numeric_type: content` entries ≤ 15; nav entries use 98/99/0 |

## Security invariants

- Stale numeric input never directly performs a financial mutation — the
  confirmation state machine (Phase 3) is mandatory for all financial
  operations.
- Numeric menu state is sender-scoped; one sender's revision cannot be
  consumed by another sender.
- Fonnte replied-message IDs may be bound as an additional revision check if
  the transport reliably provides them, but are not depended upon.

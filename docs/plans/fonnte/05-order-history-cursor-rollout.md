# Phase 5 — Cursor-Based Order History and Rollout

## Objective

Replace page-number history queries with keyset/cursor pagination ordered by
`(created_at DESC, id DESC)`. WhatsApp shows 15 selectable entries per window;
Telegram uses its existing smaller size. Tampered, cross-user, cross-tenant, or
cross-source cursors must fail closed. Navigation handles keep Telegram callback
data within the 64-byte limit.

## Delivered files

| File | Change |
|---|---|
| `app/Services/Order/OrderHistoryService.php` | Keyset pagination; source-aware limits (15 WhatsApp, existing Telegram); `previous_cursor` / `next_cursor` / `current_cursor` return; `findForUserByReference()` for opaque numeric detail lookup |
| `app/Services/Order/OrderHistoryCursorCodec.php` | New: URL-safe HMAC-signed cursor tokens; version/direction/boundary; constant-time verify; no raw IDs in token |
| `app/Services/Bot/Adapters/OrderHistoryNavigationStateService.php` | New: 16-char random handles; 15-minute TTL; tenant/User/source HMAC binding; expired handle → fresh history window |
| `app/Services/Bot/BotCommandHandler.php` | `history` / `riwayat` routes to `OrderHistoryService`; detail lookup uses opaque reference; back-to-window restores cursor from handle |
| `app/Services/Bot/BotMessageFormatter.php` | History list uses `numeric_type: content` entries; detail back-button uses navigation handle; Telegram callbacks use handles when cursor would exceed 64 bytes |
| `tests/Feature/Bot/WhatsappOrderHistoryTest.php` | Feature tests for WhatsApp and Telegram history (see Acceptance evidence) |
| `tests/Unit/Services/Order/OrderHistoryServiceTest.php` | Unit tests for cursor codec, keyset traversal, tampering, cross-user rejection (see Acceptance evidence) |

## Cursor token format

```
base64url(JSON{v,d,t,i}) . base64url(HMAC-SHA256[:16])
```

- `v`: version integer
- `d`: direction (`older` | `newer` | `window`)
- `t`: `created_at` as `Y-m-d H:i:s.u`
- `i`: record `id` as decimal string

HMAC scope: `order-history-cursor | <tenant_scope> | <user_id> | <source>`

The token contains no raw user ID, phone number, or authorization-bearing data.
Decoding without matching HMAC returns `null`; the service returns
`invalid_cursor: true` and falls back to a fresh history window (no broader
query, no silent clamp).

## Navigation state handle (Telegram)

- 16-char cryptographically random handle stored in cache with 15-minute TTL.
- Bound to tenant, User ID, and source via HMAC on write.
- Callback: `history nav <handle>` — always ≤ 64 bytes.
- On expiry: returns to fresh history window with an informational note.

## Source-aware window sizes

| Source | Items per window |
|---|---|
| `whatsapp_gateway` | 15 |
| `telegram_gateway` | 5 (existing) |

## Migration order

1. Deploy `OrderHistoryCursorCodec` and `OrderHistoryService` changes.
2. Deploy formatter and adapter changes.
3. Old page-number history links become invalid — this is acceptable since
   WhatsApp numeric state expires in 15 minutes and Telegram inline keyboards
   are ephemeral.

## Rollout dependencies

- Phase 4 (stable numeric menus) must be deployed first so the 15-item window
  maps correctly to the entry scheme.
- Telegram Deposit remains disabled by default until its existing staging gate
  passes — this phase does not change that gate.

## Rollback notes

Rolling back the keyset implementation restores page-number queries. Any
in-flight cursors or navigation handles become invalid; users receive an
`invalid_cursor` recovery response (fresh history window). No data loss.

## Acceptance evidence

### `tests/Feature/Bot/WhatsappOrderHistoryTest` — 4 passed

| Test | Assertion |
|---|---|
| `linked_sender_receives_only_their_orders_and_numeric_detail_reference_is_scoped` | History list contains only owner's orders; detail lookup scoped; foreign reference returns "tidak ditemukan" |
| `whatsapp_history_uses_fifteen_numeric_entries_and_preserves_window_from_detail` | 15 `content` entries; `navigation_next` present; all callbacks ≤ 64 bytes; back-to-window excludes inserted-later order |
| `telegram_history_callbacks_remain_within_sixty_four_bytes` | All 7 callbacks (5 content + next + detail back) ≤ 64 bytes |
| `unverified_sender_is_denied_and_unlinked_telegram_is_prompted_to_link` | Unverified WhatsApp → denied; unlinked Telegram → link prompt |

### `tests/Unit/Services/Order/OrderHistoryServiceTest` — 7 passed

| Test | Assertion |
|---|---|
| `it_lists_only_the_users_orders_in_newest_deterministic_order` | User-scoped, newest first, masked order ID |
| `it_traverses_older_and_newer_windows_without_duplicates_for_identical_timestamps` | No duplicates or skips across pages with identical `created_at` |
| `it_preserves_a_window_when_new_orders_arrive` | `window` cursor excludes later inserts |
| `whatsapp_uses_fifteen_item_windows` | 15 items returned for `whatsapp_gateway` |
| `it_rejects_tampered_cross_user_and_cross_source_cursors` | Tampered, cross-user, cross-source all return `invalid_cursor: true` |
| `cursor_codec_rejects_invalid_direction_and_resolves_detail_reference` | `InvalidArgumentException` on invalid direction |
| `it_resolves_detail_by_opaque_numeric_reference_and_rejects_foreign_reference` | Owner detail resolves; foreign reference returns `null` |

## Security invariants

- History results are always scoped by the resolved User and tenant — cursor
  tokens are routing data, not authorization.
- Invalid/tampered cursors never fall back to a broader query or silently
  expose another user's orders.
- Navigation handles are navigation-only; detail lookup re-resolves User and
  applies `ownedOrders()` scope on every request.
- No raw User ID, tenant ID, or phone number appears in cursor tokens or
  navigation handles.

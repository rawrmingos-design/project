# Kontrak API H2H — sumber kebenaran (Fase 0)

> **Bahan internal, bukan untuk dipublikasikan.** Setiap baris di sini dipakai sebagai
> rujukan tunggal saat menulis docs di `docs-api/`. Aturan: kalau satu baris tidak bisa
> ditunjuk sumbernya (file:baris), baris itu dibuang — bukan ditebak.

Semua di bawah diekstrak dari kode di commit `c330966f` (branch `staging`).

---

## 1. Endpoint, method, middleware, throttle

Sumber: `routes/api.php:45-71`, `app/Http/Kernel.php:49-70`, `app/Providers/RouteServiceProvider.php:60-83`

Prefix `/api/v1`. **Single host** — sandbox adalah path, bukan host terpisah.
**Semua endpoint POST** — termasuk `status-order` dan `simulate-status`.

| Method | Path | Throttle | Auth |
|---|---|---|---|
| POST | `/api/v1/balance` | `reseller-api-balance` | `auth.api` + `reseller.ip.enforce` |
| POST | `/api/v1/category` | `reseller-api-category` | `auth.api` + `reseller.ip.enforce` |
| POST | `/api/v1/variant` | `reseller-api-variant` | `auth.api` + `reseller.ip.enforce` |
| POST | `/api/v1/order` | `reseller-api-order` | `auth.api` + `reseller.ip.enforce` |
| POST | `/api/v1/status-order/{invoice}` | `reseller-api-status` | `auth.api` + `reseller.ip.enforce` |
| POST | `/api/v1/sandbox/balance` | `reseller-api-balance` | `auth.sandbox.api` |
| POST | `/api/v1/sandbox/category` | `reseller-api-category` | `auth.sandbox.api` |
| POST | `/api/v1/sandbox/variant` | `reseller-api-variant` | `auth.sandbox.api` |
| POST | `/api/v1/sandbox/order` | `reseller-api-order` | `auth.sandbox.api` |
| POST | `/api/v1/sandbox/status-order/{invoice}` | `reseller-api-status` | `auth.sandbox.api` |
| POST | `/api/v1/sandbox/simulate-status/{invoice}` | `reseller-api-order` | `auth.sandbox.api` |

- Grup `v1` memasang `add.api.version` untuk seluruh isinya → header versi ada di **setiap**
  respons (`routes/api.php:45`).
- Alias middleware (`app/Http/Kernel.php`): `auth.api` = `ResolveLiveResellerIntegration`
  (`:49`), `auth.sandbox.api` = `ResolveSandboxResellerIntegration` (`:50`),
  `reseller.ip.enforce` = `EnforceResellerIpWhitelist` (`:69`).
- **Sandbox TIDAK di-enforce IP whitelist** — `reseller.ip.enforce` tidak dipasang di grup
  sandbox (`routes/api.php:57-70`).

---

## 2. Autentikasi & resolusi integrasi

Sumber: `ResolveLiveResellerIntegration.php:16-42`, `ResolveSandboxResellerIntegration.php:16-42`

- **Hanya `Authorization: Bearer <token>`.** Token di-hash sha256, lalu di-resolve:
  `resolveByHash(hash('sha256',$token), 'live'|'sandbox')`.
- Token **live** dan **sandbox** adalah record terpisah (mode berbeda) — token sandbox tidak
  berlaku di endpoint live dan sebaliknya.
- ⚠️ **`X-Reseller-Integration-Code` TIDAK PERNAH DIBACA KODE.** String itu hanya muncul di
  pesan error `EnforceResellerIpWhitelist.php:39` dan dua teks panel Filament
  (`ResellerIntegrationResource.php:93,106`). `ResellerIntegrationLookup` —
  satu-satunya tempat header itu relevan — **di-import tapi tidak dipakai** di kedua
  middleware (`grep -rn "ResellerIntegrationLookup" app/Http/Middleware/` hanya baris `use`).
  **Jangan dokumentasikan header ini sebagai syarat** — itu menyesatkan integrator.
- Middleware mengeset atribut request `api_user` + `live_reseller_integration` /
  `sandbox_reseller_integration`; `EnforceResellerIpWhitelist` membacanya dari atribut itu
  (bukan query DB ulang).

---

## 3. Envelope respons

Sumber: `ResellerApiResponse.php:24-42,108-119`, `OrderApiController.php`

### Sukses
```json
{"error": false, "code": 200, "message": "Success", "data": { ... }}
```

### Error generik (SEMUA jalur kecuali `/order` gagal)
```json
{"error": true, "code": 422, "message": "...", "error_code": "VALIDATION_FAILED"}
```
Kunci `details` **hanya** ditambahkan bila ada detail validasi (`ResellerApiResponse.php:36-41`).

### ⚠️ `/order` gagal = bentuk BERBEDA (TIDAK ada `error_code`)
Sumber: `OrderApiController.php:565-580`

```json
{"error": true, "code": 400, "message": "Order Failed",
 "data": {"invoiceNumber": null, "referenceNumber": "...", "code": "...",
          "user_id": "...", "zone_id": null, "price": 12345,
          "buyer_last_saldo": 50000, "status": "failed", "message": "..."}}
```
`data.message` = alasan dari provider yang sudah disanitasi
(`sanitizeOrderFailedReason()`, `:826-857`): pola sensitif (`api_key`, `secret`, `token`,
`password`, `credential`, `authorization`) → pesan generik; sisanya dipotong 200 char.
**Saldo tidak terpotong** di jalur ini (transaction tidak commit) → aman untuk retry dengan
`referenceNumber` yang sama.

`ResellerApiResponse::orderFailed()` (`error_code: ORDER_FAILED`, `data.balance_deducted`,
`data.can_retry`, `data.reason`) **ada di kode tapi TIDAK PERNAH DIPANGGIL** oleh kedua
controller API (`grep -rn "orderFailed" app/Http/Controllers/Api/` = kosong; `orderFailed`
lain yang ditemukan hanya variabel lokal di `InvoicePageController`). Jangan dokumentasikan
bentuk itu sebagai perilaku `/order`. Ini drift #3 di plan §1.

### `data` per endpoint

| Endpoint | Field `data` | Sumber |
|---|---|---|
| `/balance` | `name`, `telp` (=`no_wa`), `membership` (=`role`), `balance` | `OrderApiController.php:49-58` |
| `/category` | array `{code, name, type, is_active}` | `:77-82` |
| `/variant` | array `{code, name, is_active, price}` | `:150-157` |
| `/order` sukses | `invoiceNumber`, `referenceNumber`, `code`, `user_id`, `zone_id`, `price`, `buyer_last_saldo`, `status`, `message` | `:531-545` |
| `/order` duplikat (live) | `invoiceNumber`, `status`, `isDuplicate: true` — **hanya 3 field** | `:187-197` |
| `/order` duplikat (sandbox) | 9 field (sama seperti sukses + `isDuplicate`) | `SandboxOrderApiController.php:44-61` |
| `/order` gagal | lihat di atas (10 field, tanpa `error_code`) | `:565-580` |
| `/status-order/{invoice}` | `invoiceNumber`, `productName`, `userId`, `zoneId`, `statusCode`, `sn`, `keteranganSn` | `:806-821` |
| `/sandbox/simulate-status` | sama dengan `/status-order` (`buildStatusPayload()`) | `SandboxOrderApiController.php:184+` |

**Campuran penamaan yang harus didokumentasikan apa adanya:** `/order` memakai
**snake_case** (`user_id`, `zone_id`, `buyer_last_saldo`), sedangkan `/status-order`
memakai **camelCase** (`userId`, `zoneId`, `statusCode`).

**Tipe data:** `price`, `balance`, `buyer_last_saldo` = **integer rupiah** (cast
`Layanan.harga*`, `User.balance`, `Pembelian.harga` semuanya `integer`). `name`/`code`/`sn`
= string (di-cast `(string)`, jadi `null` → `""`).

`/order` sandbox: `price` selalu `0` (`SandboxOrderApiController.php:142`).

---

## 4. Header respons

Sumber: `AddApiVersionHeader.php:28-31`

| Konteks | Nilai |
|---|---|
| `/api/v1/*` (live) | `X-API-Version: 1` |
| `/api/v1/sandbox/*` | `X-API-Version: 1-sandbox` |

Header ini **respons**, bukan request. Tidak pernah disebut docs lama (drift #12).
**Tidak ada v2/v2.3/v2.4** — `git log -S"api/v1/product"` kosong; string `v2.4` hanya ada di
berkas docs lama itu sendiri.

---

## 5. 13 `error_code`

Sumber: `ResellerApiResponse.php:9-22` + titik pemakaian di bawah.

| Konstanta | HTTP | Muncul dari |
|---|---|---|
| `ACCESS_TOKEN_REQUIRED` | **403** | `ResolveLive...:18-23`, `ResolveSandbox...:18-23`, `OrderApiController.php:719-725` — header `Authorization` kosong |
| `INVALID_TOKEN` | **403** | `ResolveLive...:30-35`, `ResolveSandbox...:30-35` — token tak dikenal/nonaktif untuk mode itu |
| `INTEGRATION_CODE_REQUIRED` | **422** | `EnforceResellerIpWhitelist.php:30-42` — atribut integrasi tak ada di request |
| `INVALID_INTEGRATION_CODE` | **403** | `SandboxOrderApiController.php:77-83` — integrasi sandbox tak ada di atribut request |
| `IP_WHITELIST_EMPTY` | 403 | `EnforceResellerIpWhitelist.php:52-63` — whitelist kosong di profil live |
| `IP_NOT_WHITELISTED` | 403 | `EnforceResellerIpWhitelist.php:76-88` — IP di luar whitelist/CIDR |
| `INVALID_JSON_PAYLOAD` | 400 | `OrderApiController.php:739-742` — body bukan JSON valid |
| `VALIDATION_FAILED` | 422 | `OrderApiController.php:231-235` (Check-ID), `:764-767` (field wajib), `Sandbox...:214-219` (status invalid) |
| `CODE_NOT_FOUND` | 404 | `OrderApiController.php:114-118`, `:208-212`, `Sandbox...:68-72` |
| `INSUFFICIENT_BALANCE` | 400 | `OrderApiController.php:250-254` |
| `ORDER_FAILED` | 400 | konstanta ada; **tidak terpakai di jalur `/order` nyata** (lihat §3) |
| `INVOICE_NOT_FOUND` | 404 | `OrderApiController.php:607-611`, `Sandbox...:170-174`, `:233-237` |
| `TOO_MANY_REQUESTS` | 429 | `app/Exceptions/Handler.php:66-77` via `tooManyRequests()` |

⚠️ **Auth memakai HTTP 403, bukan 401.** `OrderApiController::unauthenticatedResponse()`
juga memilih antara `ACCESS_TOKEN_REQUIRED` dan `INVALID_TOKEN`, keduanya 403.

### Bentuk 429 (beda — ada `retryAfterSeconds`)
Sumber: `ResellerApiResponse.php:108-119`, `app/Exceptions/Handler.php:66-77`

```json
{"error": true, "code": 429, "message": "Too Many Requests",
 "error_code": "TOO_MANY_REQUESTS", "retryAfterSeconds": 60}
```
`retryAfterSeconds` diambil dari header `Retry-After` (fallback `60`). Handler juga
meneruskan header asli (`$exception->getHeaders()`) → `Retry-After` ikut terkirim.

---

## 6. Status API (7 nilai)

Sumber: `PembelianStatus.php:7-14,26-35,107-110,127-139`

| Status internal | `statusCode` (API) | `statusLabel` | Final? |
|---|---|---|---|
| `success` | `Success` | `Success` | ya |
| `pending` | `Pending` | `Pending` | tidak |
| `processing` | `Processing` | `Processing` | tidak |
| `failed` | `Failed` | `Failed` | ya |
| `cancelled` | **`Canceled`** (satu L) | **`Cancelled`** (dua L) | ya |
| `expired` | `Expired` | `Expired` | ya |
| `refunded` | `Refunded` | `Refunded` | ya |
| (tak dikenal / kosong) | `Pending` | `Unknown` | tidak |

⚠️ **`statusCode` untuk batal = `Canceled` (satu L)**; `statusLabel` = `Cancelled` (dua L).
`normalize()` (`:70-87`) menerima alias bebas lalu memetakan ke nilai internal; nilai tak
dikenal jatuh ke `unknown` → `statusCode` `Pending`.

---

## 7. Body & validasi `/order`

Sumber: `OrderApiController.php:174-183` (validate), `:185` (idempotency), `:221-240` (Check-ID)

| Field | Wajib | Aturan |
|---|---|---|
| `code` | ya | string — SKU yang diminta (dicocokkan ke `Layanan.provider_id`) |
| `referenceNumber` | ya | string — **kunci idempotency** |
| `user_id` | ya | string, maks 100 |
| `zone_id` | tidak | string, maks 100 |

Body dibaca **hanya** sebagai JSON (`parseJsonPayload()`; `INVALID_JSON_PAYLOAD` bila rusak).

**Idempotency:** `findExistingOrderByReference()` dipanggil **sebelum** validasi Check-ID
(`:185`) dan sebelum provider. `referenceNumber` sama dalam mode sama → respons duplikat
(live: 3 field), **tanpa** memanggil provider, **tanpa** memotong saldo.

**Pola `invoiceNumber`:** live `TRX-{ymdHis}{8 huruf acak}` (`generateOrderId()`, `:682-692`);
sandbox `TRX-SBX-{His}{4 huruf acak}` (`SandboxOrderApiController.php:92`).

**Validasi Check-ID** (`OrderApiController.php:221-235`): dijalankan **sebelum** provider
dipanggil. Bila `skip_check !== true` dan (`status.code != 200` atau `username` kosong) →
`VALIDATION_FAILED` 422 dengan pesan `'User ID tidak ditemukan atau tidak valid.'`.
Konsekuensi yang wajib disebut docs: `user_id` salah format **dan** gangguan di penyedia
Check-ID menghasilkan **pesan yang sama** — integrator tak bisa membedakan hanya dari pesan.

---

## 8. IP whitelist

Sumber: `EnforceResellerIpWhitelist.php`, `app/Support/IpAddressMatcher.php`

- **Live**: **wajib**. Whitelist kosong → `IP_WHITELIST_EMPTY` 403; tidak cocok →
  `IP_NOT_WHITELISTED` 403 (pesan menyertakan IP pemanggil sebagai panduan penambahan).
  Mendukung IPv4 eksak + CIDR.
- **Sandbox**: **tidak** di-enforce (middleware tidak dipasang di grup sandbox).
- Kebijakan "kosong = tolak" dikonfirmasi di komentar kelas (`:19-22`).

---

## 9. Rate limit (token + IP, per menit)

Sumber: `RouteServiceProvider.php:64-82,359-370`

Dua limit dipasang **bersamaan** per endpoint: per-token **dan** per-IP. Key token:
`'missing-token:' . ip` bila header kosong (request tanpa token pun tetap dibatasi).

| Endpoint | Per token/menit | Per IP/menit |
|---|---|---|
| `/balance` | 30 | 120 |
| `/category` | 60 | 180 |
| `/variant` | 90 | 240 |
| `/order` (+ `/sandbox/simulate-status`) | 20 | 60 |
| `/status-order` | 180 | 300 |

Lewat batas → HTTP 429, `error_code: TOO_MANY_REQUESTS`, plus `retryAfterSeconds` +
header `Retry-After` (§5).

---

## 10. Webhook keluar

Sumber: `ResellerCallbackDeliveryService.php:266-287` (payload), `:198-215` (header),
`:291-303` (event), `:355-365` (test), `DeliverResellerWebhookJob.php:25,47,88`

### Event
| Konteks | Nama event |
|---|---|
| Live | `h2h.order.updated` (`LIVE_EVENT_NAME`, `:14`) |
| Sandbox | `h2h.sandbox.order.updated` (`SANDBOX_EVENT_NAME`, `:30`) |
| Uji manual | `h2h.webhook.test` (`sendTest`, `:355-365`) |

⚠️ Docs lama menyebut `order.success` / `order.failed` — **tidak ada di kode** (drift #1).

### Payload (14 field, **flat** — tidak ada pembungkus `data`)
```json
{
  "event": "h2h.order.updated",
  "timestamp": "2026-10-04T12:00:00+07:00",
  "invoiceNumber": "TRX-...",
  "referenceNumber": "...",
  "code": "...",
  "productName": "...",
  "userId": "...",
  "zoneId": "...",
  "statusCode": "Success",
  "statusLabel": "Success",
  "sn": "...",
  "keteranganSn": "...",
  "sandbox": false,
  "environment": "live"
}
```
`code` = `pembelian.active_provider_sku`. `sn` dan `keteranganSn` bersumber dari kolom yang
sama (`keterangan_sn`) dan dikirim dua kali demi kompatibilitas. `sandbox` = boolean;
`environment` = string `live`/`sandbox`.

### Header
| Header | Isi | Sumber |
|---|---|---|
| `X-Callback-Event` | nama event | `:201` |
| `X-Callback-Version` | `max(1, profile.version)` | `:202` |
| `X-Callback-Timestamp` | ISO-8601 (sama dengan `payload.timestamp`) | `:203` |
| `<signature_header>` | HMAC — nama header **dapat dikonfigurasi per profil**, default `X-Callback-Signature` | `:206`, `:316` |

### Signature
- Algoritma **per profil**: `sha1` / `sha256` / `sha512` (`resolveSigningAlgorithm`, `:305`).
- Bahan: **raw body JSON** (`json_encode(..., JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)`).
- Nilai: **hex mentah dari `hash_hmac`** — **TANPA** prefix `sha256=`.
  Docs lama mengajarkan `str_replace('sha256=', '', $signature)` — kode contoh itu **salah**.
- Secret: `$profile->decryptedWebhookSecret()` (`:187`).

### Retry
- `$tries = 5` (`DeliverResellerWebhookJob.php:25`)
- `backoff()` → **60s, 300s, 900s, 3600s** (`:47`)
- Docs lama: "3x (1s, 5s, 15s)" — salah total (drift #6).
- Habis → `Log::error('Reseller webhook delivery permanently failed after all retries.')` (`:88`)
- Delivery tercatat di tabel `reseller_callback_deliveries` (`:123-138`) — berguna untuk audit.

---

## 11. Sandbox

Sumber: `SandboxOrderApiController.php`

- Single host; sandbox = `/api/v1/sandbox/*`; token terpisah; IP whitelist tidak berlaku.
- `price` selalu `0` (`:142`), saldo **tidak** terpotong.
- Pola invoice: `TRX-SBX-{His}{4 huruf acak}` (`:92`).
- `simulate-status` menerima `status` ∈ `pending`, `processing`, `success`, `failed`,
  `cancelled` (`:207-213`); di luar itu → `VALIDATION_FAILED` 422.
- `simulate-status` mengembalikan `buildStatusPayload()` — **bukan**
  `old_status`/`new_status`/`webhook_triggered` seperti kata docs lama (drift #7).
- `simulate-status` memicu webhook sandbox bila profil callback aktif.

---

## 12. Nilai nyata `type` kategori & `membership`

- `Kategori.tipe` default `game` (`database/migrations/2026_03_20_073406_create_kategoris_table.php:26`).
  Nilai di staging: `game`, `app`, `pulsa`, `joki`, `populer`, `vilogml`.
- `Layanan.status == 'available'` + SKU hasil routing tidak kosong menentukan `is_active`
  variant (`OrderApiController.php:143-148`).
- `membership` (=`User.role`) yang memengaruhi tier harga: **`Platinum`**, **`Gold`**,
  **`Member`** (case-sensitive; `OrderApiController.php:133-141`, `:238-246`). Role lain
  (mis. `Admin`) jatuh ke `harga` dasar.
- `/variant` mengembalikan harga **sesuai tier user yang memanggil** — bukan daftar semua
  tier. `code` di respons = SKU hasil routing, dapat berbeda dari `code` yang dipakai di
  `/order` bila routing memilih provider lain.

---

## 13. Drift docs lama vs kode (ringkas — rujukan saat menulis)

| # | Docs lama | Kode nyata |
|---|---|---|
| 1 | event `order.success`/`order.failed`, payload ber-`data`, signature `sha256=` | `h2h.*`, payload flat 14 field, signature mentah |
| 2 | "tidak perlu header tambahan" | auth bearer saja (`X-Reseller-Integration-Code` **tidak dibaca**) |
| 3 | error uniform + `data` | `{error,code,message,error_code}`; `/order` gagal beda lagi |
| 4 | banner "BREAKING CHANGES v2.4" + `/product`→`/category` | penomoran `v2.3`/`v2.4` **fiktif** (`git tag` kosong), tapi rename `/product`→`/category` di `def1b4e0` **NYATA** |
| 5 | `status-order` balikin `user_id`/`zone_id`/`success` | `userId`/`zoneId`/`statusCode`/`sn`/`keteranganSn` |
| 6 | retry 3x (1s, 5s, 15s) | 5x (60s, 300s, 900s, 3600s) |
| 7 | `simulate-status` balikin `old_status`/`new_status` | `buildStatusPayload()` |
| 8 | base URL `api.namadomain.com`, host sandbox terpisah | single host, sandbox = path |
| 9 | rate limit tak disebut | token+IP per menit, lihat §9 |
| 10 | Check-ID tak disebut | ada, 422 sebelum provider |
| 11 | respons duplikat | live 3 field; sandbox 9 field |
| 12 | `X-API-Version` tak disebut | `1` / `1-sandbox` |
| 13 | sebagian endpoint tampak GET | **semua POST** |
| 14 | auth HTTP 401 | **403** |

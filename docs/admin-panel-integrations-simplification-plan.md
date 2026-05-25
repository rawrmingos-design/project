# Admin Panel Simplification Plan for Inbound + Outbound Integrations

Dokumen ini merangkum arah penyederhanaan admin panel supaya fitur inbound dan outbound terasa lebih mudah dipahami client, tanpa mencampur model data dan logika backend yang sebenarnya berbeda.

## Tujuan

Yang ingin dicapai:

1. Client tidak melihat istilah teknis yang terlalu banyak sejak awal.
2. Setup integrasi terasa seperti alur 1-2-3, bukan kumpulan resource yang terpisah-pisah.
3. Inbound dan outbound tetap dipisah di backend, tetapi dibungkus dalam bahasa UI yang lebih sederhana.

Yang sengaja **tidak** dilakukan:

- menggabungkan tabel inbound dan outbound
- mencampur policy IP inbound dengan konfigurasi webhook outbound
- menghilangkan audit log atau observability

## Masalah UI Saat Ini

Di admin panel sekarang, client berpotensi melihat beberapa menu yang terasa berdiri sendiri:

- `Inbound Whitelist`
- `Reseller Integrations`
- `Reseller Callbacks`
- `Order Management` untuk delivery log

Secara teknis ini benar, tetapi dari sudut pandang client biasanya muncul pertanyaan:

- “Saya harus buka yang mana dulu?”
- “Mana yang buat provider callback masuk?”
- “Mana yang buat callback keluar ke reseller?”
- “Kenapa integration dan callback dipisah?”

## Prinsip Desain Baru

### 1. Sederhanakan bahasa, jangan sederhanakan struktur data secara paksa

Backend tetap boleh punya:

- `InboundSourcePolicy`
- `ResellerIntegration`
- `ResellerCallbackProfile`
- `ResellerCallbackDelivery`

Tetapi UI sebaiknya berbicara dalam bahasa yang lebih familiar:

- `Partner Connections`
- `Incoming Callbacks`
- `Outgoing Callbacks`
- `Delivery Logs`

### 2. Jadikan setup sebagai alur, bukan puzzle

Client lebih gampang paham kalau urutannya eksplisit:

1. Tambah partner / integration
2. Tentukan callback masuk
3. Tentukan callback keluar
4. Cek log hasilnya

### 3. Pakai mode dasar dulu, advanced belakangan

Sebagian besar client tidak perlu mengatur setiap field teknis.

Di mode dasar, tampilkan hanya field yang memang paling sering dipakai.
Field teknis bisa ditaruh di section `Advanced` yang default-nya collapsed.

## Struktur Menu yang Direkomendasikan

### Opsi yang paling disarankan

Tambahkan satu grup yang lebih jelas, misalnya:

- `Integrations`

Di dalamnya:

1. `Connections`
2. `Incoming Rules`
3. `Outgoing Webhooks`
4. `Logs`

### Mapping ke resource yang sekarang

#### Connections
Wrapper/client-facing view untuk:

- `Reseller Integrations`

Fungsi yang client pahami:

- siapa partner / reseller ini
- integration code-nya apa
- aktif atau tidak

#### Incoming Rules
Wrapper/client-facing view untuk:

- `Inbound Whitelist`

Fungsi yang client pahami:

- IP mana yang boleh mengirim callback ke sistem kita
- source mana yang sedang `log_only`
- source mana yang sudah `enforce`

#### Outgoing Webhooks
Wrapper/client-facing view untuk:

- `Reseller Callbacks`

Fungsi yang client pahami:

- ke URL mana server kita mengirim callback
- secret apa yang dipakai
- apakah callback aktif

#### Logs
Gabungkan akses baca ke:

- inbound decision logs ringkas
- outbound delivery log ringkas
- link ke detail order bila perlu

Client tidak perlu tahu semua tabel di baliknya. Mereka cukup lihat “hasil pengiriman” dan “hasil penerimaan”.

## Desain Halaman yang Lebih Ramah Client

### A. Connections

Tujuan halaman:

- membuat atau mengedit partner live
- melihat status singkat apakah partner itu sudah siap dipakai

Kolom utama:

- `Partner / Username`
- `Integration Code`
- `Direction Summary`
- `Status`
- `Updated`

`Direction Summary` bisa berupa badge kecil:

- `Incoming ready`
- `Outgoing ready`
- `Needs setup`

### B. Incoming Rules

Tujuan halaman:

- mengatur whitelist inbound tanpa istilah yang terlalu berat

Ubah beberapa istilah:

- `Inbound Whitelist` -> `Incoming Rules`
- `Source Domain` -> `Source Type`
- `Source Name` -> `Provider / Gateway`
- `Mode` tetap boleh dipakai, tapi beri helper text:
  - `log_only`: hanya mencatat, belum memblokir
  - `enforce`: aktif memblokir IP yang tidak cocok

Tampilan daftar:

- `Source Type`
- `Provider / Gateway`
- `Mode`
- `Active`
- `Allowed IP Count`
- `Last Updated`

### C. Outgoing Webhooks

Tujuan halaman:

- mengatur callback keluar ke reseller/H2H partner

Ubah istilah:

- `Reseller Callbacks` -> `Outgoing Webhooks`
- `Callback Profile` -> `Webhook Destination`

Field yang tampil di mode dasar:

- `Integration`
- `Enabled`
- `Callback URL`
- `Webhook Secret`

Section `Advanced`:

- `Signing Algorithm`
- `Signature Header`
- `Version`

Default yang disarankan:

- `sha256`
- `X-Callback-Signature`
- `1`

Dengan begitu, banyak client cukup mengisi 4 field dasar saja.

### D. Logs

Tujuan halaman:

- memberi satu tempat baca hasil tanpa memaksa client masuk ke detail order tiap saat

Tab yang disarankan:

1. `Incoming`
2. `Outgoing`

#### Incoming

Kolom ringkas:

- `Source`
- `Client IP`
- `Mode`
- `Decision`
- `Reason`
- `Time`

#### Outgoing

Kolom ringkas:

- `Integration`
- `Order / Invoice`
- `Callback URL`
- `Delivery Status`
- `HTTP Status`
- `Last Error`
- `Time`

## Basic Mode vs Advanced Mode

Ini penting untuk mengurangi rasa “ribet”.

### Basic mode

Tampilkan hanya:

- integration code
- active toggle
- callback URL
- webhook secret
- incoming mode
- daftar IP

### Advanced mode

Baru tampilkan:

- signature header
- signing algorithm
- version
- notes / metadata
- raw delivery info
- detail source typing seperti `supplier_callback` vs `payment_gateway`

Kalau Filament belum punya mekanisme mode yang formal, ini bisa ditiru lewat:

- section `Advanced`
- helper text yang ringkas
- field default yang sudah diisi

## Setup Flow yang Direkomendasikan untuk Client

Client akan jauh lebih nyaman kalau kita arahkan ke flow tetap seperti ini:

### Flow 1: Menyalakan integrasi baru

1. buka `Connections`
2. buat integration code
3. buka `Incoming Rules` bila partner juga mengirim callback ke kita
4. buka `Outgoing Webhooks` bila kita perlu kirim callback ke mereka
5. buka `Logs` untuk validasi

### Flow 2: Audit masalah callback

1. cek `Logs`
2. kalau inbound, lihat `Incoming`
3. kalau outbound, lihat `Outgoing`
4. jika perlu, klik invoice/order terkait untuk detail

## Rekomendasi Implementasi Bertahap

### Tahap 1

Yang paling hemat effort:

- ubah label navigasi
- rapikan helper text
- sembunyikan field advanced outbound
- buat grouping menu `Integrations`

### Tahap 2

- buat halaman ringkasan `Partner Connections`
- tampilkan readiness status inbound/outbound per integration
- buat shortcut dari connection ke incoming/outgoing config

### Tahap 3

- tambahkan halaman `Logs` yang memang jadi hub baca inbound + outbound
- pertimbangkan tab `Basic` dan `Advanced`

## Keputusan Desain yang Direkomendasikan

Kalau mau paling sederhana dan tetap kuat, aku sarankan arah final ini:

- Navigation Group: `Integrations`
- Menu:
  - `Connections`
  - `Incoming Rules`
  - `Outgoing Webhooks`
  - `Logs`

Dan di level form:

- tampilkan mode dasar dulu
- pindahkan field teknis ke `Advanced`
- gunakan helper text yang menjelaskan fungsi bisnis, bukan istilah internal backend

## Kesimpulan

Penyederhanaan terbaik di sini bukan dengan menyatukan inbound dan outbound menjadi satu model data, tetapi dengan:

- menyatukan **narasi UI**
- menyederhanakan **bahasa**
- merapikan **alur setup**
- dan memindahkan detail teknis ke level `Advanced`

Hasil akhirnya: client merasa panel lebih sederhana, sementara kita tetap menjaga struktur sistem tetap sehat dan mudah di-maintain.

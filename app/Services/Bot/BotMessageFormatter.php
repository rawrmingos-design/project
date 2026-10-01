<?php

namespace App\Services\Bot;

class BotMessageFormatter
{
    private const PAGE_SIZE = 8;

    private const CATEGORY_EMOJIS = [
        'top-up-games' => '🎮',
        'top-up' => '🎮',
        'games' => '🎮',
        'pulsa-data' => '📱',
        'pulsa' => '📱',
        'data' => '📱',
        'app-premium' => '👑',
        'premium' => '👑',
        'voucher' => '🎟️',
        'e-wallet' => '💳',
        'wallet' => '💳',
        'streaming' => '🎬',
    ];

    /**
     * Sapaan pembuka yang dipakai di pesan menu & panduan.
     *
     * SENGAJA tidak memuat kontak admin. Dulu kontak (berupa nomor WhatsApp
     * mentah) ditempel di sini, padahal di Telegram nomor telepon tidak bisa
     * dipencet dan membocorkan nomor pribadi. Sekarang kontak admin punya
     * SATU tempat saja: blok "❓ Butuh Bantuan?" di pesan panduan, lengkap
     * dengan tautan yang bisa dipencet.
     *
     * Nada sapaannya sengaja umum ("game & aplikasi premium"), bukan khusus
     * top up game — katalog toko mencakup produk game maupun layanan lain.
     */
    private function storeIntro(): string
    {
        $storeName = trim((string) config('app.name', env('APP_NAME', 'Store')));

        return implode("\n", [
            __('bot.intro_welcome', ['store' => $storeName]),
            '',
            __('bot.intro_tagline'),
        ]);
    }

    private const GAME_EMOJIS = [
        'mobile-legends' => '⚔️',
        'mlbb' => '⚔️',
        'free-fire' => '🔫',
        'ff' => '🔫',
        'fc-mobile' => '⚽️',
        'fifa' => '⚽️',
        'pubg' => '🔫',
        'valorant' => '🔫',
        'genshin' => '🧙',
        'honkai' => '🚀',
        'roblox' => '🧱',
        'steam' => '🎮',
        'garena' => '🔥',
        'point-blank' => '🔫',
        'higgs' => '🎲',
        'arena-breakout' => '🪖',
    ];

    /**
     * Pesan "Akses Terbatas" saat user belum bergabung ke SEMUA channel wajib.
     *
     * Channel yang kurang ditampilkan sebagai daftar, masing-masing dengan
     * tombol Gabung sendiri, sehingga user yang hanya kurang satu channel
     * tidak perlu menebak mana yang terlewat.
     *
     * @param array<int, array{id: string, url: string, label: string}> $missingChannels
     * @return array{text: string, buttons: array}
     */
    public function formatTelegramMembershipRequired(array $missingChannels): array
    {
        $missingChannels = array_values(array_filter(
            $missingChannels,
            static fn ($channel): bool => is_array($channel)
                && trim((string) ($channel['id'] ?? '')) !== ''
                && trim((string) ($channel['url'] ?? '')) !== '',
        ));

        if ($missingChannels === []) {
            // Tidak ada channel yang bisa ditampilkan (konfigurasi kosong).
            // Jangan tampilkan gerbang tanpa jalan keluar — pakai pesan
            // gangguan agar user bisa mencoba lagi.
            return $this->formatTelegramMembershipUnavailable();
        }

        $single = count($missingChannels) === 1;
        $lines = [
            __('bot.gate_title'),
            '',
            $single ? __('bot.gate_intro_single') : __('bot.gate_intro_multi'),
            '',
        ];

        foreach ($missingChannels as $channel) {
            $id = trim((string) $channel['id']);
            $label = trim((string) ($channel['label'] ?? ''));

            $lines[] = ($label !== '' && $label !== $id)
                ? "👥 *{$this->escapeMarkdown($label)}* — {$id}"
                : "👥 {$id}";
        }

        $lines[] = '';
        $lines[] = __('bot.gate_verify_hint', $this->buttonNamePlaceholders());

        $buttons = [];

        foreach ($missingChannels as $channel) {
            $id = trim((string) $channel['id']);
            $label = trim((string) ($channel['label'] ?? ''));
            // Label tombol tombol 'Gabung' tidak dikenali parser (URL button),
            // jadi aman memakai copy lang; hanya namanya yang interpolasi.
            $text = __('bot.gate_join_channel', [
                'label' => ($label !== '' && $label !== $id) ? $label : $id,
            ]);

            $buttons[] = [$this->urlButton($text, trim((string) $channel['url']))];
        }

        // Label dari `kbd_gate_verified` (bahasa aktif): tombol ini
        // dikirim sebagai CALLBACK, jadi tidak terikat peta label parser.
        $buttons[] = [$this->button(
            $this->keyboardLabel('bot.kbd_gate_verified', '✅ Sudah Bergabung'),
            'menu',
        )];

        return [
            'text' => implode("\n", $lines),
            'buttons' => $buttons,
        ];
    }

    /**
     * Konfirmasi setelah user BERHASIL melewati gerbang keanggotaan.
     *
     * Sebelumnya tidak ada pesan ini: user yang baru bergabung langsung
     * dilempar ke menu tanpa penjelasan, sehingga tidak ada tanda bahwa
     * syaratnya sudah terpenuhi.
     */
    public function formatTelegramMembershipVerified(string $firstName = ''): array
    {
        $sapaan = trim($firstName) !== ''
            ? __('bot.gate_verified_hello', ['name' => $this->escapeMarkdown(trim($firstName))])
            : '';

        $names = $this->buttonNamePlaceholders();

        return [
            'text' => implode("\n", [
                __('bot.gate_verified_title'),
                '',
                $sapaan . __('bot.gate_verified_body'),
                '',
                // Rujuk nama TOMBOL-nya, bukan perintah mentah, dan ambil dari
                // `kbd_*` sehingga selalu sama dengan keyboard yang dirender.
                __('bot.gate_verified_hint', $names),
            ]),
            'buttons' => [
                [$this->button($names['menu'], 'menu')],
                [$this->button($names['help'], 'help')],
            ],
        ];
    }

    /**
     * Pesan saat gate TIDAK BISA berfungsi karena masalah SETELAN
     * (bot belum jadi anggota/admin di channel wajib).
     *
     * Dibedakan dari `formatTelegramMembershipUnavailable()`: yang itu untuk
     * gangguan sesaat dan menyuruh user "coba lagi" — masuk akal. Yang ini
     * tidak akan sembuh sendiri, jadi menyuruh user mencoba terus adalah
     * kebohongan yang membuatnya menunggu tanpa akhir.
     *
     * User tidak diberi detail teknis internal; cukup tahu bahwa ini bukan
     * salahnya dan sudah dilaporkan ke admin.
     */
    public function formatTelegramMembershipMisconfigured(): array
    {
        $adminUrl = trim((string) config('services.telegram-bot-api.admin_contact_url', ''));

        $buttons = [];

        if (filter_var($adminUrl, FILTER_VALIDATE_URL) !== false) {
            $buttons[] = [$this->urlButton(__('bot.gate_btn_contact'), $adminUrl)];
        }

        return [
            'text' => implode("\n", [
                __('bot.gate_maintenance_title'),
                '',
                __('bot.gate_maintenance_body'),
            ]),
            'buttons' => $buttons,
        ];
    }

    /**
     * Pesan saat verifikasi TIDAK BISA dijalankan karena gangguan sesaat
     * (timeout / Telegram sedang bermasalah). Di sini "coba lagi" masuk akal.
     *
     * @return array{text: string, buttons: array}
     */
    public function formatTelegramMembershipUnavailable(): array
    {
        return [
            'text' => implode("\n", [
                __('bot.gate_unavailable_title'),
                '',
                __('bot.gate_unavailable_body'),
            ]),
            // Komentar lama salah: 'Coba Lagi' BUKAN label parser — tombol ini
            // dikirim sebagai callback (dikunci
            // `test_label_callback_driven_bukan_perintah_teks`), jadi aman
            // mengikuti bahasa aktif.
            'buttons' => [[$this->button(__('bot.gate_btn_retry'), 'menu')]],
        ];
    }

    /**
     * @return array{text: string, buttons: array}
     */
    public function formatCategories(
        array $data,
        int $page = 1,
        ?BotGatewayCapabilities $capabilities = null,
    ): array {
        $pageSize = $capabilities?->menuPageSize() ?? self::PAGE_SIZE;
        $capabilities ??= BotGatewayCapabilities::forSource(null);
        if (! ($data['ok'] ?? false) || empty($data['data'])) {
            return [
                'text' => __('bot.menu_categories_unavailable'),
                'buttons' => [],
            ];
        }

        $pagination = $this->paginate($data['data'], $page, $pageSize);

        // Layout dibelah per channel, dan itu BUKAN kerapian belaka.
        //
        // Jalur WhatsApp merakit peta nomornya DARI TOMBOL
        // (`FonnteAdapter::numericEntries()` membaca `response['buttons']`).
        // Begitu tombol kategori dihapus demi tampilan daftar Telegram, peta
        // nomor WhatsApp jadi kosong dan SEMUA angka yang diketuk user WA
        // berbalas "menu kedaluwarsa" — transaksi WhatsApp mati total. Karena
        // itu jalur WA dipertahankan apa adanya, dan hanya Telegram yang
        // memakai tampilan daftar bernomor.
        return $capabilities->source() === BotGatewayCapabilities::SOURCE_TELEGRAM
            ? $this->telegramCategoryList($pagination)
            : $this->whatsappCategoryButtons($pagination, $capabilities);
    }

    /**
     * Layar Menu Utama Telegram: daftar kategori bernomor di TEKS.
     *
     * SENGAJA tanpa sapaan (`storeIntro`), tagline, dan instruksi "pilih
     * kategori di bawah": item sudah tampil sebagai daftar, jadi kalimat
     * pengantar hanya mendorong pilihan ke bawah lipatan layar.
     *
     * Nama kategori TIDAK lagi masuk tombol, jadi nomor di teks adalah
     * satu-satunya penanda posisi. `numeric_menu.entries` WAJIB terisi — tanpa
     * itu user melihat daftar yang tidak bisa dipilih sama sekali.
     */
    private function telegramCategoryList(array $pagination): array
    {
        $lines = [__('bot.menu_list_title'), ''];
        $entries = [];
        $number = 0;

        foreach ($pagination['items'] as $type) {
            $number++;
            $name = (string) ($type['name'] ?? '') !== ''
                ? (string) $type['name']
                : __('bot.menu_category_fallback');

            $lines[] = __('bot.menu_item_numbered', ['number' => $number, 'name' => $name]);

            // `command` dieksekusi saat nomor dipilih; `label` disimpan supaya
            // pesan "pilihan tidak valid" bisa menampilkan ulang daftar aktif.
            $entries[(string) $number] = [
                'type' => 'content',
                'label' => $name,
                'command' => 'kategori ' . (string) ($type['slug'] ?? ''),
            ];
        }

        // Halaman berikutnya/sebelumnya memakai nomor yang sama seperti jalur
        // WhatsApp (98/99), sehingga tombol angka bisa dipakai pindah halaman
        // walaupun tombol inline tidak terkirim bersama pesan menu.
        if ((int) ($pagination['total_pages'] ?? 1) > 1) {
            $page = (int) $pagination['page'];
            $totalPages = (int) $pagination['total_pages'];

            if ($page > 1) {
                $entries['98'] = [
                    'type' => 'navigation_previous',
                    'label' => '⬅️ Prev',
                    'command' => 'menu page:' . ($page - 1),
                ];
            }
            if ($page < $totalPages) {
                $entries['99'] = [
                    'type' => 'navigation_next',
                    'label' => 'Next ➡️',
                    'command' => 'menu page:' . ($page + 1),
                ];
            }
        }

        $lines[] = '';
        $lines[] = $this->menuListFooter($pagination);

        $response = [
            'text' => implode("\n", $lines),
            'buttons' => array_merge(
                $this->menuNavigationButtons($pagination),
                $this->menuActionButtons(BotGatewayCapabilities::forSource(
                    BotGatewayCapabilities::SOURCE_TELEGRAM,
                )),
            ),
            'numeric_menu' => [
                'menu' => 'categories',
                'parent_menu' => null,
                'page' => $pagination['page'],
                'entries' => $entries,
            ],
        ];

        $bannerUrl = $this->telegramMenuBannerUrl(BotGatewayCapabilities::forSource(
            BotGatewayCapabilities::SOURCE_TELEGRAM,
        ));
        if ($bannerUrl !== null) {
            $response['photo_url'] = $bannerUrl;
        }

        return $response;
    }

    /**
     * Layar Menu Utama WhatsApp — perilaku LAMA, tanpa perubahan apa pun.
     *
     * Dipertahankan utuh karena jalur WhatsApp merakit peta nomornya dari
     * tombol di sini; mengubah susunannya mematikan pemilihan nomor di WA.
     */
    private function whatsappCategoryButtons(array $pagination, BotGatewayCapabilities $capabilities): array
    {
        $items = [];

        foreach ($pagination['items'] as $type) {
            $slug = (string) ($type['slug'] ?? '');
            $items[] = $this->button(
                $this->categoryButtonLabel(
                    (string) ($type['name'] ?? '') !== '' ? (string) $type['name'] : __('bot.menu_category_fallback'),
                    $slug,
                    $type['icon'] ?? null,
                ),
                'kategori ' . $slug,
                'content',
            );
        }

        $buttons = array_chunk($items, 2);
        $buttons = $this->appendPagination($buttons, 'menu', $pagination);

        $capabilityButtons = [];
        if ($capabilities->supports('leaderboard')) {
            $capabilityButtons[] = $this->button('🏆 Leaderboard', 'leaderboard', 'global_action');
        }
        if ($capabilities->supports('deposit')) {
            $capabilityButtons[] = $this->button('💰 Deposit', 'deposit', 'global_action');
        }
        if ($capabilityButtons !== []) {
            $buttons[] = $capabilityButtons;
        }

        return [
            'text' => $this->storeIntro() . "\n\n" . __('bot.menu_title') . $this->pageSuffix($pagination)
                . "\n" . __('bot.menu_pick_category'),
            'buttons' => $buttons,
            'numeric_menu' => [
                'menu' => 'categories',
                'parent_menu' => null,
                'page' => $pagination['page'],
            ],
        ];
    }

    /**
     * Tombol navigasi layar daftar Telegram — SENGAJA hanya navigasi.
     *
     * Telegram hanya mengizinkan SATU `reply_markup` per pesan: reply keyboard
     * (`keyboard`) ATAU inline (`inline_keyboard`). Karena layar ini harus
     * mengirim keyboard angka (keputusan user: keyboard angka global), tombol
     * inline di sini tidak ikut terkirim bersama pesan menu — adapter
     * mengirimnya sebagai pesan kedua, dan hanya kalau menunya lebih dari satu
     * halaman.
     *
     * Susunan prev/next tetap dibuat di sini supaya satu sumber dengan
     * `appendPagination()` yang dipakai layar lain.
     *
     * @return array<int, array<int, array<string, string>>>
     */
    private function menuNavigationButtons(array $pagination): array
    {
        if ((int) ($pagination['total_pages'] ?? 1) <= 1) {
            return [];
        }

        return $this->appendPagination([], 'menu', $pagination);
    }

    /**
     * URL gambar banner untuk layar Menu Utama bot Telegram. `null` = jangan
     * kirim gambar.
     *
     * Dua alasan method ini ada, dan keduanya bukan gaya penulisan:
     *
     * 1. **Gate Telegram wajib di SINI.** `photo_url` dibaca KETIGA adapter
     *    (`TelegramAdapter`, `FonnteAdapter`, `OpenWaAdapter`), sedangkan
     *    `formatCategories()` dipakai bersama Telegram dan WhatsApp. Tanpa gate
     *    ini, banner ikut terkirim ke WhatsApp.
     *
     * 2. **Hanya kirim kalau berkasnya BENAR-BENAR ada.** Telegram menolak
     *    SELURUH pesan kalau URL gambarnya tidak bisa diambil — jadi banner yang
     *    hilang akan membuat menu user lenyap, bukan sekadar tanpa gambar.
     *    `existingUrl()` mengembalikan null untuk berkas yang tidak ada, dan
     *    menu tetap terkirim sebagai teks.
     */
    private function telegramMenuBannerUrl(?BotGatewayCapabilities $capabilities): ?string
    {
        if ($capabilities?->source() !== BotGatewayCapabilities::SOURCE_TELEGRAM) {
            return null;
        }

        $path = \App\Models\SettingWeb::query()->value('bot_menu_banner');

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return app(\App\Services\PublicUploadUrlService::class)->existingUrl($path);
    }

    public function formatProducts(
        array $data,
        int $page = 1,
        ?BotGatewayCapabilities $capabilities = null,
    ): array {
        $isTelegram = $capabilities?->source() === BotGatewayCapabilities::SOURCE_TELEGRAM;

        if (! ($data['ok'] ?? false) || empty($data['data'])) {
            return [
                'text' => $isTelegram
                    ? __('bot.catalog_products_empty')
                    : 'Kategori tidak ditemukan atau belum ada produk.',
                'buttons' => [[$this->button($isTelegram ? __('bot.btn_back') : '🔙 Kembali', 'menu')]],
            ];
        }

        $firstType = $data['data'][0]['category_type']['name'] ?? 'Produk';
        $typeSlug = (string) ($data['data'][0]['category_type']['slug'] ?? '');
        $pagination = $this->paginate(
            $data['data'],
            $page,
            $capabilities?->menuPageSize() ?? self::PAGE_SIZE,
        );

        // Layar "Pilih Game" di Telegram memakai daftar bernomor di TEKS, sama
        // seperti Menu Utama.
        //
        // Sebelumnya isinya hanya ada di tombol inline, dan tombol itu TIDAK
        // pernah terkirim: di layar ini keyboard angka yang menang, sementara
        // Telegram cuma mengizinkan satu `reply_markup` per pesan. Hasilnya user
        // melihat layar kosong berisi judul saja.
        if ($isTelegram) {
            return $this->telegramGameList($pagination, $firstType, $typeSlug);
        }

        $items = [];

        foreach ($pagination['items'] as $product) {
            $code = (string) ($product['code'] ?? '');
            $items[] = $this->button(
                $this->gameButtonLabel((string) ($product['name'] ?? 'Produk'), $code),
                'layanan ' . $code,
                'content',
            );
        }

        $buttons = array_chunk($items, 2);
        $buttons = $this->appendPagination($buttons, 'kategori ' . $typeSlug, $pagination);
        $buttons = $this->appendBack($buttons, 'menu', $capabilities?->source());

        return [
            'text' => ($isTelegram
                ? __('bot.catalog_products_title') . ' · ' . $firstType
                : '🎮 *Pilih Game* · ' . $firstType) . $this->pageSuffix($pagination),
            'buttons' => $buttons,
            'numeric_menu' => [
                'menu' => 'products',
                'parent_menu' => 'menu',
                'page' => $pagination['page'],
            ],
        ];
    }

    /**
     * Layar "Pilih Game" Telegram: daftar game bernomor di dalam teks.
     *
     * Bentuknya sengaja sama dengan Menu Utama (`telegramCategoryList`) supaya
     * cara memilih tidak berubah saat user masuk lebih dalam: nomor, bukan
     * tombol.
     */
    private function telegramGameList(array $pagination, string $firstType, string $typeSlug): array
    {
        $lines = [
            __('bot.catalog_products_title') . ' · ' . $firstType . $this->pageSuffix($pagination),
            '',
        ];
        $entries = [];
        $number = 0;

        foreach ($pagination['items'] as $product) {
            $number++;
            $name = (string) ($product['name'] ?? '') !== ''
                ? (string) $product['name']
                : __('bot.menu_category_fallback');

            $lines[] = __('bot.menu_item_numbered', [
                'number' => $number,
                'name' => $this->gameButtonLabel($name, (string) ($product['code'] ?? '')),
            ]);

            $entries[(string) $number] = [
                'type' => 'content',
                'label' => $name,
                'command' => 'layanan ' . (string) ($product['code'] ?? ''),
            ];
        }

        $entries = $this->serviceNavigationEntries($entries, $pagination, 'kategori ' . $typeSlug);
        $entries['0'] = [
            'type' => 'back',
            'label' => __('bot.btn_back'),
            'command' => 'menu',
        ];

        $lines[] = '';
        $lines[] = $this->menuListFooter($pagination);

        return [
            'text' => implode("\n", $lines),
            'buttons' => $this->serviceNavigationButtons($pagination, 'kategori ' . $typeSlug),
            'numeric_menu' => [
                'menu' => 'products',
                'parent_menu' => 'menu',
                'page' => $pagination['page'],
                'entries' => $entries,
            ],
        ];
    }

    public function formatServices(
        array $data,
        int $page = 1,
        ?BotGatewayCapabilities $capabilities = null,
        array $services = [],
    ): array {
        $isTelegram = $capabilities?->source() === BotGatewayCapabilities::SOURCE_TELEGRAM;

        // Jalur Telegram: SATU daftar rata, isinya layanan yang bisa dipesan
        // lewat bot (handler mengirim hasil `packagedServices()`). Nama paket
        // TIDAK ditampilkan: satu paket dengan 48 layanan akan mencetak nama
        // paketnya 48 kali tanpa menambah informasi apa pun.
        if ($isTelegram) {
            return $this->telegramServiceList($data, $services, $page);
        }

        if (! ($data['ok'] ?? false) || empty($data['data']['services'])) {
            return [
                'text' => 'Produk tidak ditemukan atau belum ada layanan.',
                'buttons' => [[$this->button('🔙 Kembali', 'menu')]],
            ];
        }

        $category = $data['data']['category'] ?? [];
        $productName = $category['name'] ?? 'Produk';
        $categoryCode = (string) ($category['code'] ?? '');
        $typeSlug = (string) ($category['category_type']['slug'] ?? '');
        $pagination = $this->paginate(
            $data['data']['services'],
            $page,
            $capabilities?->menuPageSize() ?? self::PAGE_SIZE,
        );

        $items = [];

        foreach ($pagination['items'] as $service) {
            $price = number_format($service['price'], 0, ',', '.');
            $items[] = $this->button(
                "💎 {$service['name']} · Rp {$price}",
                'metode ' . $service['service_id'],
                'content',
            );
        }

        $buttons = array_chunk($items, 2);
        $buttons = $this->appendPagination($buttons, 'layanan ' . $categoryCode, $pagination);
        $buttons = $this->appendBack(
            $buttons,
            $typeSlug !== '' ? 'kategori ' . $typeSlug : 'menu',
            $capabilities->source(),
        );

        return [
            'text' => '💎 *' . $productName . '*' . $this->pageSuffix($pagination),
            'buttons' => $buttons,
            'numeric_menu' => [
                'menu' => 'services',
                'parent_menu' => $typeSlug !== '' ? 'kategori ' . $typeSlug : 'menu',
                'page' => $pagination['page'],
            ],
        ];
    }

    /**
     * Layar layanan Telegram: SATU daftar rata, satu baris per layanan.
     *
     * Nama paket TIDAK ditampilkan (keputusan user). Paket tetap menentukan ISI
     * daftar — layanan yang tidak terikat paket mana pun tidak muncul di sini —
     * tapi label paketnya tidak diulang di tiap baris.
     *
     * `$services` datang dari handler hasil `GatewayCatalogService::packagedServices()`,
     * yang sudah rata dan sudah dipin (paket "spesial" di atas).
     *
     * Kategori yang tidak punya satu pun layanan berpaket TIDAK sampai ke sini:
     * penyaringnya ada di Menu Utama dan Pilih Game, jadi kategori begitu tidak
     * pernah dipajang untuk dibuka.
     */
    private function telegramServiceList(array $data, array $services, int $page): array
    {
        $category = $data['data']['category'] ?? [];
        $productName = (string) ($category['name'] ?? 'Produk');
        $categoryCode = (string) ($category['code'] ?? '');
        $typeSlug = (string) ($category['category_type']['slug'] ?? '');

        if (! ($data['ok'] ?? false) || $services === []) {
            return [
                'text' => __('bot.catalog_services_empty'),
                'buttons' => [[$this->button(__('bot.btn_back'), 'menu')]],
            ];
        }

        $pagination = $this->paginate($services, $page, self::SERVICE_LIST_PAGE_SIZE);

        $lines = [
            __('bot.service_list_title', ['produk' => $this->escapeMarkdown($productName)])
                . $this->pageSuffix($pagination),
            '',
        ];
        $entries = [];
        $number = 0;

        foreach ($pagination['items'] as $service) {
            $number++;

            // Nomor = POSISI DI HALAMAN INI (1..10), bukan posisi absolut di
            // seluruh daftar.
            //
            // Nomor absolut tampak lebih ramah ("11" lanjut dari "10"), tapi
            // BATAS keyboard angka Telegram adalah `CONTENT_ENTRY_LIMIT` (15).
            // Daftar layanan punya lebih dari 15 item, jadi halaman 3 memakai
            // nomor 21-30 yang SELURUHNYA ditolak `BotNumericMenuStore` — peta
            // tersimpan tanpa entri isi, keyboard angka kosong, dan pesan
            // navigasi ikut hilang. Akibatnya layar mati dan sentuhan angka
            // ditelan tanpa balasan.
            //
            // Dua layar lain (Menu Utama, Pilih Game) memang sudah memakai nomor
            // per-halaman; layar ini yang tadinya menyimpang.
            $lines[] = __('bot.service_item_numbered', [
                'number' => $number,
                'nama' => $this->escapeMarkdown((string) $service['name']),
                'harga' => number_format((float) $service['price'], 0, ',', '.'),
            ]);

            $entries[(string) $number] = [
                'type' => 'content',
                'label' => (string) $service['name'],
                'command' => 'metode ' . (int) $service['service_id'],
            ];
        }

        $entries = $this->serviceNavigationEntries($entries, $pagination, 'layanan ' . $categoryCode);
        $entries['0'] = [
            'type' => 'back',
            'label' => __('bot.btn_back'),
            'command' => $typeSlug !== '' ? 'kategori ' . $typeSlug : 'menu',
        ];

        $lines[] = '';
        $lines[] = __('bot.service_list_footer_items');
        $lines[] = $this->menuListFooter($pagination);

        return [
            'text' => implode("\n", $lines),
            'buttons' => $this->serviceNavigationButtons($pagination, 'layanan ' . $categoryCode),
            'numeric_menu' => [
                'menu' => 'services',
                'parent_menu' => $typeSlug !== '' ? 'kategori ' . $typeSlug : 'menu',
                'page' => $pagination['page'],
                'entries' => $entries,
            ],
        ];
    }

    public function formatPaymentMethods(
        array $data,
        int $serviceId,
        int $page = 1,
        ?string $backCallback = null,
        ?BotGatewayCapabilities $capabilities = null,
    ): array {
        $isTelegram = $capabilities?->source() === BotGatewayCapabilities::SOURCE_TELEGRAM;

        if (! ($data['ok'] ?? false) || empty($data['data'])) {
            return [
                'text' => $isTelegram
                    ? __('bot.catalog_payments_empty')
                    : 'Metode pembayaran sedang tidak tersedia.',
                'buttons' => $backCallback
                    ? [[$this->button($isTelegram ? __('bot.btn_back') : '🔙 Kembali', $backCallback)]]
                    : [],
            ];
        }

        $pagination = $this->paginate(
            $data['data'],
            $page,
            $capabilities?->menuPageSize() ?? self::PAGE_SIZE,
        );
        $items = [];

        foreach ($pagination['items'] as $method) {
            $items[] = $this->button(
                '💳 ' . $method['name'],
                "harga {$serviceId} {$method['code']}",
                'content',
            );
        }

        $buttons = array_chunk($items, 2);
        $buttons = $this->appendPagination($buttons, 'metode ' . $serviceId, $pagination);

        if ($backCallback) {
            $buttons = $this->appendBack($buttons, $backCallback, $capabilities?->source());
        }

        return [
            'text' => '💳 *Pilih Pembayaran*' . $this->pageSuffix($pagination),
            'buttons' => $buttons,
            'numeric_menu' => [
                'menu' => 'payments',
                'parent_menu' => $backCallback,
                'page' => $pagination['page'],
            ],
        ];
    }

    /**
     * @param string|null $source Sumber gateway (`telegram_gateway` /
     *   `whatsapp_gateway`). Dipakai untuk memilih teks dari file lang: fase
     *   ini sengaja memindahkan copy Telegram saja, WhatsApp tetap literal
     *   Indonesia supaya perilakunya tidak berubah sama sekali.
     */
    public function formatPriceQuote(
        array $data,
        bool $isConversationalCheckout = false,
        ?string $source = null,
    ): array {
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;

        if (! ($data['ok'] ?? false)) {
            return [
                'text' => "Gagal cek harga: " . ($data['message'] ?? 'Tidak diketahui'),
                'buttons' => [],
            ];
        }

        $d = $data['data'];
        $base = number_format($d['base_amount'], 0, ',', '.');
        $feeAmount = (int) ($d['payment_fee'] ?? 0) + (int) ($d['gateway_fee'] ?? 0);
        $fee = number_format($feeAmount, 0, ',', '.');
        $total = number_format($d['total_amount'], 0, ',', '.');
        $discount = number_format($d['discount'], 0, ',', '.');
        $backCallback = 'layanan ' . ($d['category_code'] ?? '');

        $lines = $isTelegram ? [
            __('bot.checkout_title'),
            '',
            '💎 ' . $this->escapeMarkdown((string) $d['service_name']),
            '👤 ' . $this->escapeMarkdown((string) ($d['category_name'] ?? '')),
            '💳 ' . $this->escapeMarkdown((string) ($d['payment_method']['name'] ?? __('bot.checkout_default_payment'))),
            '',
            __('bot.checkout_price', ['amount' => $base]),
        ] : [
            '🧾 *Cek Pesanan*',
            '',
            '💎 ' . $this->escapeMarkdown((string) $d['service_name']),
            '👤 ' . $this->escapeMarkdown((string) ($d['category_name'] ?? '')),
            '💳 ' . $this->escapeMarkdown((string) ($d['payment_method']['name'] ?? 'Pembayaran')),
            '',
            'Harga       Rp ' . $base,
        ];

        if ($d['discount'] > 0) {
            $lines[] = $isTelegram
                ? __('bot.checkout_discount', ['amount' => $discount])
                : 'Diskon      -Rp ' . $discount;
        }

        $lines[] = $isTelegram
            ? __('bot.checkout_admin_fee', ['amount' => $fee])
            : 'Admin       Rp ' . $fee;
        $lines[] = '──────────────';
        $lines[] = $isTelegram
            ? __('bot.checkout_total', ['amount' => $total])
            : '*Total      Rp ' . $total . '*';

        if ($isConversationalCheckout) {
            $lines[] = '';
            $lines = [...$lines, ...$this->conversationalInputLines(
                (bool) ($d['requires_zone_id'] ?? false),
                $d['custom_inputs'] ?? [],
                $isTelegram,
            )];
        } else {
            $lines[] = '';
            $lines[] = $isTelegram
                ? __('bot.checkout_send_command', ['service' => $d['service_id'], 'method' => $d['payment_method']['code']])
                : 'Kirim: `invoice ' . $d['service_id'] . ' ' . $d['payment_method']['code'] . ' <UID> [Zone_ID]`';
            $lines[] = $isTelegram
                ? __('bot.checkout_example_command', ['service' => $d['service_id'], 'method' => $d['payment_method']['code']])
                : 'Contoh: `invoice ' . $d['service_id'] . ' ' . $d['payment_method']['code'] . ' 1234567 1234`';
        }

        return [
            'text' => implode("\n", $lines),
            'buttons' => $isConversationalCheckout
                ? [[
                    $this->button($isTelegram ? __('bot.checkout_btn_cancel') : '❌ Batal', 'batal'),
                    $this->button($isTelegram ? __('bot.checkout_btn_back') : '🔙 Kembali', $backCallback),
                ]]
                : [[$this->button($isTelegram ? __('bot.checkout_btn_back') : '🔙 Kembali', $backCallback)]],
        ];
    }

    /**
     * @param string|null $source Lihat catatan di `formatPriceQuote()`.
     */
    public function formatCheckoutConfirmation(
        array $quote,
        array $payload,
        string $token,
        ?string $source = null,
    ): array {
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;
        $data = is_array($quote['data'] ?? null)
            ? $quote['data']
            : $quote;
        $uid = $this->maskedTarget(
            (string) ($payload['uid'] ?? ''),
        );
        $zone = trim((string) ($payload['zone'] ?? ''));
        $target = $zone !== '' ? $uid . ' / ' . $zone : $uid;
        $inputLabel = trim((string) ($payload['input_label'] ?? 'UID')) ?: 'UID';
        $nickname = trim((string) ($payload['nickname'] ?? ''));
        $serviceName = $this->escapeMarkdown(
            (string) ($data['service_name'] ?? 'Produk'),
        );
        $methodName = $this->escapeMarkdown(
            (string) data_get(
                $data,
                'payment_method.name',
                $isTelegram ? __('bot.checkout_default_payment') : 'Pembayaran',
            ),
        );
        $total = number_format(
            (int) ($data['total_amount'] ?? 0),
            0,
            ',',
            '.',
        );
        $confirmCommand = 'konfirmasi ' . $token;
        $cancelCommand = 'batal ' . $token;

        return [
            'text' => implode("\n", [
                $isTelegram ? __('bot.checkout_title') : '🧾 *Cek Pesanan*',
                '',
                '💎 ' . $serviceName,
                '👤 ' . $this->escapeMarkdown($inputLabel) . ': `' . $this->escapeMarkdownCode($target) . '`',
                ...($nickname !== '' ? ['🏷️ Nickname: ' . $this->escapeMarkdown($nickname)] : []),
                '💳 ' . $methodName,
                '',
                $isTelegram
                    ? __('bot.checkout_total', ['amount' => $total])
                    : '*Total      Rp ' . $total . '*',
                '',
                $isTelegram ? __('bot.checkout_confirm_expiry') : 'Konfirmasi berlaku 15 menit.',
            ]),
            'buttons' => [
                [
                    $this->button($isTelegram ? __('bot.checkout_btn_confirm') : '✅ Konfirmasi', $confirmCommand, 'content'),
                    $this->button($isTelegram ? __('bot.checkout_btn_cancel') : '❌ Batal', $cancelCommand, 'content'),
                ],
            ],
            'numeric_menu' => [
                'menu' => 'checkout_confirmation',
                'parent_menu' => 'menu',
            ],
        ];
    }

    /**
     * @param string|null $source Lihat catatan di `formatPriceQuote()`.
     */
    public function formatCheckoutInputRetry(
        bool $requiresZoneId,
        array $customInputs,
        string $backCallback,
        ?string $source = null,
    ): array {
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;

        return [
            'text' => implode("\n", [
                $isTelegram ? __('bot.checkout_invalid_format') : 'Format ID belum sesuai.',
                '',
                ...$this->conversationalInputLines($requiresZoneId, $customInputs, $isTelegram),
            ]),
            'buttons' => [[
                $this->button($isTelegram ? __('bot.checkout_btn_cancel') : '❌ Batal', 'batal'),
                $this->button($isTelegram ? __('bot.checkout_btn_back') : '🔙 Kembali', $backCallback),
            ]],
        ];
    }

    /**
     * @param string|null $source Lihat catatan di `formatPriceQuote()`.
     */
    public function formatCheckId(array $data, ?string $source = null): array
    {
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;

        if (! ($data['ok'] ?? false)) {
            return [
                'text' => ($data['error_code'] ?? '') === 'CHECK_ID_UNAVAILABLE'
                    ? ($isTelegram
                        ? __('bot.checkid_unavailable')
                        : 'Validasi ID sedang tidak tersedia. Coba lagi beberapa saat.')
                    : ($isTelegram
                        ? __('bot.checkid_invalid', ['message' => $data['message'] ?? 'User ID tidak ditemukan atau tidak valid.'])
                        : "ID tidak valid: " . ($data['message'] ?? 'User ID tidak ditemukan atau tidak valid.')),
                'buttons' => [],
            ];
        }

        if ($data['data']['skip_check']) {
            return [
                'text' => $isTelegram
                    ? __('bot.checkid_skip')
                    : "Produk ini tidak memerlukan validasi ID.",
                'buttons' => [],
            ];
        }

        $validTitle = $isTelegram ? __('bot.checkid_valid_title') : '✅ *ID Valid*';

        return [
            'text' => "{$validTitle}\n👤 Nickname: {$data['data']['nickname']}",
            'buttons' => [],
        ];
    }

    public function formatInvoice(array $data, string $source = 'telegram_gateway'): array
    {
        // Jalur Telegram memakai lang (bahasa aktif). Default param adalah
        // telegram_gateway karena invoice memang dikirim ke Telegram; WhatsApp
        // tetap literal Indonesia agar perilakunya tidak berubah.
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;

        if (! ($data['ok'] ?? false)) {
            // Pesan mentah dari provider TIDAK diterjemahkan — hanya
            // pembungkusnya. Alasan teknisnya tetap harus terbaca admin.
            $reason = (string) ($data['message'] ?? 'Error internal');

            return [
                'text' => $isTelegram
                    ? __('bot.invoice_create_failed', ['reason' => $reason])
                    : 'Gagal membuat invoice: ' . $reason,
                'buttons' => [],
            ];
        }

        $orderId = (string) $data['data']['order_id'];
        $paymentCode = trim((string) ($data['data']['payment']['payment_code'] ?? ''));
        $qrPayload = trim((string) data_get($data, 'data.payment.qr_payload', ''));
        $amount = number_format($data['data']['payment']['amount'] ?? 0, 0, ',', '.');
        $serviceName = trim((string) ($data['data']['service_name'] ?? '')) ?: 'Produk';
        $categoryName = trim((string) ($data['data']['category_name'] ?? '')) ?: 'Kategori';
        $quantity = max(1, (int) ($data['data']['quantity'] ?? 1));
        $invoiceUrl = filter_var($data['data']['invoice_url'] ?? $data['data']['payment_url'] ?? null, FILTER_VALIDATE_URL)
            ? (string) ($data['data']['invoice_url'] ?? $data['data']['payment_url'])
            : null;
        $photoUrl = $this->invoicePhotoUrl($data['data'], $paymentCode);
        $isQrPayment = $photoUrl !== null || $this->isQrisPayload($paymentCode) || $this->isQrisPayload($qrPayload);
        $lines = [
            $isTelegram ? __('bot.invoice_pending_title') : '⏳ *Menunggu Pembayaran*',
            '',
            '💎 ' . $this->escapeMarkdown($serviceName . ' (' . $categoryName . ')'),
            '💰 *Rp ' . $amount . '*',
            '🧾 `' . $this->escapeMarkdownCode($orderId) . '`',
        ];

        if (! $isQrPayment && $paymentCode !== '') {
            $lines[] = '';
            $lines[] = $isTelegram
                ? __('bot.invoice_va_line', ['code' => $this->escapeMarkdownCode($paymentCode)])
                : '💳 Kode Bayar / VA: `' . $this->escapeMarkdownCode($paymentCode) . '`';
        }

        $lines[] = '';
        $lines[] = $isQrPayment
            ? ($isTelegram ? __('bot.invoice_qr_hint') : 'Scan QRIS untuk membayar.')
            : ($isTelegram ? __('bot.invoice_pay_hint') : 'Selesaikan pembayaran agar pesanan diproses otomatis.');
        $lines[] = $isTelegram ? __('bot.invoice_status_hint') : 'Ketik `status` untuk cek pembayaran.';
        $buttons = [];

        if ($invoiceUrl !== null && $source !== 'whatsapp_gateway') {
            $buttons[] = [$this->urlButton(
                $isTelegram ? __('bot.invoice_btn_open') : '🔗 Buka Halaman Invoice',
                $invoiceUrl,
            )];
        }

        $buttons[] = [$this->button(
            $isTelegram ? __('bot.invoice_btn_check') : '🔎 Cek Status Pembayaran',
            "status {$orderId}",
        )];
        $response = [
            'text' => implode("\n", $lines),
            'buttons' => $buttons,
        ];

        if ($photoUrl !== null) {
            $response['photo_url'] = $photoUrl;
        }

        return $response;
    }

    /**
     * Non-Telegram (WhatsApp + listener notifikasi) tetap literal Indonesia:
     * notifikasi transaksi tidak boleh berganti bahasa karena tebakan
     * channel, jadi default `null` = perilaku lama persis.
     */
    public function formatStatus(array $data, ?string $source = null): array
    {
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;

        if (! ($data['ok'] ?? false)) {
            return [
                'text' => $isTelegram
                    ? __('bot.status_check_failed', [
                        'message' => $data['message'] ?? __('bot.status_invoice_missing'),
                    ])
                    : "Gagal cek status: " . ($data['message'] ?? 'Invoice tidak ditemukan'),
                'buttons' => [],
            ];
        }

        $d = $data['data'];
        $paymentStatus = strtolower(trim((string) data_get($d, 'payment.status')));

        if ($paymentStatus === 'lunas') {
            $orderId = $this->escapeMarkdown((string) ($d['order_id'] ?? ''));
            $product = $this->escapeMarkdown((string) ($d['product'] ?? 'Produk'));
            $nickname = $this->escapeMarkdown((string) ($d['nickname'] ?? ''));
            $sn = trim((string) ($d['sn'] ?? ''));
            $orderStatus = strtolower(trim((string) ($d['status'] ?? '')));
            $isComplete = in_array($orderStatus, ['sukses', 'success', 'berhasil', 'selesai', 'completed', 'delivered'], true);
            // Order Gagal + pembayaran lunas itu NYATA dan sebelumnya salah
            // disajikan: `$isComplete` false → jatuh ke cabang "Pembayaran
            // Berhasil / sedang diproses", sehingga user diberi tahu pesanannya
            // masih jalan padahal provider sudah menyatakan gagal. Dana sudah
            // masuk di kasus ini, jadi salah informasi ini yang paling mahal.
            $isFailed = in_array($orderStatus, ['gagal', 'failed', 'batal', 'cancelled', 'canceled'], true);

            $lines = [
                $isComplete
                    ? ($isTelegram ? __('bot.status_complete_title') : '✅ *Top Up Berhasil!*')
                    : ($isFailed
                        ? ($isTelegram ? __('bot.status_failed_title') : '❌ *Order Gagal*')
                        : ($isTelegram ? __('bot.status_paid_title') : '✅ *Pembayaran Berhasil*')),
                '',
            ];

            if ($isComplete) {
                $lines[] = $isTelegram
                    ? __('bot.status_complete_body')
                    : 'Pesanan sudah berhasil diproses dan masuk ke akun kamu 🎉';
            } elseif ($isFailed) {
                // JANGAN menulis "sedang diproses" di sini: order sudah gagal.
                // Dan jangan pula menyuruh "coba lagi" tanpa menyebut dana —
                // pembayarannya sudah lunas, jadi refund itu bagian dari kabar.
                $lines[] = $isTelegram
                    ? __('bot.status_failed_body')
                    : 'Pembayaran kamu sudah diterima, tapi pesanan *tidak berhasil diproses* oleh penyedia layanan.';
                $lines[] = '';
                $lines[] = $isTelegram
                    ? __('bot.status_failed_note')
                    : 'Dana kamu akan dikembalikan. Hubungi admin kalau dalam 1x24 jam belum diterima.';
            } else {
                $lines[] = $isTelegram
                    ? __('bot.status_paid_body')
                    : 'Pesanan kamu sudah diterima dan sedang diproses.';
                $lines[] = '';
                $lines[] = $isTelegram
                    ? __('bot.status_paid_note')
                    : 'Kami akan mengirimkan notifikasi setelah top up selesai.';
            }

            $lines[] = '';
            $lines[] = '💎 ' . $product . ($nickname !== '' ? "\n👤 " . $nickname : '');

            if ($sn !== '') {
                $lines[] = '🔑 SN: `' . $this->escapeMarkdownCode($sn) . '`';
            }

            $lines[] = '';
            $lines[] = '🧾 `' . $this->escapeMarkdownCode((string) ($d['order_id'] ?? '')) . '`';

            // Order gagal tidak boleh ditutup dengan ajakan belanja lagi.
            if ($isComplete) {
                $storeName = trim((string) config('app.name', 'Store')) ?: 'Store';
                $lines[] = '';
                $lines[] = $isTelegram
                    ? __('bot.status_thanks', ['store' => $this->escapeMarkdown($storeName)])
                    : 'Terima kasih sudah berbelanja di *' . $this->escapeMarkdown($storeName) . '*.';
                $lines[] = $isTelegram
                    ? __('bot.status_more')
                    : 'Butuh produk lain? Cek katalog kami kapan saja.';
            }

            return [
                'text' => implode("\n", $lines),
                'buttons' => [
                    [
                        $this->button($isTelegram ? __('bot.btn_back_menu') : '🔙 Kembali ke Menu', 'menu'),
                    ],
                ],
            ];
        }

        if ($paymentStatus === 'belum lunas') {
            return $this->formatUnpaidStatus($d, $source);
        }

        if (in_array($paymentStatus, ['expired', 'kadaluarsa'], true)) {
            return $this->formatExpiredStatus($d, $source);
        }

        $amount = number_format($d['amount'], 0, ',', '.');
        $lines = $isTelegram ? [
            __('bot.status_generic_title'),
            __('bot.status_generic_order_id', ['order_id' => $d['order_id']]),
            __('bot.status_generic_product', ['product' => $d['product'], 'nickname' => $d['nickname']]),
            __('bot.status_generic_total', ['amount' => $amount]),
            __('bot.status_generic_payment', ['status' => $d['payment']['status']]),
            __('bot.status_generic_order', ['status' => $d['status']]),
        ] : [
            "*Status Pesanan*",
            "Order ID: {$d['order_id']}",
            "Produk: {$d['product']} ({$d['nickname']})",
            "Total: Rp {$amount}",
            "Status Pembayaran: *{$d['payment']['status']}*",
            "Status Pesanan: *{$d['status']}*",
        ];

        if ($d['sn']) {
            $lines[] = $isTelegram
                ? "\n" . __('bot.status_generic_sn') . " \n" . $d['sn']
                : "\n*SN / Keterangan:* \n{$d['sn']}";
        }

        return [
            'text' => implode("\n", $lines),
            'buttons' => [
                [
                    $this->button($isTelegram ? __('bot.btn_back_menu') : '🔙 Kembali ke Menu', 'menu'),
                ],
            ]
        ];
    }

    /**
     * Daftar SEMUA transaksi milik sender (semua status), terpaginasi.
     * Nomor pada daftar bisa diklik untuk membuka detail order tersebut
     * (`status <order_id>`), jadi user tidak perlu menyalin order ID.
     *
     * @param iterable<int, array<string, mixed>> $orders
     * @return array{text: string, buttons: array}
     */
    public function formatSenderOrderList(
        iterable $orders,
        int $page = 1,
        int $totalPages = 1,
        int $total = 0,
        int $perPage = 5,
        ?string $source = null,
    ): array {
        // Non-Telegram (WhatsApp) tetap literal Indonesia — keputusan fase:
        // scope terjemahan Telegram saja, dan copy WA wajib identik.
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;

        $lines = [
            $isTelegram ? __('bot.sender_list_title') : '📦 *Transaksi Kamu*',
            '',
        ];
        $buttons = [];
        $row = [];
        $number = (($page - 1) * max(1, $perPage)) + 1;

        foreach ($orders as $order) {
            $orderId = (string) ($order['order_id'] ?? '');
            $orderStatus = strtolower(trim((string) ($order['order_status'] ?? '')));
            $paymentStatus = strtolower(trim((string) ($order['payment_status'] ?? '')));

            $paymentLabel = match (true) {
                in_array($paymentStatus, ['lunas', 'paid', 'success'], true) =>
                    $isTelegram ? __('bot.label_paid') : 'Lunas',
                in_array($paymentStatus, ['expired', 'kadaluarsa'], true) =>
                    $isTelegram ? __('bot.label_expired') : 'Expired',
                default => $isTelegram ? __('bot.label_unpaid') : 'Belum Bayar',
            };

            $orderLabel = match (true) {
                in_array($orderStatus, ['sukses', 'success', 'berhasil', 'selesai', 'completed', 'delivered'], true) =>
                    $isTelegram ? __('bot.label_success') : 'Sukses',
                in_array($orderStatus, ['gagal', 'failed'], true) =>
                    $isTelegram ? __('bot.label_failed') : 'Gagal',
                in_array($orderStatus, ['expired', 'kadaluarsa', 'batal', 'canceled', 'cancelled'], true) =>
                    $isTelegram ? __('bot.label_expired') : 'Expired',
                default => $isTelegram ? __('bot.label_processing') : 'Diproses',
            };

            $lines[] = $number . '. `' . $this->escapeMarkdownCode($orderId) . '`';
            $lines[] = '   💎 ' . $this->escapeMarkdown(
                (string) ($order['product'] ?? ($isTelegram ? __('bot.sender_list_product_fallback') : 'Produk')),
            )
                . ' · ' . $paymentLabel . ' · ' . $orderLabel;
            $lines[] = '   💰 Rp ' . number_format((int) ($order['amount'] ?? 0), 0, ',', '.');

            // Tombol nomor = buka detail order tsb. Telegram membatasi
            // callback_data 64 byte; order_id gateway biasanya ~21-24
            // karakter. Bila kebetulan lebih panjang, tombol dilewati
            // (user masih bisa mengetik `status <invoice>`).
            $callback = 'status ' . $orderId;
            if (strlen($callback) <= 64) {
                $row[] = $this->button((string) $number, $callback, 'status_detail');
                if (count($row) === 5) {
                    $buttons[] = $row;
                    $row = [];
                }
            }

            $number++;
        }

        if ($row !== []) {
            $buttons[] = $row;
        }

        if ($total > 0) {
            $lines[] = '';
            $lines[] = $isTelegram
                ? __('bot.sender_list_pagination', [
                    'page' => $page,
                    'pages' => $totalPages,
                    'total' => $total,
                ])
                : 'Menampilkan halaman ' . $page . ' dari ' . $totalPages
                    . ' · total ' . $total . ' transaksi.';
        }

        $lines[] = $isTelegram
            ? __('bot.sender_list_hint')
            : 'Ketik `status <invoice>` untuk detail, atau tekan nomornya.';

        if ($totalPages > 1) {
            $row = [];
            if ($page > 1) {
                $row[] = $this->button(
                    $isTelegram ? __('bot.btn_prev') : '⬅️ Sebelumnya',
                    'status page:' . ($page - 1),
                    'navigation_previous',
                );
            }
            if ($page < $totalPages) {
                $row[] = $this->button(
                    $isTelegram ? __('bot.btn_next') : 'Berikutnya ➡️',
                    'status page:' . ($page + 1),
                    'navigation_next',
                );
            }
            if ($row !== []) {
                $buttons[] = $row;
            }
        }

        $buttons[] = [$this->button($isTelegram ? __('bot.btn_back_menu') : '🔙 Kembali ke Menu', 'menu')];

        return [
            'text' => implode("\n", $lines),
            'buttons' => $buttons,
        ];
    }

    public function formatActiveOrders(
        iterable $orders,
        ?string $title = null,
        ?string $source = null,
    ): array {
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;
        $lines = [
            $title ?? ($isTelegram ? __('bot.active_orders_title') : '📦 *Pesanan Aktif*'),
            '',
        ];
        $number = 1;

        foreach ($orders as $order) {
            $paymentStatus = strtolower(trim((string) ($order['payment_status'] ?? '')));
            $orderStatus = strtolower(trim((string) ($order['order_status'] ?? '')));

            $paymentLabel = match (true) {
                in_array($paymentStatus, ['lunas', 'paid', 'success'], true) =>
                    $isTelegram ? __('bot.label_paid') : 'Lunas',
                in_array($paymentStatus, ['expired', 'kadaluarsa'], true) =>
                    $isTelegram ? __('bot.label_expired') : 'Expired',
                default => $isTelegram ? __('bot.label_awaiting_payment') : 'Menunggu Pembayaran',
            };

            // Daftar recent memuat semua status, jadi label harus
            // menggambarkan status asli — bukan selalu "Diproses".
            $orderLabel = match (true) {
                in_array($orderStatus, ['sukses', 'success', 'berhasil', 'selesai', 'completed', 'delivered'], true) =>
                    $isTelegram ? __('bot.label_success') : 'Sukses',
                in_array($orderStatus, ['gagal', 'failed'], true) =>
                    $isTelegram ? __('bot.label_failed') : 'Gagal',
                in_array($orderStatus, ['expired', 'kadaluarsa', 'batal', 'canceled', 'cancelled'], true) =>
                    $isTelegram ? __('bot.label_expired') : 'Expired',
                default => $isTelegram ? __('bot.label_processing') : 'Diproses',
            };

            $lines[] = $number . '. `' . $this->escapeMarkdownCode((string) ($order['order_id'] ?? '')) . '`';
            $lines[] = '   💎 ' . $this->escapeMarkdown(
                (string) ($order['product'] ?? ($isTelegram ? __('bot.sender_list_product_fallback') : 'Produk')),
            )
                . ' · ' . $paymentLabel . ' · ' . $orderLabel;
            $number++;
        }

        $lines[] = '';
        $lines[] = $isTelegram
            ? __('bot.active_orders_hint')
            : 'Ketik `status <invoice>` untuk detail.';

        return [
            'text' => implode("\n", $lines),
            'buttons' => [
                [
                    $this->button($isTelegram ? __('bot.btn_back_menu') : '🔙 Kembali ke Menu', 'menu'),
                ],
            ],
        ];
    }

    /**
     * Tombol pemilih bahasa — SATU sumber label untuk panel `/bahasa` DAN untuk
     * keyboard tetap Telegram.
     *
     * Kenapa harus satu sumber: label ini dikirim BALIK sebagai teks saat user
     * menekannya, jadi tiap label wajib dikenali `BotCommandParser`. Kalau panel
     * dan keyboard menyusunnya sendiri-sendiri, cepat atau lambat salah satunya
     * menyimpang dan tombolnya mati.
     *
     * `Bahasa` (ID) / `Indonesian` (EN) sengaja BUKAN `🇮🇩 Bahasa Indonesia` di
     * kedua bahasa: `Bahasa Indonesia` tanpa emoji bukan label yang dikenal
     * parser (pencocokan exact), jadi tombol itu akan jadi tombol mati.
     *
     * Nama tombolnya bergantung pada bahasa yang SEDANG AKTIF: user berbahasa
     * Indonesia melihat `🇮🇩 Bahasa` / `🇬🇧 English`, user berbahasa Inggris
     * melihat `🇮🇩 Indonesian` / `🇬🇧 English`. Keduanya tetap mengirim TEKS yang
     * sama (`bahasa id` tidak pernah berubah), jadi perpindahan bahasa tidak
     * pernah bergantung pada nama tombol yang terlihat.
     *
     * @return array{0: array{text: string, callback: string}, 1: array{text: string, callback: string}}
     */
    private function languageButtons(?string $currentLocale = null): array
    {
        $currentLocale ??= app()->getLocale();

        $byLocale = [
            'id' => [$this->button('🇮🇩 Bahasa', 'bahasa id'), $this->button('🇬🇧 English', 'bahasa en')],
            'en' => [$this->button('🇮🇩 Indonesian', 'bahasa id'), $this->button('🇬🇧 English', 'bahasa en')],
        ];

        $labels = $byLocale[$this->normalizeLocale($currentLocale)] ?? $byLocale['id'];

        // Jaring pengaman: jangan pernah mengirim tombol yang tidak dikenali
        // parser. Lebih baik jatuh ke pasangan default daripada mengirim tombol
        // mati yang membuat user bingung karena tap-nya tidak dijawab.
        foreach ($labels as $button) {
            if (! BotCommandParser::anyLabel((string) $button['text'])) {
                return $byLocale['id'];
            }
        }

        return $labels;
    }

    /**
     * Label tombol keyboard tetap, sesuai locale AKTIF.
     *
     * Satu sumber untuk `defaultReplyKeyboard()` DAN copy yang menyebut nama
     * tombol (`formatHelp`, `formatTelegramMembershipVerified`). Kalau label
     * dibaca dari tempat berbeda, copy bisa menyuruh user menekan tombol yang
     * tidak ada di layarnya — persis keluhan yang pernah terjadi.
     *
     * Label netral (leaderboard/deposit) sengaja literal di pemanggilnya: sama
     * di kedua bahasa, jadi menduplikasinya hanya menambah nilai kembar.
     */
    private function keyboardLabel(string $key, string $fallback): string
    {
        $label = __($key);

        // `__()` mengembalikan kunci mentah kalau terjemahannya hilang — itu
        // tampil sebagai `bot.kbd_menu` di chat. Teks apa pun lebih baik.
        return str_starts_with($label, 'bot.') ? $fallback : $label;
    }

    /**
     * Placeholder untuk copy yang MENYEBUT nama tombol (`:menu`, `:status`, …).
     *
     * Dipakai supaya nama tombol di dalam kalimat selalu mengikuti label yang
     * benar-benar dirender keyboard di bahasa aktif — diterjemahkan sekali, di
     * satu tempat.
     *
     * @return array<string, string>
     */
    private function buttonNamePlaceholders(): array
    {
        return [
            'menu' => $this->keyboardLabel('bot.kbd_menu', '🛍️ Buka Menu'),
            'status' => $this->keyboardLabel('bot.kbd_status', '📦 Cek Status'),
            'cekid' => $this->keyboardLabel('bot.kbd_cekid', '🔍 Cek ID Game'),
            'help' => $this->keyboardLabel('bot.kbd_help', '❓ Bantuan'),
            'cancel' => $this->keyboardLabel('bot.kbd_cancel', '❌ Batal Transaksi'),
            'history' => $this->keyboardLabel('bot.kbd_history', '📜 Riwayat Order'),
            'deposit' => '💰 Deposit',
            'id' => $this->languageButtonLabels()['id'],
            'en' => $this->languageButtonLabels()['en'],
            'gate_verified' => $this->keyboardLabel('bot.kbd_gate_verified', '✅ Sudah Bergabung'),
        ];
    }

    /**
     * Label tombol bahasa untuk locale tertentu.
     *
     * Publik karena `TelegramWelcomeService` juga memakainya: sambutan grup
     * menyebut tombol ganti bahasa, dan nama itu harus persis sama dengan yang
     * dirender panel/keyboard — kalau menyimpang, petunjuknya menyesatkan.
     *
     * @return array{id: string, en: string}
     */
    public function languageButtonLabels(?string $locale = null): array
    {
        $buttons = $this->languageButtons($locale);

        return [
            'id' => (string) ($buttons[0]['text'] ?? '🇮🇩 Bahasa'),
            'en' => (string) ($buttons[1]['text'] ?? '🇬🇧 English'),
        ];
    }

    /** Locale yang dikenal formatter; di luar itu → 'id'. */
    private function normalizeLocale(?string $locale): string
    {
        return in_array($locale, BotLocale::SUPPORTED, true) ? (string) $locale : 'id';
    }

    /**
     * Panel pemilih bahasa (dipakai `/bahasa` dan saat bahasa benar-benar ganti).
     *
     * @param  array<int, string>  $locales
     * @return array{text: string, buttons: array<int, array<int, array>>}
     */
    public function languagePanel(string $current, bool $withPicker = true, ?string $note = null): array
    {
        $lines = [
            __('bot.lang_title'),
            '',
            __('bot.lang_current', ['label' => $this->languageLabel($current)]),
        ];

        if ($note !== null && $note !== '') {
            $lines[] = '';
            $lines[] = $note;
        }

        $lines[] = '';
        $lines[] = __('bot.lang_pick');

        $rows = [];

        if ($withPicker) {
            // Satu baris: [🇮🇩 Bahasa] [🇬🇧 English]. Ini juga yang dipakai
            // keyboard tetap, supaya nama tombolnya tidak pernah menyimpang.
            $rows[] = $this->languageButtons($current);
        }

        $rows[] = [$this->button(__('bot.btn_back_menu'), 'menu')];

        return [
            'text' => implode("\n", $lines),
            'buttons' => $rows,
        ];
    }

    /** Nama bahasa yang ditampilkan ke user. Bukan label tombol. */
    private function languageLabel(string $locale): string
    {
        return $locale === 'en' ? 'English' : 'Bahasa Indonesia';
    }
    public function formatHelp(?BotGatewayCapabilities $capabilities = null): array
    {
        $capabilities ??= BotGatewayCapabilities::forSource(null);
        // Telegram punya garis bawah (`__teks__`), WhatsApp tidak — di sana
        // penanda itu justru tampil mentah bersama garis bawahnya. Jadi
        // penekanan judul memakai garis bawah hanya di Telegram, dan jatuh ke
        // tebal di channel lain supaya tetap terlihat menonjol.
        $isTelegram = $capabilities->source() === BotGatewayCapabilities::SOURCE_TELEGRAM;
        $em = static fn (string $text): string => $isTelegram ? "__{$text}__" : "*{$text}*";

        // Label tombol diambil dari SUMBER YANG SAMA dengan keyboard tetap
        // (`keyboardLabel()`). Dulu di sini tertulis literal, dan pernah
        // menyimpang dari keyboard → user melihat dua nama untuk tombol yang
        // sama.
        $names = $this->buttonNamePlaceholders();
        $buttons = [[$this->button($names['menu'], 'menu')]];

        // Tombol bahasa di panduan — kompensasi WAJIB dari auto-deteksi. Ini
        // yang bikin user yang salah-terdeteksi punya jalan keluar 1 tap tanpa
        // harus tahu perintah `/bahasa` ada.
        if ($isTelegram) {
            $buttons[] = $this->languageButtons();
        }

        if ($capabilities->supports('leaderboard')) {
            $buttons[] = [$this->button('🏆 Leaderboard', 'leaderboard')];
        }
        if ($capabilities->supports('order_history')) {
            $buttons[] = [$this->button($names['history'], 'order_history')];
        }
        if ($capabilities->supports('deposit')) {
            $buttons[] = [$this->button($names['deposit'], 'deposit')];
        }

        $adminUrl = trim((string) config('services.telegram-bot-api.admin_contact_url', ''));

        // Langkah-langkah sengaja memakai kata umum ("layanan", "detail
        // kontak"), bukan "game" / "ID akun game": katalog toko mencakup
        // produk game maupun layanan aplikasi premium.
        // Ayat panduan diambil dari file lang HANYA untuk Telegram. WhatsApp
        // tetap memakai literal Indonesia di bawah: fase ini mengunci perilaku
        // WhatsApp supaya tidak berubah sama sekali. Kalau nanti WhatsApp ikut
        // diterjemahkan, itu keputusan terpisah — bukan efek samping.
        // Judul di-`$em()` setelah diterjemahkan agar penekanan tetap
        // channel-specific (`__` di Telegram, `*` di channel lain).
        if ($isTelegram) {
            $lines = [
                $em(__('bot.help_title')),
                '',
                $em(__('bot.help_order_title')),
                // Nama tombol di dalam kalimat diisi dari `kbd_*` (locale aktif).
                // Kalau ditulis literal, user berbahasa Inggris disuruh menekan
                // tombol yang tulisannya berbeda dari yang ada di layarnya.
                __('bot.help_order_step_1', $names),
                __('bot.help_order_step_2'),
                __('bot.help_order_step_3'),
                __('bot.help_order_step_4'),
                '',
                $em(__('bot.help_manage_title')),
                __('bot.help_manage_status', $names),
                __('bot.help_manage_history', $names),
            ];
            // Baris "🔍 Cek ID Game" DICABUT dari panduan (keputusan user), sama
            // seperti baris batal: tombolnya sudah tidak ada di keyboard, jadi
            // panduan tidak boleh mengarahkan user ke tombol yang tidak terlihat.
            // Perintah `cekid` tetap bisa diketik dan tetap didokumentasikan di
            // balasan perintah itu sendiri (`handleCekId()`).
            // Baris "❌ Batal Transaksi" DICABUT dari panduan (keputusan user).
            //
            // Tombolnya sudah tidak dirender di keyboard, jadi menyebutkannya di
            // panduan mengarahkan user menekan tombol yang tidak ada di
            // layarnya. Perintah `batal` sendiri tetap hidup (perintah ketik +
            // tombol inline di layar konfirmasi checkout), jadi mencabut baris
            // ini tidak mematikan fiturnya — hanya berhenti mempromosikannya.
            //
            // Kunci lang `bot.help_manage_cancel` SENGAJA tidak dihapus (nol
            // penghapusan di `resources/lang/`), jadi kuncinya kini tidak dipakai
            // di jalur Telegram mana pun.

            if ($capabilities->supports('deposit')) {
                // Ditaruh di dalam daftar supaya urutannya ikut alur, bukan
                // menggantung di bawah.
                $lines[] = __('bot.help_manage_deposit', $names);
            }

            // Bahasa ikut terdaftar di panduan supaya user tahu jalan keluarnya
            // TANPA harus menebak `/bahasa`. Ini kompensasi wajib dari
            // auto-deteksi: kalau tebakan bahasa perangkat salah, user harus
            // bisa menemukan penggantinya.
            $lines[] = __('bot.help_manage_language');

            $lines[] = '';
            $lines[] = $em(__('bot.help_help_title'));
            $lines[] = $adminUrl !== ''
                ? __('bot.help_admin_link', ['url' => $adminUrl])
                : __('bot.help_admin_no_link');

            return [
                'text' => $this->storeIntro() . "\n\n" . implode("\n", $lines),
                'buttons' => $buttons,
                'use_reply_keyboard' => true,
            ];
        }

        $lines = [
            $em('📖 Panduan Singkat'),
            '',
            $em('🛒 Cara Order'),
            '1. Tekan *🛍️ Buka Menu*',
            '2. Pilih layanan, lalu pilih nominalnya',
            '3. Masukkan detail kontak untuk bukti pembayaran',
            '4. Pilih pembayaran, lalu selesaikan pembayaran',
            '',
            $em('🔎 Cek & Kelola'),
            '• *📦 Cek Status* — status pesanan terakhir',
            '• *📜 Riwayat Order* — daftar pesananmu',
            '• *🔍 Cek ID Game* — pastikan nama akun benar dulu',
            '• *❌ Batal Transaksi* — batalkan pesanan yang belum dibayar',
        ];

        if ($capabilities->supports('deposit')) {
            // Ditaruh di dalam daftar supaya urutannya ikut alur, bukan
            // menggantung di bawah.
            $lines[] = '• *💰 Deposit* — isi saldo lebih dulu';
        }

        $lines[] = '';
        $lines[] = $em('❓ Butuh Bantuan?');

        // Kontak admin hanya ada di SATU tempat: di sini, dan lengkap dengan
        // tautan yang bisa dipencet. Sebelumnya kontak juga ditempel di
        // sapaan pembuka sebagai nomor mentah — duplikat yang tidak bisa
        // dipencet, jadi dihapus.
        //
        // Cabang Telegram sudah ditangani di atas, jadi di sini murni WhatsApp:
        // URL ditulis apa adanya karena WhatsApp tidak merender sintaks tautan
        // Telegram, sehingga bisa diketuk langsung.
        $lines[] = $adminUrl !== ''
            ? "Hubungi admin di {$adminUrl}, atau ketik /admin. 🙏"
            : 'Ketik /admin untuk menghubungi admin kalau ada kendala. 🙏';

        return [
            'text' => $this->storeIntro() . "\n\n" . implode("\n", $lines),
            'buttons' => $buttons,
            'use_reply_keyboard' => true,
        ];
    }

    /**
     * @param array{items: array<int, array<string, mixed>>, previous_cursor: string|null, next_cursor: string|null, current_cursor: string|null, invalid_cursor: bool, previous_handle?: string|null, next_handle?: string|null, current_handle?: string|null} $data
     */
    public function formatOrderHistory(array $data, ?string $source = null): array
    {
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;

        if ($data['invalid_cursor'] ?? false) {
            return [
                'text' => $isTelegram
                    ? __('bot.history_invalid')
                    : 'Riwayat sudah kedaluwarsa atau tidak valid. Buka riwayat terbaru.',
                'buttons' => [[$this->button(
                    $isTelegram ? __('bot.history_load_latest') : '📜 Muat Riwayat Terbaru',
                    'order_history',
                )]],
                'numeric_menu' => [
                    'menu' => 'order_history_invalid',
                    'parent_menu' => 'menu',
                    'cursor' => null,
                ],
            ];
        }

        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        if ($items === []) {
            return [
                'text' => $isTelegram
                    ? __('bot.history_empty_title') . "\n\n" . __('bot.history_empty_body')
                    : '📦 *RIWAYAT ORDER*\n\nBelum ada order yang dapat ditampilkan untuk akun ini.',
                'buttons' => [[$this->button(
                    $isTelegram ? __('bot.btn_back_menu') : '🔙 Kembali ke Menu',
                    'menu',
                    'back',
                )]],
                'numeric_menu' => [
                    'menu' => 'order_history',
                    'parent_menu' => 'menu',
                    'cursor' => $data['current_handle'] ?? null,
                ],
            ];
        }

        $lines = [
            $isTelegram ? __('bot.history_title') : '📦 *Riwayat Order*',
            '',
        ];
        $buttons = [];
        $currentHandle = is_string($data['current_handle'] ?? null)
            ? $data['current_handle']
            : null;

        foreach (array_values($items) as $index => $item) {
            $number = $index + 1;
            $status = $this->orderStatusLabel($item);
            $amount = number_format((int) ($item['amount'] ?? 0), 0, ',', '.');
            $lines[] = "{$number}. {$status} " . $this->escapeMarkdown(
                (string) ($item['service'] ?? ($isTelegram ? __('bot.sender_list_product_fallback') : 'Produk')),
            );
            $lines[] = '   `' . $this->escapeMarkdownCode((string) ($item['order_id'] ?? '')) . '` · Rp ' . $amount . ' · ' . $this->escapeMarkdown((string) ($item['created_at'] ?? '-'));
            $lines[] = '';
            $detailCallback = 'history detail ' . (string) ($item['reference'] ?? '');
            if ($currentHandle !== null) {
                $detailCallback .= ' ' . $currentHandle;
            }
            $buttons[] = [$this->button(
                $isTelegram ? __('bot.history_detail_btn', ['number' => $number]) : 'Detail #' . $number,
                $detailCallback,
                'content',
            )];
        }

        if (is_string($data['previous_handle'] ?? null)) {
            $buttons[] = [$this->button(
                $isTelegram ? __('bot.btn_prev') : '⬅️ Sebelumnya',
                'history nav ' . $data['previous_handle'],
                'navigation_previous',
            )];
        }
        if (is_string($data['next_handle'] ?? null)) {
            $buttons[] = [$this->button(
                $isTelegram ? __('bot.btn_next') : 'Berikutnya ➡️',
                'history nav ' . $data['next_handle'],
                'navigation_next',
            )];
        }
        $buttons[] = [$this->button(
            $isTelegram ? __('bot.btn_back_menu') : '🔙 Kembali ke Menu',
            'menu',
            'back',
        )];

        return [
            'text' => implode("\n", $lines),
            'buttons' => $buttons,
            'numeric_menu' => [
                'menu' => 'order_history',
                'parent_menu' => 'menu',
                'cursor' => $currentHandle,
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function formatOrderHistoryDetail(
        ?array $data,
        ?string $returnHandle = null,
        ?string $source = null,
    ): array {
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;
        $returnCallback = $returnHandle === null
            ? 'order_history'
            : 'history nav ' . $returnHandle;

        if ($data === null) {
            return [
                'text' => $isTelegram
                    ? __('bot.history_detail_missing')
                    : 'Order tidak ditemukan atau tidak dapat ditampilkan.',
                'buttons' => [[
                    $this->button(
                        $isTelegram ? __('bot.btn_back_history') : '📜 Kembali ke Riwayat',
                        $returnCallback,
                        'back',
                    ),
                ]],
                'numeric_menu' => [
                    'menu' => 'order_history_detail',
                    'parent_menu' => 'order_history',
                    'cursor' => $returnHandle,
                ],
            ];
        }

        $amount = number_format((int) ($data['amount'] ?? 0), 0, ',', '.');
        $product = $this->escapeMarkdown(
            (string) ($data['service'] ?? ($isTelegram ? __('bot.sender_list_product_fallback') : 'Produk')),
        );
        $date = $this->escapeMarkdown((string) ($data['created_at'] ?? '-'));
        $statusLabel = $this->escapeMarkdown((string) ($data['status_label'] ?? 'Unknown'));

        $lines = $isTelegram ? [
            __('bot.history_detail_title'),
            '',
            __('bot.history_detail_invoice', [
                'order_id' => $this->escapeMarkdownCode((string) ($data['order_id'] ?? '')),
            ]),
            __('bot.history_detail_product', ['product' => $product]),
            __('bot.history_detail_date', ['date' => $date]),
            __('bot.history_detail_total', ['amount' => $amount]),
            __('bot.history_detail_status', ['status' => $statusLabel]),
        ] : [
            '🧾 *DETAIL ORDER*',
            '',
            'Invoice: `' . $this->escapeMarkdownCode((string) ($data['order_id'] ?? '')) . '`',
            'Produk: ' . $product,
            'Tanggal: ' . $date,
            'Total: Rp ' . $amount,
            'Status Order: ' . $statusLabel,
        ];

        if (filled($data['payment_status'] ?? null)) {
            $paymentStatus = $this->escapeMarkdown((string) $data['payment_status']);
            $lines[] = $isTelegram
                ? __('bot.history_detail_payment_status', ['status' => $paymentStatus])
                : 'Status Pembayaran: ' . $paymentStatus;
        }

        if (filled($data['target_game_account_id'] ?? null)) {
            $gameId = $this->escapeMarkdown((string) $data['target_game_account_id']);
            $lines[] = $isTelegram
                ? __('bot.history_detail_game_id', ['game_id' => $gameId])
                : 'ID Game: ' . $gameId;
        }

        return [
            'text' => implode("\n", $lines),
            'buttons' => [[
                $this->button(
                    $isTelegram ? __('bot.btn_back_history') : '📜 Kembali ke Riwayat',
                    $returnCallback,
                    'back',
                ),
            ]],
            'numeric_menu' => [
                'menu' => 'order_history_detail',
                'parent_menu' => 'order_history',
                'cursor' => $returnHandle,
            ],
        ];
    }

    private function orderStatusLabel(array $item): string
    {
        return match ((string) ($item['status'] ?? 'unknown')) {
            'success' => '✅',
            'pending' => '⏳',
            'processing' => '⏳',
            'failed', 'cancelled', 'expired', 'refunded' => '❌',
            default => '⚠️',
        };
    }

    /**
     * @param string|null $source Jalur `telegram_gateway` memakai lang (bahasa
     *   aktif); channel lain tetap literal Indonesia (leaderboard WhatsApp
     *   berbagi method ini).
     */
    public function formatLeaderboard(array $data, ?string $source = null): array
    {
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;

        $sections = [
            'today' => $isTelegram ? __('bot.leaderboard_today') : 'Hari Ini',
            'week' => $isTelegram ? __('bot.leaderboard_week') : 'Minggu Ini',
            'month' => $isTelegram ? __('bot.leaderboard_month') : 'Bulan Ini',
        ];
        $lines = ['🏆 *Leaderboard*'];

        foreach ($sections as $key => $label) {
            $lines[] = '';
            $lines[] = "*{$label}*";
            $rows = is_array($data[$key] ?? null) ? $data[$key] : [];

            if ($rows === []) {
                $lines[] = $isTelegram ? __('bot.leaderboard_empty') : 'Belum ada transaksi sukses.';
                continue;
            }

            foreach (array_values($rows) as $index => $row) {
                $username = $this->escapeMarkdown((string) ($row['username'] ?? 'User'));
                $total = number_format((int) ($row['total_harga'] ?? 0), 0, ',', '.');
                $lines[] = ($index + 1) . ". {$username} — Rp {$total}";
            }
        }

        return [
            'text' => implode("\n", $lines),
            'buttons' => [[
                $this->button($isTelegram ? __('bot.btn_back_menu') : '🔙 Kembali ke Menu', 'menu'),
            ]],
        ];
    }

    /**
     * @return array{keyboard: array, resize_keyboard: bool, is_persistent: bool, input_field_placeholder: string}
     */
    public function defaultReplyKeyboard(?BotGatewayCapabilities $capabilities = null): array
    {
        $capabilities ??= BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM);

        // Label mengikuti bahasa aktif — TAPI hanya di jalur Telegram. Reply
        // keyboard memang cuma dipakai Telegram, dan `defaultReplyKeyboard()`
        // selalu dipanggil dengan source Telegram; penjagaan eksplisit di sini
        // bikin scope-nya tidak bergantung pada fakta itu.
        $isTelegram = $capabilities->source() === BotGatewayCapabilities::SOURCE_TELEGRAM;

        $pick = fn (string $key, string $fallback): string => $isTelegram
            ? $this->keyboardLabel($key, $fallback)
            : $fallback;

        $keyboard = [[['text' => $pick('bot.kbd_menu', '🛍️ Buka Menu')]]];

        if ($capabilities->supports('leaderboard')) {
            $keyboard[] = [['text' => '🏆 Leaderboard']];
        }
        if ($capabilities->supports('order_history')) {
            // Dibaca dari `kbd_*` juga: copy panduan menyebut tombol ini.
            $keyboard[] = [['text' => $pick('bot.kbd_history', '📜 Riwayat Order')]];
        }
        if ($capabilities->supports('deposit')) {
            $keyboard[] = [['text' => '💰 Deposit']];
        }

        // Tombol "🔍 Cek ID Game" TIDAK dirender (keputusan user).
        //
        // Perintah `cekid` sendiri tetap hidup: parser tetap mengenali labelnya
        // (label lama masih tergeletak di riwayat chat user) dan `handleCekId()`
        // tidak disentuh. Jadi ini soal berhenti MEMPROMOSIKAN tombolnya, bukan
        // mematikan fiturnya.
        $keyboard[] = [
            ['text' => $pick('bot.kbd_status', '📦 Cek Status')],
        ];

        // Baris bahasa — Telegram saja, dan dijaga EKSPLISIT pada source-nya.
        // Reply keyboard memang cuma dipakai Telegram, tapi penjagaan ini bikin
        // scope-nya tidak bergantung pada fakta itu: kalau suatu saat channel
        // lain ikut memakai reply keyboard, ia tidak otomatis kena switch bahasa
        // yang di luar scope.
        // Label diambil dari `languageButtons()` supaya TIDAK PERNAH menyimpang
        // dari panel `/bahasa`; label yang menyimpang = tombol mati.
        if ($capabilities->source() === BotGatewayCapabilities::SOURCE_TELEGRAM) {
            $languageRow = [];
            foreach ($this->languageButtons() as $button) {
                $languageRow[] = ['text' => (string) $button['text']];
            }
            $keyboard[] = $languageRow;
        }

        // Tombol "❌ Batal Transaksi" TIDAK dirender (keputusan user).
        //
        // Dihapus di SUMBERNYA, bukan disaring di keyboard angka saja: keyboard
        // angka menggantikan keyboard ini begitu user membuka daftar, tapi
        // sebelum itu (`/start`, layar panduan) yang terpasang adalah keyboard
        // INI — menyaring di satu tempat saja meninggalkan tombolnya terlihat
        // di layar-layar awal, dan user mengira tombolnya masih ada.
        //
        // Label `bot.kbd_cancel` tetap ada di file lang dan perintah `batal`
        // tetap dikenali parser: label lama masih tergeletak di riwayat chat
        // user, dan layar konfirmasi checkout memakai tombol inline-nya sendiri.
        $keyboard[] = [
            ['text' => $pick('bot.kbd_help', '❓ Bantuan')],
        ];

        // TIDAK ada tombol "📞 Hubungi Admin" di sini.
        //
        // Reply keyboard Telegram hanya bisa memuat callback/data — labelnya
        // cuma TEKS yang dikirim kembali sebagai pesan, jadi tidak bisa
        // dijadikan tautan ke profil admin. Sebelumnya label itu tetap
        // dipasang ketika `admin_contact_url` terisi, dan hasilnya menyesatkan:
        // user menekannya, bot menerima balasan "📞 Hubungi Admin", lalu
        // perintah `admin` membalas dengan tautan yang harus diketuk pada
        // pesan BARU.
        //
        // Kontak admin sekarang dikirim sebagai tombol inline bertipe `url`
        // di pesan panduan/menu — itu benar-benar bisa dipencet sekali klik.
        return [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'is_persistent' => true,
            'input_field_placeholder' => $pick('bot.kbd_placeholder', 'Pilih aksi...'),
        ];
    }

    /**
     * @return array<int, string>
     */
    /**
     * @param bool $isTelegram true = ambil teks dari file lang (Telegram),
     *   false = literal Indonesia (WhatsApp, perilaku lama).
     */
    private function conversationalInputLines(
        bool $requiresZoneId,
        array $customInputs,
        bool $isTelegram = false,
    ): array {
        $userInput = is_array($customInputs['user_id'] ?? null) ? $customInputs['user_id'] : [];
        $zoneInput = is_array($customInputs['zone'] ?? null) ? $customInputs['zone'] : [];
        $userLabel = trim((string) ($userInput['label'] ?? 'User ID')) ?: 'User ID';
        $userPlaceholder = trim((string) ($userInput['placeholder'] ?? 'Masukkan User ID')) ?: 'Masukkan User ID';
        $userLabelText = $this->escapeMarkdown($userLabel);
        $isEmail = str_contains(strtolower($userLabel), 'email')
            || str_contains(strtolower($userPlaceholder), 'email');
        $lines = [];

        if (! $requiresZoneId) {
            return [
                $isTelegram
                    ? __($isEmail ? 'bot.checkout_input_title_email' : 'bot.checkout_input_title', ['label' => $userLabelText])
                    : ($isEmail ? '📧' : '🎮') . ' *Masukkan ' . $userLabelText . '*',
                '',
                // 'Format: `UID`' dan 'Format: `email@contoh.com`' identik di
                // kedua bahasa — dibiarkan literal supaya parity guard tetap
                // bermakna. Contohnya yang beda, itu yang diterjemahkan.
                $isEmail ? 'Format: `email@contoh.com`' : 'Format: `UID`',
                $isTelegram
                    ? __($isEmail ? 'bot.checkout_input_example_email' : 'bot.checkout_input_example_uid')
                    : ($isEmail ? 'Contoh: `nama@email.com`' : 'Contoh: `12345`'),
            ];
        }

        $zoneLabel = trim((string) ($zoneInput['label'] ?? 'Server ID')) ?: 'Server ID';
        $zonePlaceholder = trim((string) ($zoneInput['placeholder'] ?? 'Masukkan Server ID')) ?: 'Masukkan Server ID';
        $zoneLabelText = $this->escapeMarkdown($zoneLabel);

        $lines[] = $isTelegram
            ? __('bot.checkout_input_title', ['label' => $userLabelText])
            : '🎮 *Masukkan ' . $userLabelText . '*';
        $lines[] = '';
        // Netral di kedua bahasa ('Format' == 'Format'), jadi dibiarkan
        // literal — sama seperti 'Format: `UID`' di cabang tanpa zone.
        $lines[] = 'Format: `UID <' . $this->escapeMarkdownCode($zoneLabel) . '>`';
        $lines[] = $isTelegram
            ? __('bot.checkout_input_example_zone')
            : 'Contoh: `12345 6789`';

        if (($zoneInput['is_select'] ?? false) && ! empty($zoneInput['options']) && is_array($zoneInput['options'])) {
            $lines[] = '';
            $lines[] = $isTelegram
                ? __('bot.checkout_input_zone_options', ['label' => $zoneLabelText])
                : "Pilihan {$zoneLabelText}:";

            foreach ($zoneInput['options'] as $option) {
                if (! is_array($option)) {
                    continue;
                }

                $label = trim((string) ($option['label'] ?? ''));
                $value = trim((string) ($option['value'] ?? ''));
                if ($value === '') {
                    continue;
                }

                $lines[] = '• ' . $this->escapeMarkdown($label !== '' ? $label : $value)
                    . ': `' . $this->escapeMarkdownCode($value) . '`';
            }
        }

        return $lines;
    }

    private function formatExpiredStatus(array $data, ?string $source = null): array
    {
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;
        $storeName = $this->escapeMarkdown(trim((string) config('app.name', 'Laravel')) ?: 'Laravel');
        $orderId = $this->escapeMarkdown((string) ($data['order_id'] ?? ''));
        $product = $this->escapeMarkdown((string) ($data['product'] ?? 'Produk'));

        return [
            'text' => implode("\n", [
                $isTelegram ? __('bot.status_expired_title') : '❌ *Pembayaran Kadaluarsa*',
                '',
                '💎 ' . $product,
                '🧾 `' . $this->escapeMarkdownCode((string) ($data['order_id'] ?? '')) . '`',
                '',
                $isTelegram ? __('bot.status_expired_body') : 'Silakan buat pesanan ulang.',
            ]),
            'buttons' => [
                [
                    $this->button($isTelegram ? __('bot.btn_back_menu') : '🔙 Kembali ke Menu', 'menu'),
                ],
            ],
        ];
    }

    private function formatUnpaidStatus(array $data, ?string $source = null): array
    {
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;
        $payment = is_array($data['payment'] ?? null) ? $data['payment'] : [];
        $orderId = $this->escapeMarkdown((string) ($data['order_id'] ?? ''));
        $product = $this->escapeMarkdown((string) ($data['product'] ?? 'Produk'));
        $amount = is_numeric($payment['amount'] ?? null) ? (int) $payment['amount'] : (int) ($data['amount'] ?? 0);
        $method = $this->escapeMarkdown(trim((string) ($payment['method'] ?? '')) ?: 'Pembayaran');

        return [
            'text' => implode("\n", [
                $isTelegram ? __('bot.status_unpaid_title') : '⏳ *Menunggu Pembayaran*',
                '',
                '💎 ' . $product,
                $isTelegram
                    ? __('bot.status_unpaid_amount', ['amount' => number_format($amount, 0, ',', '.')])
                    : '💰 *Rp ' . number_format($amount, 0, ',', '.') . '*',
                '🧾 `' . $this->escapeMarkdownCode((string) ($data['order_id'] ?? '')) . '`',
                '',
                $isTelegram
                    ? __('bot.status_unpaid_method', ['method' => $method])
                    : '💳 Metode: *' . $method . '*',
                $isTelegram ? __('bot.status_unpaid_check') : 'Ketik `status` untuk cek pembayaran.',
            ]),
            'buttons' => [
                [
                    $this->button($isTelegram ? __('bot.btn_back_menu') : '🔙 Kembali ke Menu', 'menu'),
                ],
            ],
        ];
    }

    private function paymentExpiryLabel(mixed $expiresAt): ?string
    {
        if (blank($expiresAt)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($expiresAt)
                ->timezone(config('app.timezone'))
                ->format('d/m/Y H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function maskedTarget(string $value): string
    {
        $value = trim($value);
        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 2)
            . str_repeat('*', max(2, $length - 4))
            . substr($value, -2);
    }

    private function escapeMarkdown(string $value): string
    {
        return str_replace(
            ['\\', '_', '*', '`', '[', ']'],
            ['\\\\', '\\_', '\\*', '\\`', '\\[', '\\]'],
            $value,
        );
    }

    private function escapeMarkdownCode(string $value): string
    {
        return str_replace(['\\', '`'], ['\\\\', '\\`'], $value);
    }

    // ============================================================= daftar layanan
    //
    // Layar layanan Telegram adalah SATU daftar rata di dalam TEKS, satu baris
    // per layanan. Bukan kartu per item, dan tanpa nama paket.
    //
    // **Kenapa di TEKS.** Telegram hanya mengizinkan SATU `reply_markup` per
    // pesan, dan begitu keyboard angka dipakai (keputusan user: keyboard angka
    // global), tombol inline TIDAK ikut terkirim sama sekali. Daftar yang
    // disimpan di tombol karena itu tidak pernah terlihat user — layarnya
    // tampak kosong, hanya tersisa judul.
    //
    // **Kenapa tanpa nama paket.** Kartu per item mencetak nama paketnya di
    // SETIAP kartu ("Proses Instant" 48 kali untuk Free Fire) dan memakai 5
    // baris untuk satu layanan. Setelah user berada di dalam kategorinya, nama
    // paket tidak menambah informasi apa pun.

    /**
     * Jumlah layanan per halaman layar layanan Telegram.
     *
     * Sepuluh, bukan delapan: dengan satu baris per layanan, sepuluh baris
     * masih nyaman dibaca sekali lihat, dan keyboard angka menampungnya dalam
     * dua baris (5 per baris).
     *
     * SENGAJA terpisah dari `BotGatewayCapabilities::menuPageSize()`: konstanta
     * itu dipakai bersama Menu Utama dan Pilih Game, yang isinya kategori
     * (pendek-pendek) dan tidak boleh ikut bergeser.
     */
    private const SERVICE_LIST_PAGE_SIZE = 10;

    /**
     * Nomor 98/99 untuk pindah halaman, dengan arti yang sama seperti jalur
     * WhatsApp supaya kebiasaan user tidak perlu diubah antar channel.
     *
     * @param  array<string, array<string, string>>  $entries
     * @return array<string, array<string, string>>
     */
    private function serviceNavigationEntries(array $entries, array $pagination, string $baseCommand): array
    {
        $page = (int) ($pagination['page'] ?? 1);
        $totalPages = (int) ($pagination['total_pages'] ?? 1);

        if ($page > 1) {
            $entries['98'] = [
                'type' => 'navigation_previous',
                'label' => '⬅️ Prev',
                'command' => $baseCommand . ' page:' . ($page - 1),
            ];
        }

        if ($page < $totalPages) {
            $entries['99'] = [
                'type' => 'navigation_next',
                'label' => 'Next ➡️',
                'command' => $baseCommand . ' page:' . ($page + 1),
            ];
        }

        return $entries;
    }

    /**
     * Tombol pindah halaman untuk pesan kedua “Pindah halaman”.
     *
     * Tombol ini menyimpan DUA perintah, dan itu disengaja:
     *
     * - `callback` memakai pola `menu page:N`. Adapter mengirim pesan kedua
     *   HANYA untuk tombol berpola itu, jadi tanpa pola ini navigasinya ikut
     *   terbuang dan halaman berikutnya cuma bisa dicapai dengan mengetik `99`.
     * - `page_command` memuat perintah layar yang SEBENARNYA (`layanan mlbb
     *   page:2`). Inilah yang dipakai adapter sebagai isi tombol di pesan
     *   kedua. Sebelumnya `callback` yang dipakai, sehingga menekan “Next ➡️”
     *   membuka Menu Utama halaman 2 — bukan lanjutan daftar layanan yang
     *   sedang dilihat user.
     *
     * @return array<int, array<int, array<string, string>>>
     */
    private function serviceNavigationButtons(array $pagination, string $baseCommand): array
    {
        if ((int) ($pagination['total_pages'] ?? 1) <= 1) {
            return [];
        }

        $page = (int) $pagination['page'];
        $row = [];

        if ($page > 1) {
            $row[] = $this->button('⬅️ Prev', 'menu page:' . ($page - 1), 'navigation_previous')
                + ['page_command' => $baseCommand . ' page:' . ($page - 1)];
        }

        if ($page < (int) $pagination['total_pages']) {
            $row[] = $this->button('Next ➡️', 'menu page:' . ($page + 1), 'navigation_next')
                + ['page_command' => $baseCommand . ' page:' . ($page + 1)];
        }

        return $row === [] ? [] : [$row];
    }

    private function invoicePhotoUrl(array $invoice, string $paymentCode): ?string
    {
        // Step 0: URL checkout TriPay (/qr/...) langsung return gambar PNG.
        // Kalau dibawa ke isCheckoutUrl, bakal di-generate QR dari URL-nya
        // (salah: QR yang dihasilkan berisi link, bukan payload QRIS).
        $tripayQr = data_get($invoice, 'payment_url')
            ?? data_get($invoice, 'pay_url')
            ?? data_get($invoice, 'payment.pay_url')
            ?? data_get($invoice, 'data.pay_url');
        if (is_string($tripayQr) && $tripayQr !== '' && $this->isTripayQrUrl($tripayQr)) {
            return $tripayQr;
        }

        // Step 1: Cek URL gambar QR yang sudah jadi dari berbagai gateway
        foreach ([
            data_get($invoice, 'payment.qr_image_url'),
            data_get($invoice, 'qr_image_url'),
            data_get($invoice, 'qris_url'),
            data_get($invoice, 'qr_url'),
            data_get($invoice, 'qr_image_url'),
            data_get($invoice, 'qr_link'),           // Tokopay: qr_link
            data_get($invoice, 'barcode_url'),
            data_get($invoice, 'payment.qris_url'),
            data_get($invoice, 'payment.qr_url'),
            data_get($invoice, 'payment.qr_image_url'),
            data_get($invoice, 'payment.qr_link'),   // Tokopay nested
            data_get($invoice, 'payment.barcode_url'),
            data_get($invoice, 'data.qr_link'),      // Tokopay: data.qr_link
            data_get($invoice, 'payment_url'),       // Gateway payment URL
            data_get($invoice, 'payment.payment_url'),
            data_get($invoice, 'pay_url'),           // Gateway pay URL
            data_get($invoice, 'payment.pay_url'),
            data_get($invoice, 'data.pay_url'),
            data_get($invoice, 'paymentUrl'),        // Duitku: paymentUrl
            data_get($invoice, 'payment.paymentUrl'),
            data_get($invoice, 'data.paymentUrl'),
            $paymentCode,                             // Bisa berupa URL gambar langsung
        ] as $url) {
            if (filter_var($url, FILTER_VALIDATE_URL) && $this->isImageUrl($url)) {
                return $url;
            }
        }

        // Step 3: Cek raw QR string atau checkout URL dari berbagai gateway
        $qrData = null;
        $provider = strtolower(trim((string) data_get($invoice, 'payment.provider', '')));
        $paymentUrl = trim((string) data_get($invoice, 'payment.payment_url', ''));
        $paymentCodeIsTokopayUrl = $provider === 'tokopay'
            && $paymentUrl !== ''
            && trim($paymentCode) === $paymentUrl;

        foreach ([
            data_get($invoice, 'payment.qr_payload'),
            data_get($invoice, 'qr_payload'),
            data_get($invoice, 'qrString'),          // Duitku: qrString
            data_get($invoice, 'qr_string'),         // Tripay/Tokopay: qr_string
            data_get($invoice, 'payment.qr_string'),
            data_get($invoice, 'data.qr_string'),    // Tokopay: data.qr_string
            data_get($invoice, 'paymentUrl'),        // Duitku: paymentUrl (fallback)
            data_get($invoice, 'payment.paymentUrl'),
            data_get($invoice, 'data.paymentUrl'),
            data_get($invoice, 'pay_url'),           // Tokopay: pay_url
            data_get($invoice, 'payment.pay_url'),
            data_get($invoice, 'data.pay_url'),      // Tokopay: data.pay_url
            data_get($invoice, 'checkout_url'),      // Tripay: checkout_url
            data_get($invoice, 'payment.checkout_url'),
            $paymentCode,                             // Fallback ke payment_code
        ] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($paymentCodeIsTokopayUrl && $candidate === $paymentUrl) {
                continue;
            }

            if ($candidate !== '' && ($this->isQrisPayload($candidate) || $this->isCheckoutUrl($candidate))) {
                $qrData = $candidate;
                break;
            }
        }

        if ($qrData === null) {
            return null;
        }

        // Step 3: Generate QR code menggunakan api.qrserver.com
        return 'https://api.qrserver.com/v1/create-qr-code/?size=512x512&margin=15&data=' . rawurlencode($qrData);
    }

    private function isTripayQrUrl(string $url): bool
    {
        $parsedUrl = parse_url($url);
        if (! is_array($parsedUrl) || strtolower((string) ($parsedUrl['scheme'] ?? '')) !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parsedUrl['host'] ?? ''));
        if (! in_array($host, ['tripay.co.id', 'www.tripay.co.id'], true)) {
            return false;
        }

        $path = (string) ($parsedUrl['path'] ?? '');

        return preg_match('#^/(?:qr|payment)/[^/]+$#i', $path) === 1;
    }

    private function isImageUrl(string $url): bool
    {
        $parsedUrl = parse_url($url);
        $path = is_array($parsedUrl) ? (string) ($parsedUrl['path'] ?? '') : '';
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if (in_array(strtolower($extension), ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            return true;
        }

        if (! is_array($parsedUrl) || strtolower((string) ($parsedUrl['scheme'] ?? '')) !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parsedUrl['host'] ?? ''));
        if (! in_array($host, ['tripay.co.id', 'www.tripay.co.id'], true)) {
            return false;
        }

        return preg_match('#^/(?:qr|payment)/[^/]+(?:/|$)#i', $path) === 1;
    }

    private function isCheckoutUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && (str_contains($value, 'checkout')
                || str_contains($value, 'pay.')
                || str_contains($value, '/pay/'));
    }

    private function isQrisPayload(string $paymentCode): bool
    {
        $paymentCode = trim($paymentCode);

        return str_starts_with($paymentCode, '000201')
            && strlen($paymentCode) >= 50;
    }

    private function categoryButtonLabel(string $name, string $slug, mixed $icon = null): string
    {
        return $this->labelWithEmoji($name, $this->emojiForCategory($name, $slug, $icon));
    }

    private function gameButtonLabel(string $name, string $code): string
    {
        return $this->labelWithEmoji($name, $this->emojiForGame($name, $code));
    }

    private function labelWithEmoji(string $label, string $emoji): string
    {
        $label = trim($label);

        if ($label !== '' && preg_match('/^\p{So}/u', $label)) {
            return $label;
        }

        return trim($emoji . ' ' . $label);
    }

    private function emojiForCategory(string $name, string $slug, mixed $icon = null): string
    {
        $icon = trim((string) $icon);
        if ($icon !== '') {
            return $icon;
        }

        $key = $this->normalizeKey($slug . ' ' . $name);

        foreach (self::CATEGORY_EMOJIS as $needle => $emoji) {
            if (str_contains($key, $needle)) {
                return $emoji;
            }
        }

        return '🛍️';
    }

    private function emojiForGame(string $name, string $code): string
    {
        $key = $this->normalizeKey($code . ' ' . $name);

        foreach (self::GAME_EMOJIS as $needle => $emoji) {
            if (str_contains($key, $needle)) {
                return $emoji;
            }
        }

        return '🎮';
    }

    private function normalizeKey(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    private function paginate(
        array $items,
        int $page,
        int $pageSize = self::PAGE_SIZE,
    ): array {
        $pageSize = max(1, $pageSize);
        $total = count($items);
        $totalPages = max(1, (int) ceil($total / $pageSize));
        $currentPage = min(max(1, $page), $totalPages);

        return [
            'items' => array_slice($items, ($currentPage - 1) * $pageSize, $pageSize),
            'page' => $currentPage,
            'total_pages' => $totalPages,
            'total' => $total,
        ];
    }

    private function appendPagination(array $buttons, string $baseCallback, array $pagination): array
    {
        if (($pagination['total_pages'] ?? 1) <= 1) {
            return $buttons;
        }

        $page = (int) $pagination['page'];
        $totalPages = (int) $pagination['total_pages'];
        $row = [];

        if ($page > 1) {
            $row[] = $this->button(
                '⬅️ Prev',
                $baseCallback . ' page:' . ($page - 1),
                'navigation_previous',
            );
        }

        if ($page < $totalPages) {
            $row[] = $this->button(
                'Next ➡️',
                $baseCallback . ' page:' . ($page + 1),
                'navigation_next',
            );
        }

        if ($row !== []) {
            $buttons[] = $row;
        }

        return $buttons;
    }

    /**
     * Tombol "kembali" generik.
     *
     * @param string|null $source Jalur `telegram_gateway` memakai label dari
     *   lang (bahasa aktif); channel lain tetap literal Indonesia supaya
     *   perilakunya tidak berubah sama sekali.
     */
    private function appendBack(array $buttons, string $callback, ?string $source = null): array
    {
        $label = $source === BotGatewayCapabilities::SOURCE_TELEGRAM
            ? __('bot.btn_back')
            : '🔙 Kembali';
        $buttons[] = [$this->button($label, $callback, 'back')];

        return $buttons;
    }

    private function pageSuffix(array $pagination): string
    {
        if (($pagination['total_pages'] ?? 1) <= 1) {
            return '';
        }

        return " · {$pagination['page']}/{$pagination['total_pages']}";
    }

    /**
     * Footer layar daftar: baris halaman (kalau lebih dari satu) + jam.
     *
     * Baris halaman disembunyikan saat cuma ada SATU halaman: "📄 Halaman 1 / 1"
     * bukan informasi, hanya kebisingan yang membuat daftar pendek terlihat
     * seperti terpotong.
     *
     * Jam diambil lewat `config('app.timezone')`, bukan `now()` mentah. Server
     * menyimpan waktu UTC, sedangkan user bot ada di WIB; jam yang menyimpang
     * dari jam HP user membuat pesan terlihat basi — dan user memakai jam ini
     * untuk menyocokkan dengan riwayat order.
     */
    private function menuListFooter(array $pagination): string
    {
        $lines = [];

        $total = (int) ($pagination['total_pages'] ?? 1);
        if ($total > 1) {
            $lines[] = __('bot.menu_page_footer', [
                'page' => (int) ($pagination['page'] ?? 1),
                'total' => $total,
            ]);
        }

        $lines[] = '📆 ' . \Illuminate\Support\Carbon::now(config('app.timezone'))->format('h:i:s A');

        return implode("\n", $lines);
    }

    /**
     * Tombol aksi layar Menu Utama Telegram — TANPA tombol kategori.
     *
     * Dikembalikan sebagai baris terpisah supaya adapter bisa memutuskan
     * mengirimnya (sebagai pesan kedua) saat tombol harus bergeser tempat.
     * Menyertakannya di sini menjaga susunannya satu sumber dengan layar lain.
     *
     * @return array<int, array<int, array<string, string>>>
     */
    private function menuActionButtons(BotGatewayCapabilities $capabilities): array
    {
        $buttons = [];

        if ($capabilities->supports('leaderboard')) {
            $buttons[] = [$this->button('🏆 Leaderboard', 'leaderboard')];
        }

        if ($capabilities->supports('deposit')) {
            $buttons[] = [$this->button('💰 Deposit', 'deposit')];
        }

        if ($capabilities->source() === BotGatewayCapabilities::SOURCE_TELEGRAM) {
            $buttons[] = $this->languageButtons();
        }

        return $buttons;
    }

    /**
     * Reply keyboard angka untuk Telegram — GLOBAL, dikirim di setiap layar.
     *
     * Telegram hanya mengizinkan SATU `reply_markup` per pesan, jadi keyboard
     * ini MENGGANTIKAN keyboard default saat ada daftar aktif. Tombol aksi lama
     * TIDAK dibuang: kalau dibuang, user kehilangan akses Menu/Riwayat/Bantuan
     * dari layar dan hanya bisa lewat perintah ketik — regresi yang jauh lebih
     * besar daripada manfaat nomornya. Angka DITAMBAHKAN di atasnya.
     *
     * Jumlah angka MENGIKUTI daftar, bukan selalu 1..10: mengirim tombol yang
     * tidak punya item di belakangnya memberi user tombol mati.
     *
     * Tombol "❌ Batal Transaksi" sengaja tidak dirender (keputusan user). Label
     * `bot.kbd_cancel` tetap ada di file lang; perintah `batal` tetap dikenali
     * parser, dan layar konfirmasi checkout memakai tombol inline-nya sendiri.
     *
     * @param  array<int, int|string>  $numbers
     * @return array<string, mixed>
     */
    public function numericReplyKeyboard(array $numbers, ?BotGatewayCapabilities $capabilities = null): array
    {
        $capabilities ??= BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM);

        $keyboard = [];

        if ($numbers !== []) {
            $buttons = [];
            foreach (array_values($numbers) as $number) {
                $buttons[] = ['text' => (string) $number];
            }

            // Lima per baris: cukup rapat supaya keyboard tidak memakan separuh
            // layar, dan tetap sejajar dengan nomor 1..15 pada daftar terpanjang.
            $keyboard = array_chunk($buttons, 5);
        }

        // Tombol aksi diambil UTUH dari keyboard default; batal sudah tidak ada
        // di sana (dihapus di sumbernya), jadi di sini tidak perlu penyaringan
        // lagi — dan penyaringan berbasis label literal justru berbahaya: di
        // locale Inggris labelnya `❌ Cancel Order`, sehingga tombolnya lolos
        // hanya karena bahasanya berbeda.
        $base = $this->defaultReplyKeyboard($capabilities)['keyboard'];

        return [
            'keyboard' => array_merge($keyboard, $base),
            'resize_keyboard' => true,
            'is_persistent' => true,
            'input_field_placeholder' => $this->keyboardLabel('bot.kbd_placeholder', 'Pilih aksi...'),
        ];
    }

    private function button(
        string $text,
        string $callback,
        ?string $numericType = null,
    ): array {
        return array_filter([
            'text' => $text,
            'callback' => $this->callback($callback),
            'numeric_type' => $numericType,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private function urlButton(string $text, string $url): array
    {
        return [
            'text' => $text,
            'url' => $url,
        ];
    }

    private function callback(string $callback): string
    {
        $callback = trim(preg_replace('/\s+/', ' ', $callback) ?? $callback);

        if (strlen($callback) > 64) {
            throw new \InvalidArgumentException(
                'Callback bot melebihi batas 64 byte.',
            );
        }

        return $callback;
    }

    /**
     * Prompt shown to an unregistered WhatsApp sender when they attempt deposit.
     *
     * @return array{text: string, buttons: array}
     */
    public function formatWaRegisterPrompt(): array
    {
        return [
            'text' => implode("\n", [
                '⚠️ *Nomor WhatsApp kamu belum terdaftar.*',
                '',
                'Untuk melakukan deposit, kamu perlu membuat akun terlebih dahulu.',
                '',
                'Ketik *YA* untuk daftar sekarang, atau *TIDAK* untuk batalkan.',
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Prompt asking for optional email during WhatsApp registration.
     *
     * @return array{text: string, buttons: array}
     */
    public function formatWaRegisterEmailPrompt(): array
    {
        return [
            'text' => implode("\n", [
                '📧 *Pendaftaran Akun*',
                '',
                'Mau daftarkan email? Ketik alamat email kamu, atau ketik *SKIP* untuk lewati.',
                '',
                '_Email bersifat opsional dan bisa ditambahkan nanti via website._',
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Retry prompt shown when the provided email is invalid or already used.
     *
     * @param  int  $attemptsLeft  Number of attempts remaining before auto-SKIP.
     * @param  string  $reason     'duplicate' or 'invalid'
     * @return array{text: string, buttons: array}
     */
    public function formatWaRegisterEmailRetry(int $attemptsLeft, string $reason): array
    {
        $reasonText = $reason === 'duplicate'
            ? 'Email sudah digunakan oleh akun lain.'
            : 'Format email tidak valid.';

        return [
            'text' => implode("\n", [
                "❌ {$reasonText}",
                '',
                "Coba email lain, atau ketik *SKIP* untuk lewati. (Sisa percobaan: {$attemptsLeft})",
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Success message sent after WhatsApp auto-registration completes.
     *
     * @param  string  $username   Generated username (wa_628xxx).
     * @param  string  $password   Plain-text password — sent ONCE, never cached.
     * @param  string  $appUrl     Value of config('app.url').
     * @return array{text: string, buttons: array}
     */
    public function formatWaRegisterSuccess(string $username, string $password, string $appUrl): array
    {
        return [
            'text' => implode("\n", [
                '🎉 *Akun berhasil dibuat!*',
                '',
                "Username: `{$username}`",
                "Password: `{$password}`",
                '',
                '⚠️ _Simpan password ini sekarang, tidak akan dikirim ulang._',
                '',
                "Reset password: {$appUrl}/forgot-password",
                '',
                'Silakan ulangi perintah *deposit* untuk melanjutkan.',
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Message shown when an unverified WhatsApp account is auto-verified on deposit.
     *
     * @return array{text: string, buttons: array}
     */
    public function formatWaAutoVerified(): array
    {
        return [
            'text' => implode("\n", [
                '✅ *Nomor WhatsApp berhasil diverifikasi!*',
                '',
                'Akun kamu ditemukan dan nomor WhatsApp sudah terhubung.',
                '',
                'Silakan ulangi perintah *deposit* untuk melanjutkan.',
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Prompt shown to an unlinked Telegram sender when they attempt deposit.
     *
     * @return array{text: string, buttons: array}
     */
    public function formatTgRegisterPrompt(): array
    {
        // Prompt ini HANYA dirender di jalur Telegram (registrasi otomatis
        // Telegram), jadi tidak perlu threading `$source`. Prompt WhatsApp
        // punya method sendiri (`formatWaRegisterPrompt`) yang tetap Indonesia.
        return [
            'text' => implode("\n", [
                __('bot.tg_register_prompt_title'),
                '',
                __('bot.tg_register_prompt_body'),
                '',
                __('bot.tg_register_prompt_confirm'),
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Prompt asking for a custom username during Telegram registration.
     *
     * @return array{text: string, buttons: array}
     */
    public function formatTgRegisterUsernamePrompt(): array
    {
        return [
            'text' => implode("\n", [
                __('bot.tg_register_username_title'),
                '',
                __('bot.tg_register_username_prompt'),
                '',
                __('bot.tg_register_username_example'),
                __('bot.tg_register_username_note'),
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Retry prompt shown when the provided username is invalid or already taken.
     *
     * @param  int  $attemptsLeft  Number of attempts remaining.
     * @param  string  $reason     'invalid' or 'taken'
     * @return array{text: string, buttons: array}
     */
    public function formatTgRegisterUsernameRetry(int $attemptsLeft, string $reason): array
    {
        $reasonText = $reason === 'taken'
            ? __('bot.tg_register_username_taken')
            : __('bot.tg_register_username_invalid');

        return [
            'text' => implode("\n", [
                "❌ {$reasonText}",
                '',
                __('bot.tg_register_username_retry', ['left' => $attemptsLeft]),
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Prompt asking for optional email during Telegram registration.
     *
     * @return array{text: string, buttons: array}
     */
    public function formatTgRegisterEmailPrompt(): array
    {
        return [
            'text' => implode("\n", [
                __('bot.tg_register_email_title'),
                '',
                __('bot.tg_register_email_prompt'),
                '',
                __('bot.tg_register_email_note'),
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Retry prompt shown when the provided email is invalid or already used.
     *
     * @param  int  $attemptsLeft  Number of attempts remaining before auto-SKIP.
     * @param  string  $reason     'duplicate' or 'invalid'
     * @return array{text: string, buttons: array}
     */
    public function formatTgRegisterEmailRetry(int $attemptsLeft, string $reason): array
    {
        $reasonText = $reason === 'duplicate'
            ? __('bot.tg_register_email_duplicate')
            : __('bot.tg_register_email_invalid');

        return [
            'text' => implode("\n", [
                "❌ {$reasonText}",
                '',
                __('bot.tg_register_email_retry', ['left' => $attemptsLeft]),
            ]),
            'buttons' => [],
        ];
    }

    /**
     * Success message sent after Telegram auto-registration completes.
     *
     * @param  string  $username   Provided username.
     * @param  string  $password   Plain-text password — sent ONCE, never cached.
     * @param  string  $appUrl     Value of config('app.url').
     * @return array{text: string, buttons: array}
     */
    public function formatTgRegisterSuccess(string $username, string $password, string $appUrl): array
    {
        return [
            'text' => implode("\n", [
                __('bot.tg_register_success_title'),
                '',
                "Username: `{$username}`",
                "Password: `{$password}`",
                '',
                __('bot.tg_register_success_note'),
                '',
                "Reset password: {$appUrl}/forgot-password",
                '',
                __('bot.tg_register_success_retry'),
            ]),
            'buttons' => [],
        ];
    }

    /**
     * @param string|null $source Lihat catatan di `formatPriceQuote()`.
     *
     * Alur numerik deposit dipakai KEDUA channel (WhatsApp & Telegram), tapi
     * scope terjemahan hanya jalur Telegram — WhatsApp tetap Indonesia.
     */
    public function formatDepositAmountPrompt(?string $source = null): array
    {
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;

        return [
            'text' => implode("\n", [
                $isTelegram ? __('bot.deposit_amount_title') : '💰 *Pilih Jumlah Deposit*',
                '',
                $isTelegram ? __('bot.deposit_amount_hint') : 'Silakan pilih nominal deposit (balas angkanya saja):',
                '1. Rp 10.000',
                '2. Rp 25.000',
                '3. Rp 50.000',
                '4. Rp 100.000',
                '5. Rp 250.000',
                '6. Rp 500.000',
                '',
                $isTelegram ? __('bot.deposit_amount_custom') : 'Atau ketik nominal deposit yang kamu inginkan (minimal Rp 10.000).'
            ]),
            'buttons' => [],
            'numeric_menu' => [
                'menu' => 'deposit_amounts',
                'parent_menu' => 'menu',
                'page' => 1,
            ],
        ];
    }

    /**
     * @param string|null $source Lihat catatan di `formatPriceQuote()`.
     */
    public function formatDepositMethodPrompt(\Illuminate\Support\Collection $methods, int $amount, ?string $source = null): array
    {
        $isTelegram = $source === BotGatewayCapabilities::SOURCE_TELEGRAM;
        $amountLine = ($isTelegram ? __('bot.deposit_amount_line') : 'Jumlah: Rp :amount');

        $lines = [
            $isTelegram ? __('bot.deposit_method_title') : '💳 *Pilih Metode Pembayaran*',
            '',
            // Satu kunci dipakai ulang di respons deposit: teksnya identik, dan
            // parity test melarang dua kunci bernilai sama persis.
            str_replace(':amount', number_format($amount, 0, ',', '.'), $amountLine),
            '',
            $isTelegram ? __('bot.deposit_method_hint') : 'Silakan pilih metode pembayaran (balas angkanya saja):',
        ];

        $idx = 1;
        foreach ($methods as $method) {
            $lines[] = $idx . '. ' . $method->name;
            $idx++;
        }

        return [
            'text' => implode("
", $lines),
            'buttons' => [],
            'numeric_menu' => [
                'menu' => 'deposit_methods',
                'parent_menu' => 'deposit',
                'page' => 1,
            ],
        ];
    }
}

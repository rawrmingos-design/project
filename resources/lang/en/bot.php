<?php

/**
 * Bot copy — English.
 *
 * Kunci WAJIB identik dengan `lang/id/bot.php` (ada test parity).
 *
 * Note: idiom is kept short and neutral; these strings are sent to real users
 * in a payment flow, so avoid slang and keep amounts/placeholders verbatim.
 * Keep `*bold*` markers and emoji in the same positions as the Indonesian
 * source — the Telegram renderer depends on them.
 *
 * KEYBOARD LABELS FOLLOW THE ACTIVE LANGUAGE. `kbd_*` holds the labels the
 * reply keyboard renders, and `BotMessageFormatter::defaultReplyKeyboard()` /
 * `formatHelp()` take them from here — so any copy that names a button can
 * never point at a button that is not on screen.
 *
 * Those labels travel BACK to the bot as plain text when tapped, so every
 * variant must exist in `BotCommandParser::LABELS`. The parser has carried the
 * ID + EN variants since Phase 2, and a guard test renders the keyboard in both
 * locales and asserts the parser recognises every label — a dead button fails
 * CI instead of failing silently in a user's chat.
 *
 * WhatsApp still renders Indonesian literals (scope is locked to Telegram).
 *
 * Labels that read the same in both languages (`🏆 Leaderboard`, `💰 Deposit`)
 * stay single literals in the formatter on purpose — duplicating identical
 * values would only force them onto the parity allow-list.
 */
return [
    // --- Greeting (used by menu & help) ---
    'intro_welcome' => '👋 *Welcome to :store*',
    'intro_tagline' => 'Premium games and apps, all in one place.',

    // --- Main menu ---
    'menu_title' => '🏠 *Main Menu*',
    'menu_pick_category' => 'Pick a category below to get started. 👇',
    // The Main Menu screen is a NUMBERED LIST, not a greeting block.
    // `menu_title` & `menu_pick_category` are kept on purpose: `storeIntro()` is
    // still used by `/start`, and removing keys risks leaking raw key literals
    // to any path that has not been migrated yet.
    'menu_list_title' => 'PRODUCT LIST',
    'menu_page_footer' => '📄 Page :page / :total',
    'menu_item_numbered' => '[:number]. :name',
    'menu_categories_unavailable' => 'Sorry, the category list is unavailable right now.',
    'menu_category_fallback' => 'Category',

    // --- Service screen: service package cards ---
    // Same box pattern as the storefront order page, so users who have ordered
    // on the web recognise the layout immediately.
    //
    // The box rules use the light box-drawing character (U+2500), NOT a hyphen.
    // `TelegramMarkdown::fromLegacy()` escapes `-`, so a hyphen rule would go
    // out as `\-` and render broken in chat. Box characters are not MarkdownV2
    // special characters.
    'service_package_title' => '╭───────────────',
    'service_card_name' => '┊ :paket',
    'service_card_spacer' => '┊',
    'service_card_item' => '┊・Service : :nama',
    'service_card_price' => '┊・Price : Rp :harga',
    'service_card_more' => '┊・and :jumlah more',
    'service_card_footer' => '╰───────────────',
    'service_list_title' => '💎 *:produk*',
    'service_list_footer_packages' => 'Type a number to see what a package contains.',
    'service_list_footer_items' => 'Type a number to pick a service.',
    'service_item_numbered' => '[:number]. :nama — Rp :harga',
    'nav_page_prompt' => 'Switch page: ⬅️ / ➡️',

    // --- Payment method screen (Telegram) ---
    // The list lives in TEXT, not in buttons: Telegram allows only ONE
    // `reply_markup` per message and the numeric keyboard wins here. The list
    // used to live only in inline buttons, which is why the screen rendered
    // empty ("💳 Pilih Pembayaran" with no method at all).
    'payment_list_title' => '💳 *Choose Payment*',
    'payment_list_service' => '💎 :nama',
    'payment_list_group' => '_:grup_',
    // Cost is spelled out as "(fee admin)": a bare number after the method name
    // does not explain what the charge is.
    'payment_list_free' => ' (fee admin: Rp 0)',
    'payment_list_fee' => ' (fee admin: Rp :fee)',
    'payment_list_fee_later' => ' (fee admin: calculated on the next step)',

    // --- Help /help ---
    'help_title' => '📖 Quick Guide',
    'help_order_title' => '🛒 How to Order',
    'help_order_step_1' => '1. Tap *:menu*',
    'help_order_step_2' => '2. Choose a service, then pick the amount',
    'help_order_step_3' => '3. Enter your contact details for the payment receipt',
    'help_order_step_4' => '4. Choose a payment method, then complete the payment',
    'help_manage_title' => '🔎 Check & Manage',
    'help_manage_status' => '• *:status* — latest order status',
    'help_manage_history' => '• *:history* — your order list',

    // --- Deposit (Task 1.4) ---
    // Amounts keep the Indonesian numeric convention (`Rp 10.000`) on purpose:
    // the figure must look identical to what the user is actually charged.
    'deposit_unavailable' => 'Deposits are not available through this gateway yet.',
    'deposit_rate_limited' => 'Too many deposit attempts. Please try again shortly.',
    'deposit_amount_title' => '💰 *Choose Deposit Amount*',
    'deposit_amount_hint' => 'Please choose a deposit amount (reply with the number):',
    'deposit_amount_custom' => 'Or type the deposit amount you want (minimum Rp 10.000).',
    'deposit_amount_invalid' => 'Invalid amount. Pick a number 1-6 or type an amount of at least 10000 (e.g. 15000).',
    'deposit_method_title' => '💳 *Choose Payment Method*',
    'deposit_method_hint' => 'Please choose a payment method (reply with the number):',
    'deposit_method_invalid' => 'Invalid payment method choice. Please reply with a matching number (e.g. 1).',
    'deposit_no_methods' => 'There are currently no payment methods available for deposits.',
    'deposit_pending_title' => '*⏳ DEPOSIT AWAITING PAYMENT*',
    'deposit_order_id' => 'Order ID: `:order_id`',
    'deposit_amount_line' => 'Amount: Rp :amount',
    'deposit_va_line' => 'Payment Code / VA: `:code`',
    // Invoice buttons & lines (Telegram path). `deposit_pending_title` is not
    // reused: the word order differs in English, and identical values are
    // rejected by the parity test.
    'invoice_pending_title' => '⏳ *Awaiting Payment*',
    'invoice_va_line' => '💳 Payment Code / VA: `:code`',
    'invoice_qr_hint' => 'Scan the QRIS code to pay.',
    'invoice_pay_hint' => 'Complete the payment so the order is processed automatically.',
    'invoice_status_hint' => 'Type `status` to check the payment.',
    'invoice_btn_check' => '🔎 Check Payment Status',
    'invoice_create_failed' => 'Failed to create the invoice: :reason',
    // Catalog titles & empty states. Identical values are rejected by the
    // parity test, so each key stands on its own.
    'catalog_products_title' => '🎮 *Choose a Game*',
    'catalog_products_empty' => 'Category not found or it has no products yet.',
    'catalog_services_empty' => 'Product not found or it has no services yet.',
    'catalog_payments_empty' => 'No payment methods are available right now.',
    'leaderboard_today' => 'Today',
    'leaderboard_week' => 'This Week',
    'leaderboard_month' => 'This Month',
    'leaderboard_empty' => 'No successful transactions yet.',
    'usage_kategori' => 'Wrong format. Use: `kategori <category_code>`' . "\n" . 'Example: `kategori top-up-games`',
    'usage_layanan' => 'Wrong format. Use: `layanan <product_code>`',
    'usage_pembayaran' => 'Wrong format. Pick a service first.',
    'usage_harga' => 'Wrong format. Use: `harga <service_id> <payment_code>`',
    'usage_cekid' => 'Wrong format. Use: `cekid <product_code> <uid> [zone]`' . "\n" . 'Example: `cekid mobile-legends 1234567 1234`',
    'tg_register_prompt_title' => '⚠️ *Telegram account not linked yet.*',
    'tg_register_prompt_body' => 'To make a deposit, you need to create an account first.',
    'tg_register_prompt_confirm' => 'Type *YES* to register now, or *NO* to cancel.',
    'tg_register_username_title' => '📝 *Account Registration*',
    'tg_register_username_prompt' => 'Type the username you want to use.',
    'tg_register_username_example' => '_Example: fahmi123_',
    'tg_register_username_note' => '_Note: letters and numbers only, no spaces (4-20 characters)._',
    'tg_register_username_taken' => 'That username is already taken. Please pick another one.',
    'tg_register_username_invalid' => 'Invalid username. Letters and numbers only, no spaces (4-20 characters).',
    'tg_register_username_retry' => 'Type a new username. (Attempts left: :left)',
    'tg_register_email_title' => '✅ *Username accepted.*',
    'tg_register_email_prompt' => 'Want to add an email? Send your email address, or type *SKIP* to skip.',
    'tg_register_email_note' => '_Email is optional and can be added later on the website._',
    'tg_register_email_duplicate' => 'That email is already used by another account.',
    'tg_register_email_invalid' => 'Invalid email format.',
    'tg_register_email_retry' => 'Try another email, or type *SKIP* to skip. (Attempts left: :left)',
    'tg_register_success_title' => '🎉 *Account created and linked to Telegram!*',
    'tg_register_success_note' => '⚠️ _Save this password now, it will not be sent again._',
    'tg_register_success_retry' => 'Run the *deposit* command again to continue.',
    'btn_back' => '🔙 Back',
    'deposit_qr_sent' => 'The payment QR is sent as an image after this message.',
    'deposit_pay_url' => 'Use the following payment URL: :url',
    'deposit_create_failed' => 'The deposit could not be created. Please try again later.',
    'deposit_session_invalid' => 'Invalid session. Please restart the deposit.',
    'deposit_message_id_invalid' => 'The message has no valid ID. Please resend the deposit command.',
    'help_manage_checkid' => '• *:cekid* — verify the account name first',
    'help_manage_cancel' => '• *:cancel* — cancel an unpaid order',
    'help_manage_deposit' => '• *:deposit* — top up your balance first',
    'help_help_title' => '❓ Need Help?',
    'help_admin_link' => 'Tap the link [💬 Click here](:url), or type /admin to open the admin contact. 🙏',
    'help_admin_no_link' => 'Type /admin to reach the admin if you run into trouble. 🙏',

    // --- Checkout: price quote & confirmation ---
    // Column padding is kept so totals still line up. `Rp` stays — it is the
    // store's currency, not a language.
    'checkout_title' => '🧾 *Order Summary*',
    'checkout_price' => 'Price       Rp :amount',
    'checkout_discount' => 'Discount    -Rp :amount',
    'checkout_admin_fee' => 'Fee         Rp :amount',
    'checkout_total' => '*Total      Rp :amount*',
    'checkout_default_payment' => 'Payment',
    // The typed command stays in its canonical form: `invoice` and the
    // positional argument order are command dialect, not prose. Only the
    // surrounding words are translated.
    'checkout_send_command' => 'Send: `invoice :service :method <UID> [Zone_ID]`',
    'checkout_example_command' => 'Example: `invoice :service :method 1234567 1234`',

    'checkout_confirm_expiry' => 'This confirmation is valid for 15 minutes.',
    'checkout_btn_confirm' => '✅ Confirm',
    'checkout_btn_cancel' => '❌ Cancel',
    'checkout_btn_back' => '🔙 Back',
    'checkout_invalid_format' => 'That ID format does not look right.',

    // --- Checkout: destination input lines ---
    'checkout_input_title' => '🎮 *Enter :label*',
    'checkout_input_title_email' => '📧 *Enter :label*',
    'checkout_input_example_uid' => 'Example: `12345`',
    'checkout_input_example_zone' => 'Example: `12345 6789`',
    'checkout_input_example_email' => 'Example: `you@email.com`',
    'checkout_input_zone_options' => 'Choose :label:',

    // --- Check ID ---
    // Clearly separates "your ID is wrong" from "the provider is down" — the
    // first is the user's mistake, the second is not.
    'checkid_valid_title' => '✅ *Valid ID*',
    'checkid_unavailable' => 'ID validation is unavailable right now. Please try again shortly.',
    'checkid_invalid' => 'Invalid ID: :message',
    'checkid_skip' => 'This product does not need ID validation.',
    // --- Order status & transaction list (Task 1.5) ---
    // Telegram-path only; the WhatsApp path and the queued order-status
    // listener keep their Indonesian literals in the class on purpose.
    // Amounts keep the Indonesian numeric convention in both languages.
    'status_check_failed' => 'Could not check the status: :message',
    'status_complete_title' => '✅ *Top Up Successful!*',
    'status_paid_title' => '✅ *Payment Successful*',
    'status_complete_body' => 'Your order has been processed and delivered to your account 🎉',
    'status_paid_body' => 'Your order has been received and is being processed.',
    'status_paid_note' => 'We will notify you once the top up is complete.',
    'status_thanks' => 'Thank you for shopping at *:store*.',
    'status_more' => 'Need something else? Browse our catalog any time.',
    'status_invoice_missing' => 'Invoice not found',
    'active_orders_title' => '📦 *Active Orders*',
    'active_orders_hint' => 'Type `status <invoice>` for details.',
    'label_awaiting_payment' => 'Awaiting Payment',
    'status_generic_title' => '*Order Status*',
    'status_generic_order_id' => 'Order ID: :order_id',
    'status_generic_product' => 'Product: :product (:nickname)',
    'status_generic_total' => 'Total: Rp :amount',
    'status_generic_payment' => 'Payment Status: *:status*',
    'status_generic_order' => 'Order Status: *:status*',
    'status_generic_sn' => '*SN / Note:*',
    'status_unpaid_title' => '⏳ *Awaiting Payment*',
    'status_unpaid_amount' => '💰 *Rp :amount*',
    'status_unpaid_method' => '💳 Method: *:method*',
    'status_unpaid_check' => 'Type `status` to check the payment.',
    'status_expired_title' => '❌ *Payment Expired*',
    'status_expired_body' => 'Please place a new order.',
    'status_no_orders' => 'You have no transactions yet. Type *menu* to start topping up 🛍️',
    'status_usage' => 'Wrong format. Use: `status <order_id>` — or type `status` alone to check your latest order.',

    'label_paid' => 'Paid',
    'label_unpaid' => 'Unpaid',
    'label_expired' => 'Expired',
    'label_success' => 'Success',
    'label_failed' => 'Failed',
    'label_processing' => 'Processing',
    'sender_list_title' => '📦 *Your Transactions*',
    'sender_list_product_fallback' => 'Product',
    'sender_list_pagination' => 'Showing page :page of :pages · :total transactions total.',
    'sender_list_hint' => 'Type `status <invoice>` for details, or tap its number.',

    // --- Order history (Task 1.5) ---
    'history_rate_limited' => 'Too many history requests. Please try again shortly.',
    'history_telegram_not_linked' => 'Order history is not available yet. Link your Telegram account from Settings first.',
    'history_invalid' => 'This history is expired or invalid. Open the latest history.',
    'history_load_latest' => '📜 Load Latest History',
    'history_empty_title' => '📦 *ORDER HISTORY*',
    'history_empty_body' => 'There are no orders to show for this account yet.',
    'history_title' => '📦 *Order History*',
    'history_detail_btn' => 'Details #:number',
    'history_detail_missing' => 'Order not found or cannot be displayed.',
    'history_detail_title' => '🧾 *ORDER DETAIL*',
    'history_detail_invoice' => 'Invoice: `:order_id`',
    'history_detail_product' => 'Product: :product',
    'history_detail_total' => 'Total: Rp :amount',
    'history_detail_date' => 'Date: :date',
    'history_detail_status' => 'Order Status: :status',
    'history_detail_payment_status' => 'Payment Status: :status',
    'history_detail_game_id' => 'Game ID: :game_id',

    // --- Shared buttons ---
    // Callback-driven labels: the bot receives the `callback` value, not the
    // label text, so translating these is safe. Parser-driven labels are safe
    // too since Phase 2 — see the `kbd_*` block below.
    'btn_back_menu' => '🔙 Back to Menu',
    'btn_back_history' => '📜 Back to History',
    'btn_prev' => '⬅️ Previous',
    'btn_next' => 'Next ➡️',

    // --- Reply keyboard labels (`defaultReplyKeyboard`) ---
    // Tapping these sends the LABEL back as text, so the parser must know every
    // variant. It has carried all of these since Phase 2, which is what makes
    // translating the keyboard safe: the old Indonesian labels stay registered,
    // so buttons still sitting in users' chat history keep working.
    'kbd_menu' => '🛍️ Open Menu',
    'kbd_status' => '📦 Check Status',
    'kbd_cekid' => '🔍 Check Game ID',
    'kbd_help' => '❓ Help',
    'kbd_cancel' => '❌ Cancel Order',
    'kbd_history' => '📜 Order History',
    'kbd_placeholder' => 'Choose an action...',
    // Gate button labels. Sent as CALLBACKS (not parser labels — see
    // `test_label_callback_driven_bukan_perintah_teks`), so they may follow
    // the active language. They used to be Indonesian literals, which put an
    // Indonesian button on an English screen — right at the gate that blocks
    // the user.
    'kbd_gate_verified' => '✅ Joined',
    // --- Membership gate & verification greeting (Task 1.6) ---
    // Button labels quoted here come from `kbd_*`, so they always match what the
    // reply keyboard actually renders — in whatever language is active.
    // (`✅ Sudah Bergabung` and `Coba Lagi` are inline/callback-driven, so their
    // labels never travel back as text — only the echoed prose is quoted here.)
    'gate_title' => '🔒 *Limited Access*',
    'gate_intro_single' => 'Hi! Before you can use this bot, please join the channel below first:',
    'gate_intro_multi' => 'Hi! Before you can use this bot, please join *all* of the channels below first:',
    // Button name filled from `kbd_gate_verified` (active language). The
    // literal here used to tell English users to tap an Indonesian button.
    'gate_verify_hint' => 'Already joined? Tap *:gate_verified* below to verify.',
    'gate_join_channel' => '📢 Join :label',
    'gate_verified_title' => '✅ *Verification Successful*',
    'gate_verified_hello' => 'Hi :name! ',
    'gate_verified_body' => 'Your membership is verified. You can now use every feature of this bot.',
    'gate_verified_hint' => 'Tap *:menu* to start shopping, or *:help* to read the guide.',
    'gate_maintenance_title' => '🛠️ *Service Under Maintenance*',
    'gate_maintenance_body' => 'Sorry, channel membership verification cannot run right now.' . "\n"
        . 'This is on our side, not because you have not joined.' . "\n\n"
        . 'We have reported it to the admin. Please try again later,' . "\n"
        . 'or contact the admin if you need help right away.',
    'gate_unavailable_title' => '*Membership Verification Problem*',
    'gate_unavailable_body' => 'Your channel membership could not be verified. Please try again in a moment.',
    'gate_btn_contact' => '💬 Contact Admin',
    'gate_btn_retry' => 'Try Again',
    // --- Language settings (Task 3.1) ---
    'lang_title' => '🌐 *Language Settings*',
    'lang_current' => 'Current language: *:label*',
    'lang_pick' => 'Pick the language you want. It is saved and used for all following messages.',
    'lang_set_ok' => '✅ Language switched to *:label*.',
    'lang_already' => 'Your language is already *:label*.',
    'help_manage_language' => '• *🇮🇩 Indonesian* / *🇬🇧 English* — change the bot language. The buttons are on the keyboard below.',

    // --- Failed order + language hint in group welcome (Task 4.x) ---
    // A Failed order whose payment was paid previously fell into the "Payment
    // Received / processing" branch — the user was told the order was moving
    // ahead while the provider had already reported failure.
    'status_failed_title' => '❌ *Order Failed*',
    'status_failed_body' => 'Your payment was received, but the order *could not be processed* by the service provider.',
    'status_failed_note' => 'Your money will be refunded. Contact admin if it has not arrived within 24 hours.',
    // Group welcomes have no per-user language (no private chat to seed
    // `language_code` from), so the language escape hatch is announced here.
    // Double-quoted: single-quoted PHP does not interpret \n, so `'\n\n'`
    // would show up literally as "\n\n" in the chat.
    'welcome_language_hint' => "\n\n🌐 *Change language?* Open this bot's private chat, then tap *:id* or *:en* — or type /bahasa.",
];


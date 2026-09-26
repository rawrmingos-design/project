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
 * BUTTON LABELS ARE INTENTIONALLY LEFT IN INDONESIAN (`🛍️ Buka Menu`,
 * `📦 Cek Status`, …). Button text is echoed inside the help copy, and tapping
 * a button sends that exact string back to the bot. Until the multi-language
 * parser (Phase 2) can recognise both variants, translating a label here would
 * silently break the button. Copy around the label is translated; the label
 * itself is not.
 */
return [
    // --- Greeting (used by menu & help) ---
    'intro_welcome' => '👋 *Welcome to :store*',
    'intro_tagline' => 'Premium games and apps, all in one place.',

    // --- Main menu ---
    'menu_title' => '🏠 *Main Menu*',
    'menu_pick_category' => 'Pick a category below to get started. 👇',
    'menu_categories_unavailable' => 'Sorry, the category list is unavailable right now.',
    'menu_category_fallback' => 'Category',

    // --- Help /help ---
    'help_title' => '📖 Quick Guide',
    'help_order_title' => '🛒 How to Order',
    'help_order_step_1' => '1. Tap *🛍️ Buka Menu*',
    'help_order_step_2' => '2. Choose a service, then pick the amount',
    'help_order_step_3' => '3. Enter your contact details for the payment receipt',
    'help_order_step_4' => '4. Choose a payment method, then complete the payment',
    'help_manage_title' => '🔎 Check & Manage',
    'help_manage_status' => '• *📦 Cek Status* — latest order status',
    'help_manage_history' => '• *📜 Riwayat Order* — your order list',
    'help_manage_checkid' => '• *🔍 Cek ID Game* — verify the account name first',
    'help_manage_cancel' => '• *❌ Batal Transaksi* — cancel an unpaid order',
    'help_manage_deposit' => '• *💰 Deposit* — top up your balance first',
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
];

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
];

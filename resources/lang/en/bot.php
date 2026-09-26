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
    'deposit_qr_sent' => 'The payment QR is sent as an image after this message.',
    'deposit_pay_url' => 'Use the following payment URL: :url',
    'deposit_create_failed' => 'The deposit could not be created. Please try again later.',
    'deposit_session_invalid' => 'Invalid session. Please restart the deposit.',
    'deposit_message_id_invalid' => 'The message has no valid ID. Please resend the deposit command.',
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
    // label text, so translating these is safe. Parser-driven labels (`YA`,
    // `TIDAK`, `❌ Batal Transaksi`) stay Indonesian until Phase 2.
    'btn_back_menu' => '🔙 Back to Menu',
    'btn_back_history' => '📜 Back to History',
    'btn_prev' => '⬅️ Previous',
    'btn_next' => 'Next ➡️',
];

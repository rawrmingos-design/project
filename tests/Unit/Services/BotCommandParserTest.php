<?php

namespace Tests\Unit\Services;

use App\Services\Bot\BotCommandParser;
use Tests\TestCase;

class BotCommandParserTest extends TestCase
{
    private BotCommandParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BotCommandParser();
    }

    public function test_parses_slash_command_with_args(): void
    {
        $result = $this->parser->parse('/menu');
        $this->assertSame('menu', $result['command']);
        $this->assertSame([], $result['args']);

        $result = $this->parser->parse('/layanan mobile-legends');
        $this->assertSame('layanan', $result['command']);
        $this->assertSame(['mobile-legends'], $result['args']);
    }

    public function test_parses_command_without_slash(): void
    {
        $result = $this->parser->parse('harga 123 QRIS');
        $this->assertSame('harga', $result['command']);
        $this->assertSame(['123', 'QRIS'], $result['args']);
    }

    public function test_lowercases_command(): void
    {
        $result = $this->parser->parse('MENU');
        $this->assertSame('menu', $result['command']);
    }

    public function test_empty_string_returns_null_command(): void
    {
        $result = $this->parser->parse('');
        $this->assertNull($result['command']);
        $this->assertSame([], $result['args']);
    }

    public function test_reply_keyboard_alias_buka_menu(): void
    {
        $result = $this->parser->parse('🛍️ Buka Menu');
        $this->assertSame('menu', $result['command']);
        $this->assertSame([], $result['args']);
    }

    public function test_reply_keyboard_alias_cek_status(): void
    {
        $result = $this->parser->parse('🔎 Cek Status');
        $this->assertSame('status', $result['command']);
        $this->assertSame([], $result['args']);
    }

    public function test_reply_keyboard_alias_bantuan(): void
    {
        $result = $this->parser->parse('❓ Bantuan');
        $this->assertSame('help', $result['command']);
        $this->assertSame([], $result['args']);
    }

    public function test_reply_keyboard_alias_batal_transaksi(): void
    {
        $result = $this->parser->parse('❌ Batal Transaksi');
        $this->assertSame('batal', $result['command']);
        $this->assertSame([], $result['args']);
    }

    public function test_reply_keyboard_alias_cek_id_game(): void
    {
        $result = $this->parser->parse('🔍 Cek ID Game');
        $this->assertSame('cekid', $result['command']);
        $this->assertSame([], $result['args']);
    }

    public function test_reply_keyboard_alias_cek_status_new_emoji(): void
    {
        $result = $this->parser->parse('📦 Cek Status');
        $this->assertSame('status', $result['command']);
        $this->assertSame([], $result['args']);
    }

    public function test_reply_keyboard_alias_hubungi_admin(): void
    {
        $result = $this->parser->parse('📞 Hubungi Admin');
        $this->assertSame('admin', $result['command']);
        $this->assertSame([], $result['args']);
    }

    public function test_partial_alias_is_not_translated(): void
    {
        // Hanya teks yang persis sama yang diterjemahkan, bukan substring parsial
        $result = $this->parser->parse('Buka Menu');
        $this->assertSame('buka', $result['command']);
        $this->assertSame(['Menu'], $result['args']); // args tidak di-lowercase
    }

    public function test_multiple_spaces_collapsed(): void
    {
        $result = $this->parser->parse('invoice  1   QRIS  uid123');
        $this->assertSame('invoice', $result['command']);
        $this->assertSame(['1', 'QRIS', 'uid123'], $result['args']);
    }
}

<?php

namespace Tests\Unit\Support;

use App\Support\PembelianStatus;
use Tests\TestCase;

/**
 * Unit tests for PembelianStatus alias coverage and roundtrip consistency.
 *
 * Ensures that:
 * (1) All known DB status values are covered by aliasesFor()
 * (2) preferredDatabaseLabel() roundtrips correctly
 * (3) normalize() handles all known variants including edge cases
 */
class PembelianStatusAliasTest extends TestCase
{
    // ── Tests: alias coverage ─────────────────────────────────────────────────

    public function test_success_aliases_cover_all_known_db_values(): void
    {
        $aliases = PembelianStatus::aliasesFor(PembelianStatus::SUCCESS);

        $this->assertContains('Sukses', $aliases, "'Sukses' must be a SUCCESS alias (primary DB label)");
        $this->assertContains('Success', $aliases, "'Success' must be a SUCCESS alias (legacy/provider variant)");
    }

    public function test_failed_aliases_cover_all_known_db_values(): void
    {
        $aliases = PembelianStatus::aliasesFor(PembelianStatus::FAILED);

        $this->assertContains('Gagal', $aliases,   "'Gagal' must be a FAILED alias");
        $this->assertContains('Failed', $aliases,  "'Failed' must be a FAILED alias");
        $this->assertContains('Error', $aliases,   "'Error' must be a FAILED alias");
    }

    public function test_cancelled_aliases_cover_all_known_db_values(): void
    {
        $aliases = PembelianStatus::aliasesFor(PembelianStatus::CANCELLED);

        $this->assertContains('Batal', $aliases,     "'Batal' must be a CANCELLED alias");
        $this->assertContains('Cancelled', $aliases, "'Cancelled' must be a CANCELLED alias");
    }

    public function test_expired_aliases_cover_all_known_db_values(): void
    {
        $aliases = PembelianStatus::aliasesFor(PembelianStatus::EXPIRED);

        $this->assertContains('Expired', $aliases, "'Expired' must be an EXPIRED alias");
        $this->assertContains('expired', $aliases, "'expired' must be an EXPIRED alias");
    }

    public function test_pending_aliases_cover_pending(): void
    {
        $aliases = PembelianStatus::aliasesFor(PembelianStatus::PENDING);

        $this->assertContains('Pending', $aliases, "'Pending' must be a PENDING alias");
    }

    // ── Tests: preferredDatabaseLabel roundtrip ───────────────────────────────

    public function test_preferred_database_label_roundtrip_success(): void
    {
        $label = PembelianStatus::preferredDatabaseLabel(PembelianStatus::SUCCESS);

        $this->assertEquals('Sukses', $label,
            'Preferred DB label for SUCCESS should be Sukses (Indonesian canonical)');

        // Roundtrip: normalize back
        $this->assertEquals(PembelianStatus::SUCCESS, PembelianStatus::normalize($label));
    }

    public function test_preferred_database_label_roundtrip_failed(): void
    {
        $label = PembelianStatus::preferredDatabaseLabel(PembelianStatus::FAILED);

        $this->assertEquals('Gagal', $label,
            'Preferred DB label for FAILED should be Gagal');

        $this->assertEquals(PembelianStatus::FAILED, PembelianStatus::normalize($label));
    }

    public function test_preferred_database_label_roundtrip_pending(): void
    {
        $label = PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING);

        $this->assertEquals('Pending', $label);
        $this->assertEquals(PembelianStatus::PENDING, PembelianStatus::normalize($label));
    }

    public function test_preferred_database_label_roundtrip_cancelled(): void
    {
        $label = PembelianStatus::preferredDatabaseLabel(PembelianStatus::CANCELLED);

        $this->assertEquals('Batal', $label,
            'Preferred DB label for CANCELLED should be Batal');

        $this->assertEquals(PembelianStatus::CANCELLED, PembelianStatus::normalize($label));
    }

    public function test_preferred_database_label_roundtrip_expired(): void
    {
        $label = PembelianStatus::preferredDatabaseLabel(PembelianStatus::EXPIRED);

        $this->assertEquals('Expired', $label,
            'Preferred DB label for EXPIRED should be Expired');

        $this->assertEquals(PembelianStatus::EXPIRED, PembelianStatus::normalize($label));
    }

    // ── Tests: normalize() handles all known variants ─────────────────────────

    /** @dataProvider knownStatusVariantsProvider */
    public function test_normalize_handles_all_known_db_variants(
        string $raw,
        string $expectedCanonical
    ): void {
        $this->assertEquals(
            $expectedCanonical,
            PembelianStatus::normalize($raw),
            "normalize('{$raw}') should return '{$expectedCanonical}'"
        );
    }

    public static function knownStatusVariantsProvider(): array
    {
        return [
            // SUCCESS variants
            'Sukses'    => ['Sukses',   PembelianStatus::SUCCESS],
            'Success'   => ['Success',  PembelianStatus::SUCCESS],

            // FAILED variants
            'Gagal'     => ['Gagal',    PembelianStatus::FAILED],
            'Failed'    => ['Failed',   PembelianStatus::FAILED],
            'Error'     => ['Error',    PembelianStatus::FAILED],

            // CANCELLED variants
            'Batal'     => ['Batal',    PembelianStatus::CANCELLED],
            'Cancelled' => ['Cancelled', PembelianStatus::CANCELLED],
            'Canceled'  => ['Canceled', PembelianStatus::CANCELLED],

            // EXPIRED
            'Expired'   => ['Expired',   PembelianStatus::EXPIRED],

            // PENDING
            'Pending'   => ['Pending',  PembelianStatus::PENDING],

            // PROCESSING
            'Processing' => ['Processing', PembelianStatus::PROCESSING],
            'Proses'     => ['Proses',     PembelianStatus::PROCESSING],
        ];
    }

    public function test_normalize_null_returns_unknown(): void
    {
        $this->assertEquals(PembelianStatus::UNKNOWN, PembelianStatus::normalize(null));
    }

    public function test_normalize_empty_string_returns_unknown(): void
    {
        $this->assertEquals(PembelianStatus::UNKNOWN, PembelianStatus::normalize(''));
    }

    public function test_normalize_garbage_returns_unknown(): void
    {
        $this->assertEquals(PembelianStatus::UNKNOWN, PembelianStatus::normalize('some-random-provider-status-xyz'));
    }

    // ── Tests: aliasesFor() consistency with normalize() ─────────────────────

    public function test_all_success_aliases_normalize_back_to_success(): void
    {
        foreach (PembelianStatus::aliasesFor(PembelianStatus::SUCCESS) as $alias) {
            $this->assertEquals(
                PembelianStatus::SUCCESS,
                PembelianStatus::normalize($alias),
                "Alias '{$alias}' should normalize to SUCCESS"
            );
        }
    }

    public function test_all_failed_aliases_normalize_back_to_failed(): void
    {
        foreach (PembelianStatus::aliasesFor(PembelianStatus::FAILED) as $alias) {
            $this->assertEquals(
                PembelianStatus::FAILED,
                PembelianStatus::normalize($alias),
                "Alias '{$alias}' should normalize to FAILED"
            );
        }
    }

    public function test_all_expired_aliases_normalize_back_to_expired(): void
    {
        foreach (PembelianStatus::aliasesFor(PembelianStatus::EXPIRED) as $alias) {
            $this->assertEquals(
                PembelianStatus::EXPIRED,
                PembelianStatus::normalize($alias),
                "Alias '{$alias}' should normalize to EXPIRED"
            );
        }
    }
}

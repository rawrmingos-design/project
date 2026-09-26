<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Preferensi bahasa bot per percakapan (`external_user_id`).
 *
 * SENGAJA TANPA trait `BelongsToTenant`. Trait itu memasang global scope +
 * auto-isi `tenant_id` saat creating; kalau `tenant_id` terisi dari konteks
 * webhook, unique(source, external_user_id) bisa pecah jadi dua baris per user
 * di tenant berbeda → preferensi bahasa jadi tidak deterministik.
 */
class BotLocalePreference extends Model
{
    public const SOURCE_EXPLICIT = 'explicit';
    public const SOURCE_DETECTED = 'detected';
    public const SOURCE_PANEL = 'panel';

    protected $fillable = [
        'source',
        'external_user_id',
        'locale',
        'locale_source',
    ];
}

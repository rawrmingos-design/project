<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class SeoRoutePolicy
{
    public const INDEXABLE_ROBOTS = 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1';
    public const PRIVATE_ROBOTS = 'noindex,nofollow,noarchive';

    private const PRIVATE_PATTERNS = [
        'id/sign-in',
        'id/sign-up',
        'id/forgot-password',
        'id/reset-password*',
        'id/dashboard*',
        'id/settings*',
        'id/deposit*',
        'id/affiliate*',
        'id/withdrawal*',
        'id/invoices*',
        'id/track*',
        'id/search*',
        'id/reseller/registry*',
        'id/reseller/dashboard*',
        'id/reseller/settings*',
        'id/reseller/deposit-methods*',
        'id/reseller/credentials*',
        'id/reseller/callbacks*',
        'id/reseller/orders*',
        'id/reseller/deposits*',
        'id/reseller/sandbox*',
        'id/reseller/notifications*',
    ];

    public static function robots(?Request $request = null): string
    {
        $path = trim(($request ?? request())->path(), '/');

        foreach (self::PRIVATE_PATTERNS as $pattern) {
            if (self::matches($path, $pattern)) {
                return self::PRIVATE_ROBOTS;
            }
        }

        return self::INDEXABLE_ROBOTS;
    }

    public static function isIndexable(?Request $request = null): bool
    {
        return self::robots($request) === self::INDEXABLE_ROBOTS;
    }

    private static function matches(string $path, string $pattern): bool
    {
        return Str::is($pattern, $path);
    }
}


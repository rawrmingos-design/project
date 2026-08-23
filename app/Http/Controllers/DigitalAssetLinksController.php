<?php

namespace App\Http\Controllers;

use App\Services\PublicSiteConfigService;
use Illuminate\Http\Response;

/**
 * Serves /.well-known/assetlinks.json for Android TWA (Digital Asset Links).
 *
 * Statement is dynamic: reads android_package_id + android_cert_fingerprints
 * from setting_webs so each deployment (istanatopup, egymarket, ...) serves
 * its own values without code changes. Falls back to the static file in
 * public/.well-known/ when the DB columns are empty.
 */
class DigitalAssetLinksController extends Controller
{
    public function __invoke(PublicSiteConfigService $siteConfigService): Response
    {
        $settings = $siteConfigService->getSettings();

        $packageId = trim((string) ($settings->android_package_id ?? ''));
        $fingerprintsJson = trim((string) ($settings->android_cert_fingerprints ?? ''));

        if ($packageId !== '' && $fingerprintsJson !== '') {
            $fingerprints = json_decode($fingerprintsJson, true);
            $valid = array_values(array_filter(
                is_array($fingerprints) ? $fingerprints : [],
                fn ($f): bool => is_string($f) && preg_match('/^[0-9A-Fa-f:]{95}$/', trim($f)) === 1,
            ));

            if ($valid !== []) {
                return response(
                    json_encode([
                        [
                            'relation' => ['delegate_permission/common.handle_all_urls'],
                            'target' => [
                                'namespace' => 'android_app',
                                'package_name' => $packageId,
                                'sha256_cert_fingerprints' => array_map(
                                    static fn (string $f): string => strtoupper(str_replace(' ', '', trim($f))),
                                    $valid,
                                ),
                            ],
                        ],
                    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n",
                    200,
                )->header('Content-Type', 'application/json');
            }
        }

        // Fallback: static file shipped with the deployment.
        $path = public_path('.well-known/assetlinks.json');

        if (! is_file($path)) {
            abort(404);
        }

        return response((string) file_get_contents($path), 200)
            ->header('Content-Type', 'application/json');
    }
}

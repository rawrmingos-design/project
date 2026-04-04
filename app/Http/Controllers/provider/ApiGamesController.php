<?php

namespace App\Http\Controllers\provider;

use App\Http\Controllers\Controller;
use App\Services\Providers\ApiGamesService;

class ApiGamesController extends Controller
{
    private ApiGamesService $service;

    public function __construct(array $config = [])
    {
        $this->service = new ApiGamesService($config);
    }

    public function order($uid = null, $zone = null, $service = null, $order_id = null): array
    {
        return $this->service->order($uid, $zone, $service, $order_id);
    }

    public function status($poid): array
    {
        return $this->service->status($poid);
    }

    public function verifyWebhookSignature(string $refId, ?string $signature): bool
    {
        return $this->service->verifyWebhookSignature($refId, $signature);
    }

    public static function normalizeStatusMeta(?string $status): array
    {
        return ApiGamesService::normalizeStatusMeta($status);
    }
}

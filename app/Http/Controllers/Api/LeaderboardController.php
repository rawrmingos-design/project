<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LeaderboardService;

class LeaderboardController extends Controller
{
    public function index(LeaderboardService $leaderboard): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $leaderboard->rankings(),
        ]);
    }
}

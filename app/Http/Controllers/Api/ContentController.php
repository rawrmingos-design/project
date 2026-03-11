<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SettingWeb;

class ContentController extends Controller
{
    public function show($slug)
    {
        $config = SettingWeb::first();

        $content = match($slug) {
            'terms' => [
                'title' => 'Terms and Conditions',
                'body' => "Welcome to {$config->judul_web} Shop! These terms and conditions outline the rules and regulations for the use of {$config->judul_web} Website...",
            ],
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'body' => "Privacy Policy for {$config->judul_web}...",
            ],
            default => null,
        };

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $content
        ]);
    }
}

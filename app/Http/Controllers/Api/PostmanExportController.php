<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class PostmanExportController extends Controller
{
    /**
     * Export Postman collection
     */
    public function collection(Request $request): Response
    {
        $baseUrl = config('app.url');
        
        $collection = [
            "info" => [
                "_postman_id" => "h2h-api-collection-v1",
                "name" => "H2H API - Elite Reseller",
                "description" => "Comprehensive API collection for H2H integration.\n\n## Quick Start\n1. Import this collection to Postman\n2. Set your API keys in collection variables\n3. Start testing with Sandbox endpoints\n\n## Authentication\nAll endpoints require Bearer token.",
                "schema" => "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
            ],
            "item" => [
                [
                    "name" => "Production API",
                    "item" => [
                        [
                            "name" => "1. Balance Inquiry",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    ["key" => "Content-Type", "value" => "application/json"],
                                ],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => "{}"
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/api/v1/balance",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["api", "v1", "balance"]
                                ]
                            ]
                        ],
                        [
                            "name" => "2. Get Categories",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    ["key" => "Content-Type", "value" => "application/json"],
                                ],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => "{}"
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/api/v1/category",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["api", "v1", "category"]
                                ]
                            ]
                        ],
                        [
                            "name" => "3. Get Product Variants",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    ["key" => "Content-Type", "value" => "application/json"],
                                ],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => json_encode(["code" => "mobilelegends"], JSON_PRETTY_PRINT)
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/api/v1/variant",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["api", "v1", "variant"]
                                ]
                            ]
                        ],
                        [
                            "name" => "4. Create Order",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    ["key" => "Content-Type", "value" => "application/json"],
                                ],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => json_encode([
                                        "code" => "ML86D",
                                        "referenceNumber" => "REF-{{timestamp}}",
                                        "user_id" => "123456789",
                                        "zone_id" => "7890"
                                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/api/v1/order",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["api", "v1", "order"]
                                ]
                            ]
                        ],
                        [
                            "name" => "5. Check Order Status",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    ["key" => "Content-Type", "value" => "application/json"],
                                ],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => "{}"
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/api/v1/status-order/{{invoice_number}}",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["api", "v1", "status-order", "{{invoice_number}}"]
                                ]
                            ]
                        ]
                    ],
                    "auth" => [
                        "type" => "bearer",
                        "bearer" => [
                            ["key" => "token", "value" => "{{api_key}}", "type" => "string"]
                        ]
                    ]
                ],
                [
                    "name" => "Sandbox API",
                    "item" => [
                        [
                            "name" => "1. Balance Inquiry (Sandbox)",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    ["key" => "Content-Type", "value" => "application/json"],
                                ],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => "{}"
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/api/v1/sandbox/balance",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["api", "v1", "sandbox", "balance"]
                                ]
                            ]
                        ],
                        [
                            "name" => "2. Get Categories (Sandbox)",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    ["key" => "Content-Type", "value" => "application/json"],
                                ],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => "{}"
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/api/v1/sandbox/category",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["api", "v1", "sandbox", "category"]
                                ]
                            ]
                        ],
                        [
                            "name" => "3. Get Variants (Sandbox)",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    ["key" => "Content-Type", "value" => "application/json"],
                                ],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => json_encode(["code" => "mobilelegends"], JSON_PRETTY_PRINT)
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/api/v1/sandbox/variant",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["api", "v1", "sandbox", "variant"]
                                ]
                            ]
                        ],
                        [
                            "name" => "4. Create Order (Sandbox)",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    ["key" => "Content-Type", "value" => "application/json"],
                                ],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => json_encode([
                                        "code" => "ML86D",
                                        "referenceNumber" => "SANDBOX-{{timestamp}}",
                                        "user_id" => "123456789",
                                        "zone_id" => "7890"
                                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/api/v1/sandbox/order",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["api", "v1", "sandbox", "order"]
                                ]
                            ]
                        ],
                        [
                            "name" => "5. Check Status (Sandbox)",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    ["key" => "Content-Type", "value" => "application/json"],
                                ],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => "{}"
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/api/v1/sandbox/status-order/{{sandbox_invoice}}",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["api", "v1", "sandbox", "status-order", "{{sandbox_invoice}}"]
                                ]
                            ]
                        ],
                        [
                            "name" => "6. Simulate Status Change",
                            "request" => [
                                "method" => "POST",
                                "header" => [
                                    ["key" => "Content-Type", "value" => "application/json"],
                                ],
                                "body" => [
                                    "mode" => "raw",
                                    "raw" => json_encode(["status" => "success"], JSON_PRETTY_PRINT)
                                ],
                                "url" => [
                                    "raw" => "{{base_url}}/api/v1/sandbox/simulate-status/{{sandbox_invoice}}",
                                    "host" => ["{{base_url}}"],
                                    "path" => ["api", "v1", "sandbox", "simulate-status", "{{sandbox_invoice}}"]
                                ]
                            ]
                        ]
                    ],
                    "auth" => [
                        "type" => "bearer",
                        "bearer" => [
                            ["key" => "token", "value" => "{{sandbox_api_key}}", "type" => "string"]
                        ]
                    ]
                ]
            ],
            "variable" => [
                [
                    "key" => "base_url",
                    "value" => $baseUrl,
                    "type" => "string"
                ],
                [
                    "key" => "api_key",
                    "value" => "YOUR_PRODUCTION_API_KEY",
                    "type" => "string"
                ],
                [
                    "key" => "sandbox_api_key",
                    "value" => "YOUR_SANDBOX_API_KEY",
                    "type" => "string"
                ],
                [
                    "key" => "invoice_number",
                    "value" => "",
                    "type" => "string"
                ],
                [
                    "key" => "sandbox_invoice",
                    "value" => "",
                    "type" => "string"
                ]
            ]
        ];
        
        $json = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        return response($json, 200)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="H2H_API_Collection.json"');
    }
    
    /**
     * Export Postman environment (placeholder for future implementation)
     */
    public function environment(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Environment export feature coming soon',
            'instructions' => 'Please manually configure your API keys in Postman collection variables'
        ]);
    }
}

import React from 'react';
import { Head, Link } from '@inertiajs/react';

export default function Sandbox({ is_sandbox_active }) {
    return (
        <div className="p-6">
            <Head title="Sandbox Guide" />
            <h1 className="text-2xl font-bold mb-6">Sandbox Testing Guide</h1>

            {!is_sandbox_active ? (
                <div className="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded mb-6">
                    <p className="font-semibold">Sandbox Inactive</p>
                    <p className="text-sm">You do not have an active Sandbox integration. Contact an administrator to enable it for your account.</p>
                </div>
            ) : null}

            <div className="space-y-6">
                <section className="bg-white shadow p-6 rounded-lg border">
                    <h2 className="text-xl font-semibold mb-4">1. Understand the Sandbox Environment</h2>
                    <p className="text-gray-600 mb-4">
                        The Sandbox environment allows you to test your integration without spending real balance. 
                        It simulates provider responses and order status updates locally.
                    </p>
                    <ul className="list-disc list-inside text-gray-700 space-y-2">
                        <li>Use the <code className="bg-gray-100 px-1">/api/v1/sandbox/*</code> endpoints.</li>
                        <li>Pass your <strong>Sandbox API Key</strong> as the Bearer Token.</li>
                        <li>Pass your <strong>Sandbox Integration Code</strong> in the <code className="bg-gray-100 px-1">X-Reseller-Integration-Code</code> header.</li>
                    </ul>
                </section>

                <section className="bg-white shadow p-6 rounded-lg border">
                    <h2 className="text-xl font-semibold mb-4">2. Testing Callbacks (Webhooks)</h2>
                    <p className="text-gray-600 mb-4">
                        When an order status changes in Sandbox, we will fire a webhook to your configured Live webhook URL, 
                        but the payload will include <code className="bg-gray-100 px-1">"mode": "sandbox"</code>.
                    </p>
                    <p className="text-gray-600">
                        <strong>Interactive Webhook Testing:</strong> 
                        Currently under development. Soon, you will be able to manually trigger test payloads to your endpoint from this page.
                    </p>
                </section>

                <section className="bg-white shadow p-6 rounded-lg border">
                    <h2 className="text-xl font-semibold mb-4">3. Simulating Status Changes</h2>
                    <p className="text-gray-600 mb-4">
                        In the Sandbox environment, you can force an order to change its status to test your webhook handling.
                    </p>
                    <pre className="bg-gray-800 text-green-400 p-4 rounded overflow-x-auto text-sm">
{`POST /api/v1/sandbox/simulate-status
Content-Type: application/json
Authorization: Bearer <SANDBOX_API_KEY>
X-Reseller-Integration-Code: <SANDBOX_INTEGRATION_CODE>

{
    "invoice": "INV-12345",
    "status": "Success" // or "Failed", "Processing"
}`}
                    </pre>
                </section>
            </div>
            
            <div className="mt-8 text-center">
                <Link href="/id/reseller/docs" className="text-blue-600 hover:underline">
                    &larr; Back to API Documentation
                </Link>
            </div>
        </div>
    );
}

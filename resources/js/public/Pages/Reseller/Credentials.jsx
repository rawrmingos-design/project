import React from 'react';
import { Head, Link } from '@inertiajs/react';

export default function Credentials({ live, sandbox }) {
    return (
        <div className="p-6">
            <Head title="API Credentials" />
            <h1 className="text-2xl font-bold mb-6">API Credentials & Webhooks</h1>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Live Panel */}
                <div className="border rounded-lg p-5 shadow-sm">
                    <h2 className="text-xl font-semibold mb-4 text-green-700 flex items-center">
                        <span className="w-3 h-3 rounded-full bg-green-500 mr-2"></span>
                        Live Environment
                    </h2>
                    {live ? (
                        <div className="space-y-3 text-sm">
                            <p><strong>Status:</strong> {live.is_active ? 'Active' : 'Inactive'}</p>
                            <p><strong>Integration Code:</strong> <code className="bg-gray-100 px-1 rounded">{live.integration_code}</code></p>
                            <p><strong>API Key:</strong> <code className="bg-gray-100 px-1 rounded">{live.api_key_hint || 'Not Generated'}</code></p>
                            
                            <div className="mt-4 pt-4 border-t">
                                <h3 className="font-semibold mb-2">Webhook Profile</h3>
                                {live.webhook ? (
                                    <ul className="list-disc list-inside space-y-1">
                                        <li>Status: {live.webhook.is_enabled ? 'Enabled' : 'Disabled'}</li>
                                        <li>URL: {live.webhook.url}</li>
                                        <li>Algorithm: {live.webhook.algorithm}</li>
                                        <li>Header: {live.webhook.signature_header}</li>
                                    </ul>
                                ) : (
                                    <p className="text-gray-500 italic">No webhook configured.</p>
                                )}
                            </div>
                        </div>
                    ) : (
                        <p className="text-gray-500">Live integration not enabled.</p>
                    )}
                </div>

                {/* Sandbox Panel */}
                <div className="border rounded-lg p-5 shadow-sm">
                    <h2 className="text-xl font-semibold mb-4 text-yellow-600 flex items-center">
                        <span className="w-3 h-3 rounded-full bg-yellow-400 mr-2"></span>
                        Sandbox Environment
                    </h2>
                    {sandbox ? (
                        <div className="space-y-3 text-sm">
                            <p><strong>Status:</strong> {sandbox.is_active ? 'Active' : 'Inactive'}</p>
                            <p><strong>Integration Code:</strong> <code className="bg-gray-100 px-1 rounded">{sandbox.integration_code}</code></p>
                            <p><strong>Sandbox Key:</strong> <code className="bg-gray-100 px-1 rounded">{sandbox.api_key_hint || 'Not Generated'}</code></p>
                            <p className="text-xs text-gray-500 mt-2">
                                Webhooks in Sandbox follow the same configuration as Live.
                            </p>
                        </div>
                    ) : (
                        <p className="text-gray-500">Sandbox integration not enabled.</p>
                    )}
                </div>
            </div>
            
            <div className="mt-8 text-sm text-gray-600 bg-blue-50 p-4 rounded border border-blue-100">
                <p>For security, raw API keys and Webhook Secrets are never displayed here. If you suspect your key has been compromised, please contact an administrator to rotate it.</p>
            </div>
        </div>
    );
}

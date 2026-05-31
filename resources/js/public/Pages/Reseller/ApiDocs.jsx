import React from 'react';
import { Head } from '@inertiajs/react';

export default function ApiDocs({ canonical_url, live_base_url, sandbox_base_url, live, sandbox }) {
    return (
        <div className="p-6">
            <Head title="API Documentation Context" />
            
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">API Documentation Reference</h1>
                <a 
                    href={canonical_url} 
                    target="_blank" 
                    rel="noreferrer"
                    className="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition"
                >
                    View Full Documentation ↗
                </a>
            </div>

            <div className="bg-blue-50 text-blue-900 p-4 rounded-lg border border-blue-200 mb-8">
                <p>Welcome! Use the personalized context parameters below when integrating with our API. For full payload structures and endpoint details, click the button above to view our canonical API documentation.</p>
            </div>

            <div className="space-y-8">
                {/* Live Context */}
                <section>
                    <h2 className="text-xl font-semibold mb-3 border-b pb-2">Live Environment Parameters</h2>
                    {live ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="bg-gray-50 p-4 rounded border">
                                <span className="block text-xs font-bold text-gray-500 uppercase tracking-wide">Base URL</span>
                                <code className="text-blue-600 block mt-1">{live_base_url}</code>
                            </div>
                            <div className="bg-gray-50 p-4 rounded border">
                                <span className="block text-xs font-bold text-gray-500 uppercase tracking-wide">X-Reseller-Integration-Code</span>
                                <code className="block mt-1">{live.integration_code}</code>
                            </div>
                            <div className="bg-gray-50 p-4 rounded border">
                                <span className="block text-xs font-bold text-gray-500 uppercase tracking-wide">API Key Hint (Authorization Bearer)</span>
                                <code className="block mt-1">{live.api_key_hint || 'Contact Admin'}</code>
                            </div>
                        </div>
                    ) : (
                        <p className="text-gray-500">Live integration is not active on your account.</p>
                    )}
                </section>

                {/* Sandbox Context */}
                <section>
                    <h2 className="text-xl font-semibold mb-3 border-b pb-2">Sandbox Environment Parameters</h2>
                    {sandbox ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="bg-gray-50 p-4 rounded border">
                                <span className="block text-xs font-bold text-gray-500 uppercase tracking-wide">Base URL</span>
                                <code className="text-yellow-700 block mt-1">{sandbox_base_url}</code>
                            </div>
                            <div className="bg-gray-50 p-4 rounded border">
                                <span className="block text-xs font-bold text-gray-500 uppercase tracking-wide">X-Reseller-Integration-Code</span>
                                <code className="block mt-1">{sandbox.integration_code}</code>
                            </div>
                            <div className="bg-gray-50 p-4 rounded border">
                                <span className="block text-xs font-bold text-gray-500 uppercase tracking-wide">Sandbox API Key Hint (Authorization Bearer)</span>
                                <code className="block mt-1">{sandbox.api_key_hint || 'Contact Admin'}</code>
                            </div>
                        </div>
                    ) : (
                        <p className="text-gray-500">Sandbox integration is not active on your account.</p>
                    )}
                </section>
            </div>
        </div>
    );
}

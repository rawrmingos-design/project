import React from 'react';
import { Head } from '@inertiajs/react';

export default function CallbackLogs({ deliveries }) {
    return (
        <div className="p-6">
            <Head title="Callback Logs" />
            <h1 className="text-2xl font-bold mb-6">Webhook Delivery Logs</h1>

            <div className="bg-white shadow overflow-hidden rounded-lg">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Environment</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HTTP Code</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {deliveries.data.length > 0 ? deliveries.data.map((log) => (
                            <tr key={log.id}>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{new Date(log.created_at).toLocaleString()}</td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                    {log.integration.mode === 'sandbox' ? (
                                        <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Sandbox</span>
                                    ) : (
                                        <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Live</span>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{log.pembelian ? log.pembelian.order_id : '-'}</td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                    {log.status === 'success' ? (
                                        <span className="text-green-600 font-medium">Success</span>
                                    ) : (
                                        <span className="text-red-600 font-medium">Failed</span>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{log.last_response_status || '-'}</td>
                            </tr>
                        )) : (
                            <tr>
                                <td colSpan="5" className="px-6 py-4 text-center text-gray-500">No callback logs found.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination Placeholder */}
            {deliveries.links && deliveries.links.length > 3 && (
                <div className="mt-4 flex justify-center">
                    <p className="text-sm text-gray-500">Pagination UI here...</p>
                </div>
            )}
        </div>
    );
}

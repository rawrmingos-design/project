import React from 'react';
import { Head } from '@inertiajs/react';

export default function Dashboard({ user }) {
    return (
        <div className="p-6">
            <Head title="Reseller Dashboard" />
            <h1 className="text-2xl font-bold mb-4">Reseller Dashboard</h1>
            <p>Welcome, {user.name}. This is a placeholder for the MVP Reseller Dashboard.</p>
        </div>
    );
}

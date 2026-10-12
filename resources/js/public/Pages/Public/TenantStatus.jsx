import React from 'react';
import TenantShell from '../../Layouts/TenantShell';

/**
 * Halaman status untuk tenant non-aktif (menggantikan 404 polos).
 *
 * Props dari TenantStatusPageController:
 *   status             'suspended' | 'pending_payment'
 *   address            alamat tenant yang sudah disensor (D9)
 *   contactUrl         WA platform → WA admin → app.url (D8)
 *   contactIsWhatsapp  true kalau contactUrl memang tautan wa.me (Q5)
 *
 * Q6: sengaja tidak ada baris "Sejak (kapan disuspend)".
 */

const BADGE = {
    suspended: {
        label: 'Ditangguhkan',
        className: 'bg-error-container text-on-error-container',
    },
    pending_payment: {
        label: 'Menunggu pembayaran',
        className: 'bg-secondary-container text-on-secondary-container',
    },
};

const COPY = {
    suspended: {
        title: 'Situs ini ditangguhkan sementara',
        body: 'Layanan untuk alamat ini sedang tidak aktif karena tagihan langganan belum diselesaikan. Semua data toko aman dan tidak dihapus.',
    },
    pending_payment: {
        title: 'Situs ini belum aktif',
        body: 'Pendaftaran untuk alamat ini sudah tercatat, tetapi langganan belum diaktifkan. Semua data toko aman dan tidak dihapus.',
    },
};

export default function TenantStatus({ status, address, contactUrl, contactIsWhatsapp }) {
    const badge = BADGE[status] || BADGE.suspended;
    const copy = COPY[status] || COPY.suspended;

    return (
        <TenantShell title="Status Layanan" footer="Halaman ini tidak diindeks mesin pencari.">
            <div className="flex flex-col gap-8">
                <div className="flex flex-col gap-5">
                    <span
                        className={`self-start px-3 py-1 font-label-bold text-label-bold uppercase tracking-wider ${badge.className}`}
                    >
                        {badge.label}
                    </span>

                    <h1 className="font-display-lg text-display-lg text-on-surface">{copy.title}</h1>

                    <p className="font-body-lg text-body-lg text-on-surface-variant max-w-prose">
                        {copy.body}
                    </p>
                </div>

                <dl className="border border-outline-variant bg-surface-container p-5 flex flex-col gap-4">
                    <div className="flex flex-col gap-1">
                        <dt className="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                            Alamat
                        </dt>
                        <dd className="font-label-bold text-label-bold text-on-surface break-all">
                            {address}
                        </dd>
                    </div>

                    <div className="flex flex-col gap-1">
                        <dt className="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                            Status
                        </dt>
                        <dd className="font-label-bold text-label-bold text-on-surface">
                            {badge.label}
                        </dd>
                    </div>
                </dl>

                <div className="flex flex-wrap gap-3">
                    <a
                        href={contactUrl}
                        className="px-5 py-3 bg-primary-container text-on-primary-container font-label-bold text-label-bold uppercase tracking-wider focus-visible:outline focus-visible:outline-2 focus-visible:outline-tertiary"
                    >
                        {contactIsWhatsapp ? 'Hubungi kami di WhatsApp' : 'Hubungi kami'}
                    </a>
                </div>
            </div>
        </TenantShell>
    );
}

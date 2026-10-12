import React from 'react';
import TenantShell from '../../Layouts/TenantShell';

/**
 * D13: halaman tagihan publik ber-token — `/tagihan/{token}` di host platform.
 *
 * Props dari BillingPageController:
 *   invoice.number, .amount, .status, .dueDate, .periodEnd, .tier
 *   address            label tenant yang disensor (D9), boleh kosong
 *   payUrl             tautan bayar gateway, null kalau tidak ada yang perlu dibayar
 *   contactUrl         WA platform → WA admin → app.url (D8)
 *   contactIsWhatsapp  true kalau contactUrl memang tautan wa.me (Q5)
 *
 * D13.4: yang tampil HANYA data tagihan. Tidak ada nama owner, email, nomor WA,
 * atau merek tenant. Tautan ini bisa dibuka siapa saja yang punya URL-nya —
 * satu kebocoran di sini langsung terbaca publik.
 */

const STATUS = {
    pending: {
        label: 'Menunggu pembayaran',
        className: 'bg-secondary-container text-on-secondary-container',
    },
    paid: {
        label: 'Sudah dibayar',
        className: 'bg-tertiary-container text-on-tertiary-container',
    },
    expired: {
        label: 'Kedaluwarsa',
        className: 'bg-error-container text-on-error-container',
    },
    cancelled: {
        label: 'Dibatalkan',
        className: 'bg-surface-container-highest text-on-surface-variant',
    },
};

// Uang wajib mono supaya angka tidak "menari" antar baris (design system §2).
function rupiah(value) {
    return `Rp ${new Intl.NumberFormat('id-ID').format(Number(value || 0))}`;
}

function tanggal(value) {
    if (!value) {
        return '—';
    }

    const d = new Date(value);

    if (Number.isNaN(d.getTime())) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(d);
}

export default function TenantBilling({
    invoice = {},
    address,
    payUrl,
    contactUrl,
    contactIsWhatsapp,
}) {
    const status = STATUS[invoice.status] || STATUS.pending;
    const bisaBayar = typeof payUrl === 'string' && payUrl !== '';

    return (
        <TenantShell title="Tagihan Langganan" footer="Halaman ini tidak diindeks mesin pencari.">
            <div className="flex flex-col gap-8">
                <div className="flex flex-col gap-5">
                    <span
                        className={`self-start px-3 py-1 font-label-bold text-label-bold uppercase tracking-wider ${status.className}`}
                    >
                        {status.label}
                    </span>

                    <h1 className="font-headline-lg text-headline-lg text-on-surface">
                        Tagihan langganan
                    </h1>

                    <p className="font-body-lg text-body-lg text-on-surface-variant max-w-prose">
                        Rincian tagihan untuk layanan ini. Selesaikan pembayaran sebelum jatuh tempo
                        supaya situs tetap aktif.
                    </p>
                </div>

                <dl className="border border-outline-variant bg-surface-container p-5 flex flex-col gap-4">
                    <div className="flex flex-col gap-1">
                        <dt className="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                            Nomor tagihan
                        </dt>
                        <dd className="font-label-bold text-label-bold text-on-surface break-all">
                            {invoice.number || '—'}
                        </dd>
                    </div>

                    <div className="flex flex-col gap-1">
                        <dt className="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                            Jumlah
                        </dt>
                        <dd className="font-label-bold text-label-bold text-on-surface break-all">
                            {rupiah(invoice.amount)}
                        </dd>
                    </div>

                    <div className="flex flex-col gap-1">
                        <dt className="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                            Jatuh tempo
                        </dt>
                        <dd className="font-label-bold text-label-bold text-on-surface break-all">
                            {tanggal(invoice.dueDate)}
                        </dd>
                    </div>

                    {invoice.periodEnd ? (
                        <div className="flex flex-col gap-1">
                            <dt className="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                                Berlaku sampai
                            </dt>
                            <dd className="font-label-bold text-label-bold text-on-surface break-all">
                                {tanggal(invoice.periodEnd)}
                            </dd>
                        </div>
                    ) : null}

                    {address ? (
                        <div className="flex flex-col gap-1">
                            <dt className="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                                Untuk alamat
                            </dt>
                            <dd className="font-label-bold text-label-bold text-on-surface break-all">
                                {address}
                            </dd>
                        </div>
                    ) : null}
                </dl>

                <div className="flex flex-wrap items-center gap-3">
                    {bisaBayar ? (
                        <a
                            href={payUrl}
                            className="px-5 py-3 bg-primary-container text-on-primary-container font-label-bold text-label-bold uppercase tracking-wider focus-visible:outline focus-visible:outline-2 focus-visible:outline-tertiary"
                        >
                            Bayar sekarang
                        </a>
                    ) : null}

                    <a
                        href={contactUrl}
                        className="px-5 py-3 border border-outline-variant text-on-surface font-label-bold text-label-bold uppercase tracking-wider focus-visible:outline focus-visible:outline-2 focus-visible:outline-tertiary"
                    >
                        {contactIsWhatsapp ? 'Hubungi kami di WhatsApp' : 'Hubungi kami'}
                    </a>
                </div>

                {!bisaBayar && invoice.status === 'pending' ? (
                    <p className="font-body-sm text-body-sm text-on-surface-variant max-w-prose">
                        Tautan pembayaran sedang tidak tersedia. Hubungi kami supaya tautan baru
                        dibuatkan untuk tagihan ini.
                    </p>
                ) : null}
            </div>
        </TenantShell>
    );
}

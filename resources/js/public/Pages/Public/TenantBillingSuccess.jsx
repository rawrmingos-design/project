import React from 'react';
import TenantShell from '../../Layouts/TenantShell';

/**
 * S4: halaman sukses / aktivasi — `/tagihan/{token}/sukses` di host platform.
 *
 * Props dari BillingSuccessPageController:
 *   state              'paid' | 'confirming' | 'expired' | 'cancelled'
 *   invoice.*          number, amount, status, tier, paidAt, dueDate, periodEnd
 *   store.name, .host  toko yang baru dibeli (milik pembeli sendiri)
 *   billingUrl         halaman tagihan ber-token (S3), untuk coba lagi
 *   contactUrl         WA platform → WA admin → app.url
 *   contactIsWhatsapp  true kalau contactUrl memang tautan wa.me
 *
 * ⚠️ Hanya `state === 'paid'` yang boleh berbunyi "berhasil". `confirming`
 * berarti pembeli tiba SEBELUM callback Duitku masuk — jangan bilang sukses,
 * jangan bilang gagal. Status resmi datang dari callback, bukan dari kedatangan
 * pengunjung.
 *
 * Uang & tanggal wajib mono (design system) supaya angka tidak "menari".
 */

export const STATE = {
    paid: {
        label: 'Pembayaran diterima',
        heading: 'Pembayaran diterima — toko lo sudah aktif.',
        badge: 'bg-tertiary-container text-on-tertiary-container',
    },
    confirming: {
        label: 'Menunggu konfirmasi',
        heading: 'Pembayaran sedang dikonfirmasi.',
        badge: 'bg-secondary-container text-on-secondary-container',
    },
    expired: {
        label: 'Kedaluwarsa',
        heading: 'Tagihan ini sudah kedaluwarsa.',
        badge: 'bg-error-container text-on-error-container',
    },
    cancelled: {
        label: 'Dibatalkan',
        heading: 'Tagihan ini sudah dibatalkan.',
        badge: 'bg-surface-container-highest text-on-surface-variant',
    },
};

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

function Baris({ label, children }) {
    return (
        <div className="flex flex-col gap-1">
            <dt className="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                {label}
            </dt>
            <dd className="font-label-bold text-label-bold text-on-surface break-all">{children}</dd>
        </div>
    );
}

export default function TenantBillingSuccess({
    state = 'confirming',
    invoice = {},
    store = {},
    billingUrl,
    contactUrl,
    contactIsWhatsapp,
}) {
    const view = STATE[state] || STATE.confirming;
    const lunas = state === 'paid';
    const alamatToko = typeof store.host === 'string' && store.host !== '' ? store.host : '';
    const urlToko = typeof store.url === 'string' && store.url !== '' ? store.url : '';

    return (
        <TenantShell title="Status Aktivasi" footer="Halaman ini tidak diindeks mesin pencari.">
            <div className="flex flex-col gap-8">
                <div className="flex flex-col gap-5">
                    <span
                        className={`self-start px-3 py-1 font-label-bold text-label-bold uppercase tracking-wider ${view.badge}`}
                    >
                        {view.label}
                    </span>

                    <h1 className="font-headline-lg text-headline-lg text-on-surface">{view.heading}</h1>

                    {lunas ? (
                        <p className="font-body-lg text-body-lg text-on-surface-variant max-w-prose">
                            Toko lo sudah aktif dan bisa dibuka sekarang. Semua rinciannya ada di
                            bawah ini.
                        </p>
                    ) : null}

                    {state === 'confirming' ? (
                        <p className="font-body-lg text-body-lg text-on-surface-variant max-w-prose">
                            Pembayaran lo sudah kami terima di sisi kami, tapi konfirmasi resmi dari
                            penyedia pembayaran belum masuk. Ini normal dan biasanya cepat. Muat
                            ulang halaman ini sebentar lagi — statusnya berubah sendiri begitu
                            konfirmasi datang.
                        </p>
                    ) : null}

                    {state === 'expired' ? (
                        <p className="font-body-lg text-body-lg text-on-surface-variant max-w-prose">
                            Waktu pembayaran untuk tagihan ini sudah habis, jadi toko belum aktif.
                            Buka halaman tagihan untuk membuat tautan pembayaran baru.
                        </p>
                    ) : null}

                    {state === 'cancelled' ? (
                        <p className="font-body-lg text-body-lg text-on-surface-variant max-w-prose">
                            Tagihan ini sudah dibatalkan dan tidak bisa dibayar lagi. Hubungi kami
                            kalau lo merasa ini keliru.
                        </p>
                    ) : null}
                </div>

                <dl className="border border-outline-variant bg-surface-container p-5 flex flex-col gap-4">
                    {store.name ? <Baris label="Nama toko">{store.name}</Baris> : null}

                    {alamatToko ? (
                        <Baris label="Alamat toko">
                            {alamatToko}
                        </Baris>
                    ) : null}

                    {invoice.tier ? <Baris label="Paket">{invoice.tier}</Baris> : null}

                    <Baris label="Jumlah dibayar">{rupiah(invoice.amount)}</Baris>

                    {lunas ? (
                        <>
                            <Baris label="Berlaku sampai">{tanggal(invoice.periodEnd)}</Baris>
                            <Baris label="Dibayar pada">{tanggal(invoice.paidAt)}</Baris>
                        </>
                    ) : (
                        <Baris label="Jatuh tempo">{tanggal(invoice.dueDate)}</Baris>
                    )}

                    <Baris label="Nomor tagihan">{invoice.number || '—'}</Baris>
                </dl>

                <div className="flex flex-wrap items-center gap-3">
                    {lunas && urlToko ? (
                        <a
                            href={urlToko}
                            className="px-5 py-3 bg-primary-container text-on-primary-container font-label-bold text-label-bold uppercase tracking-wider focus-visible:outline focus-visible:outline-2 focus-visible:outline-tertiary"
                        >
                            Buka toko lo
                        </a>
                    ) : null}

                    {!lunas && billingUrl ? (
                        <a
                            href={billingUrl}
                            className="px-5 py-3 bg-primary-container text-on-primary-container font-label-bold text-label-bold uppercase tracking-wider focus-visible:outline focus-visible:outline-2 focus-visible:outline-tertiary"
                        >
                            {state === 'confirming' ? 'Lihat halaman tagihan' : 'Coba bayar lagi'}
                        </a>
                    ) : null}

                    {state === 'confirming' ? (
                        <button
                            type="button"
                            onClick={() => window.location.reload()}
                            className="px-5 py-3 border border-outline-variant text-on-surface font-label-bold text-label-bold uppercase tracking-wider focus-visible:outline focus-visible:outline-2 focus-visible:outline-tertiary"
                        >
                            Muat ulang
                        </button>
                    ) : null}

                    {!lunas ? (
                        <a
                            href={contactUrl}
                            className="px-5 py-3 border border-outline-variant text-on-surface font-label-bold text-label-bold uppercase tracking-wider focus-visible:outline focus-visible:outline-2 focus-visible:outline-tertiary"
                        >
                            {contactIsWhatsapp ? 'Hubungi kami di WhatsApp' : 'Hubungi kami'}
                        </a>
                    ) : null}
                </div>

                {lunas ? (
                    <div className="flex flex-col gap-3 border-t border-outline-variant pt-6">
                        <h2 className="font-title-md text-title-md text-on-surface">
                            Langkah berikutnya
                        </h2>
                        <ol className="flex flex-col gap-2 font-body-md text-body-md text-on-surface-variant">
                            <li>1. Buka toko lo dan pastikan katalognya sudah tampil.</li>
                            <li>2. Atur markup harga sesuai margin yang lo mau.</li>
                            <li>3. Ganti tema/warna toko kalau perlu.</li>
                        </ol>
                    </div>
                ) : null}

                <p className="font-body-sm text-body-sm text-on-surface-variant max-w-prose">
                    Langganan lo tidak berubah sendiri di luar perpanjangan yang lo setujui. Tidak
                    ada biaya tambahan yang diambil tanpa konfirmasi lo.
                </p>
            </div>
        </TenantShell>
    );
}

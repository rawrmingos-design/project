import React from 'react';
import { Head } from '@inertiajs/react';

/**
 * Shell halaman platform (status tenant & tagihan).
 *
 * Sengaja TIDAK memakai PublicLayout: itu layout storefront yang menarik
 * Navbar/Footer platform dan mewarisi warna tema tenant saat runtime. Halaman
 * ini milik platform — identitasnya sendiri, tanpa warna merek tenant.
 *
 * Semua kelas di sini memakai token design system di tailwind.config.js.
 *
 * `noindex` dirender di sini, bukan lewat `SeoHead`: SeoHead membaca
 * `siteConfig`/`seoDefaults` (yang sengaja dibuang dari halaman platform) dan
 * menurunkan `canonical` dari `window.location.href`, sehingga host tenant
 * mentah bocor balik ke dalam markup. Prop `meta.robots` dari controller juga
 * mematikan JSON-LD otomatis di `app-inertia.blade.php`.
 */
export default function TenantShell({ title, children, footer, robots = 'noindex, nofollow' }) {
    return (
        <>
            <Head title={title}>
                <meta head-key="robots" name="robots" content={robots} />
            </Head>

            <div className="min-h-screen flex flex-col bg-background text-on-surface font-body-md">
                <header className="w-full max-w-2xl mx-auto px-6 py-6 flex items-center gap-3">
                    <span className="h-2 w-2 bg-primary-container" aria-hidden="true" />
                    <span className="font-label-bold text-label-bold text-primary uppercase tracking-wider">
                        Status Layanan
                    </span>
                </header>

                <main className="flex-1 w-full max-w-2xl mx-auto px-6 py-8 flex flex-col justify-center gap-8">
                    {children}
                </main>

                <footer className="w-full max-w-2xl mx-auto px-6 py-6 border-t border-outline-variant">
                    <p className="font-label-sm text-label-sm text-on-surface-variant">{footer}</p>
                </footer>
            </div>
        </>
    );
}

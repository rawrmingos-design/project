import React, { useState } from 'react';
import { Head } from '@inertiajs/react';

export default function DocsIndex({ appName }) {
    const [env, setEnv] = useState('live');
    const [activeTab, setActiveTab] = useState('introduction');

    const copyCode = (id) => {
        const el = document.getElementById(id);
        if (el) {
            const text = el.innerText;
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('copy-btn-' + id);
                if (btn) {
                    const original = btn.innerHTML;
                    btn.innerHTML = `<span class="material-symbols-outlined text-[14px]">check</span> Copied!`;
                    setTimeout(() => {
                        btn.innerHTML = original;
                    }, 2000);
                }
            });
        }
    };

    const renderSidebarLink = (id, icon, label) => {
        const isActive = activeTab === id;
        return (
            <button 
                onClick={() => setActiveTab(id)} 
                className={`flex w-full items-center gap-3 px-4 py-2 rounded-lg transition-colors ${isActive ? 'text-primary font-bold border-r-2 border-primary bg-primary/5' : 'text-on-surface-variant hover:text-on-surface hover:bg-white/5'}`}
            >
                <span className={`material-symbols-outlined ${isActive ? 'text-primary' : ''}`}>{icon}</span>
                <span className="font-body-md text-body-md">{label}</span>
            </button>
        );
    };

    const renderContent = () => {
        if (activeTab === 'introduction') {
            return (
                <div className="grid grid-cols-1 xl:grid-cols-2 gap-0 min-h-[calc(100vh-64px)]">
                    <section className="p-container-margin border-r border-white/5 max-w-4xl mx-auto xl:mx-0">
                        {/* Breaking Changes Alert Banner */}
                        <div className="bg-gradient-to-r from-error/20 to-error/10 border-2 border-error rounded-2xl p-8 mb-12 relative overflow-hidden">
                            <div className="absolute top-0 right-0 w-32 h-32 bg-error/10 rounded-full blur-3xl"></div>
                            <div className="relative z-10">
                                <div className="flex items-start gap-4">
                                    <div className="bg-error/20 p-3 rounded-xl">
                                        <span className="material-symbols-outlined text-error text-[32px]">warning</span>
                                    </div>
                                    <div className="flex-1">
                                        <h3 className="text-error font-bold text-xl mb-2">⚠️ BREAKING CHANGES IN v2.4</h3>
                                        <p className="text-on-surface mb-4 leading-relaxed">
                                            Jika Anda terintegrasi sebelum <strong>Juni 2026</strong>, Anda <strong>WAJIB</strong> update code integration Anda. Lihat Migration Guide di bawah.
                                        </p>
                                        <div className="bg-surface-container/50 rounded-lg p-4 border border-error/20">
                                            <p className="text-sm text-on-surface-variant mb-2 font-semibold">Key Changes:</p>
                                            <ul className="text-sm text-on-surface-variant space-y-1 list-disc list-inside">
                                                <li>Endpoint <code className="font-mono text-error">/product</code> → <code className="font-mono text-primary">/category</code> (404 untuk endpoint lama)</li>
                                                <li>Status response: <code className="font-mono text-error">userData</code> → <code className="font-mono text-primary">user_id + zone_id</code> terpisah</li>
                                                <li>Order failure response: Struktur uniform dengan success case</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <header className="mb-12">
                            <h2 className="font-headline-lg text-headline-lg text-on-surface mb-4">Pendahuluan</h2>
                            <p className="font-body-lg text-body-lg text-on-surface-variant leading-relaxed mb-6">
                                Selamat datang di dokumentasi API {appName} H2H v1. API ini dirancang untuk memungkinkan mitra (Reseller) mengintegrasikan layanan topup dan PPOB kami secara langsung ke dalam sistem mereka (Host-to-Host).
                            </p>
                            <p className="font-body-md text-body-md text-on-surface-variant leading-relaxed mb-6">
                                Dengan arsitektur yang modern, API kami menggunakan standar autentikasi <strong>Bearer Token</strong> untuk menjamin keamanan setiap transaksi. Seluruh request dan response format menggunakan standar JSON.
                            </p>
                            <div className="glass-panel p-6 rounded-xl border-l-4 border-primary">
                                <h4 className="text-on-surface font-bold mb-2 flex items-center gap-2">
                                    <span className="material-symbols-outlined text-primary">lightbulb</span> 
                                    Konsep Integrasi
                                </h4>
                                <p className="text-on-surface-variant text-sm leading-relaxed">
                                    Klien dapat membuat integrasi tanpa batas di dasbor Reseller (misal: integrasi untuk website A, integrasi untuk bot B), dan setiap integrasi memiliki Live Key dan Sandbox Key yang unik. Sistem akan otomatis mendeteksi integrasi mana yang sedang digunakan melalui Bearer Token yang dikirimkan.
                                </p>
                            </div>
                        </header>

                        <div className="mb-12">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6 flex items-center gap-3">
                                <span className="material-symbols-outlined text-primary text-[28px]">upgrade</span>
                                Migration Guide v2.3 → v2.4
                            </h3>
                            
                            <div className="bg-primary/10 border-l-4 border-primary p-6 rounded-r-xl mb-6">
                                <p className="text-on-surface text-sm leading-relaxed mb-2">
                                    <strong>Effective Date:</strong> Juni 8, 2026
                                </p>
                                <p className="text-on-surface-variant text-sm leading-relaxed">
                                    Jika Anda sudah terintegrasi sebelum tanggal ini, ikuti langkah-langkah di bawah untuk update integration code Anda. Estimasi waktu: <strong>15-30 menit</strong>.
                                </p>
                            </div>

                            <div className="space-y-8">
                                {/* Step 1 */}
                                <div className="glass-panel p-6 rounded-xl">
                                    <div className="flex items-start gap-4">
                                        <div className="bg-primary/20 text-primary font-bold rounded-full w-8 h-8 flex items-center justify-center flex-shrink-0">1</div>
                                        <div className="flex-1">
                                            <h4 className="text-on-surface font-bold mb-3">Update Category Endpoint URL</h4>
                                            <p className="text-sm text-on-surface-variant mb-3">
                                                Ganti endpoint <code className="bg-error/20 text-error px-1.5 py-0.5 rounded font-mono">/product</code> menjadi <code className="bg-primary/20 text-primary px-1.5 py-0.5 rounded font-mono">/category</code>
                                            </p>
                                            <div className="grid md:grid-cols-2 gap-3">
                                                <div className="bg-surface-container-highest/50 rounded-lg p-3">
                                                    <p className="text-xs font-semibold text-error mb-2">❌ Before:</p>
                                                    <code className="text-xs text-on-surface-variant block font-mono">
                                                        POST /api/v1/product
                                                    </code>
                                                </div>
                                                <div className="bg-surface-container-highest/50 rounded-lg p-3">
                                                    <p className="text-xs font-semibold text-primary mb-2">✅ After:</p>
                                                    <code className="text-xs text-on-surface-variant block font-mono">
                                                        POST /api/v1/category
                                                    </code>
                                                </div>
                                            </div>
                                            <p className="text-xs text-on-surface-variant/60 mt-2">
                                                💡 Response structure tetap sama - hanya URL yang berubah.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Step 2 */}
                                <div className="glass-panel p-6 rounded-xl">
                                    <div className="flex items-start gap-4">
                                        <div className="bg-primary/20 text-primary font-bold rounded-full w-8 h-8 flex items-center justify-center flex-shrink-0">2</div>
                                        <div className="flex-1">
                                            <h4 className="text-on-surface font-bold mb-3">Update Status Response Parsing</h4>
                                            <p className="text-sm text-on-surface-variant mb-3">
                                                Field <code className="font-mono">userData</code> sekarang terpisah menjadi <code className="font-mono">user_id</code> dan <code className="font-mono">zone_id</code>
                                            </p>
                                            <div className="space-y-3">
                                                <div className="bg-surface-container-highest/50 rounded-lg p-3">
                                                    <p className="text-xs font-semibold text-error mb-2">❌ Old Code:</p>
                                                    <pre className="text-xs text-on-surface-variant font-mono leading-relaxed">
{`const [userId, zoneId] = response.data.userData.split("|");`}
                                                    </pre>
                                                </div>
                                                <div className="bg-surface-container-highest/50 rounded-lg p-3">
                                                    <p className="text-xs font-semibold text-primary mb-2">✅ New Code:</p>
                                                    <pre className="text-xs text-on-surface-variant font-mono leading-relaxed">
{`const userId = response.data.user_id;
const zoneId = response.data.zone_id || null;`}
                                                    </pre>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Step 3 */}
                                <div className="glass-panel p-6 rounded-xl">
                                    <div className="flex items-start gap-4">
                                        <div className="bg-primary/20 text-primary font-bold rounded-full w-8 h-8 flex items-center justify-center flex-shrink-0">3</div>
                                        <div className="flex-1">
                                            <h4 className="text-on-surface font-bold mb-3">Handle Order Failure Response</h4>
                                            <p className="text-sm text-on-surface-variant mb-3">
                                                Order failure sekarang return struktur uniform dengan success case. Update error handling logic Anda.
                                            </p>
                                            <div className="bg-surface-container-highest/50 rounded-lg p-3">
                                                <p className="text-xs font-semibold text-primary mb-2">✅ New Approach:</p>
                                                <pre className="text-xs text-on-surface-variant font-mono leading-relaxed overflow-x-auto">
{`// Both success and failure return 'data' object
if (response.data.status === "failed") {
  // Handle failure: no charge, retry safe
  console.log(response.data.message); // Error reason
  console.log(response.data.buyer_last_saldo); // Unchanged
} else {
  // Handle success
  console.log(response.data.invoiceNumber);
}`}
                                                </pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Step 4 */}
                                <div className="glass-panel p-6 rounded-xl border border-tertiary/20">
                                    <div className="flex items-start gap-4">
                                        <div className="bg-tertiary/20 text-tertiary font-bold rounded-full w-8 h-8 flex items-center justify-center flex-shrink-0">4</div>
                                        <div className="flex-1">
                                            <h4 className="text-on-surface font-bold mb-3">Testing Checklist</h4>
                                            <ul className="space-y-2 text-sm text-on-surface-variant">
                                                <li className="flex gap-2">
                                                    <span className="text-tertiary">▸</span>
                                                    <span>Test category endpoint di Sandbox</span>
                                                </li>
                                                <li className="flex gap-2">
                                                    <span className="text-tertiary">▸</span>
                                                    <span>Verify status response parsing dengan order test</span>
                                                </li>
                                                <li className="flex gap-2">
                                                    <span className="text-tertiary">▸</span>
                                                    <span>Test failure scenario (insufficient balance)</span>
                                                </li>
                                                <li className="flex gap-2">
                                                    <span className="text-tertiary">▸</span>
                                                    <span>Verify idempotency dengan duplicate referenceNumber</span>
                                                </li>
                                                <li className="flex gap-2">
                                                    <span className="text-tertiary">▸</span>
                                                    <span>Deploy ke production saat off-peak hour</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="mb-12">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6">Base URL</h3>
                            <div className="glass-panel p-4 rounded-xl mb-4">
                                <p className="text-sm text-on-surface-variant mb-2">Gunakan URL berikut untuk lingkungan Live (Production):</p>
                                <code className="text-primary font-mono bg-primary/10 px-2 py-1 rounded">https://api.namadomain.com</code>
                            </div>
                            <div className="glass-panel p-4 rounded-xl border border-tertiary/20">
                                <p className="text-sm text-on-surface-variant mb-2">Gunakan URL berikut untuk lingkungan Sandbox (Testing):</p>
                                <code className="text-tertiary font-mono bg-tertiary/10 px-2 py-1 rounded">https://sandbox-api.namadomain.com</code>
                            </div>
                        </div>

                        <div className="mb-12">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6 flex items-center gap-3">
                                <span className="material-symbols-outlined text-error text-[28px]">shield</span>
                                IP Whitelist Requirement
                            </h3>
                            
                            <div className="grid md:grid-cols-2 gap-4 mb-6">
                                <div className="glass-panel p-5 rounded-xl border-l-4 border-error">
                                    <div className="flex items-center gap-2 mb-3">
                                        <span className="material-symbols-outlined text-error">lock</span>
                                        <h4 className="font-bold text-on-surface">Live Environment</h4>
                                    </div>
                                    <p className="text-sm text-on-surface-variant leading-relaxed mb-3">
                                        Endpoint <code className="font-mono text-error">/order</code> dan <code className="font-mono text-error">/status-order</code> <strong>REQUIRE IP Whitelist</strong>.
                                    </p>
                                    <div className="bg-error/10 rounded-lg p-3">
                                        <p className="text-xs text-on-surface-variant mb-1">
                                            <strong>Error jika tidak whitelisted:</strong>
                                        </p>
                                        <code className="text-xs font-mono text-error">
                                            403: IP address x.x.x.x tidak diizinkan
                                        </code>
                                    </div>
                                </div>
                                
                                <div className="glass-panel p-5 rounded-xl border-l-4 border-tertiary">
                                    <div className="flex items-center gap-2 mb-3">
                                        <span className="material-symbols-outlined text-tertiary">science</span>
                                        <h4 className="font-bold text-on-surface">Sandbox Environment</h4>
                                    </div>
                                    <p className="text-sm text-on-surface-variant leading-relaxed mb-3">
                                        <strong>TIDAK require IP Whitelist</strong>. Testing bebas dari IP mana saja.
                                    </p>
                                    <div className="bg-tertiary/10 rounded-lg p-3">
                                        <p className="text-xs text-on-surface-variant">
                                            Perfect untuk development dan QA testing tanpa batasan IP.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="bg-primary/10 border-l-4 border-primary p-5 rounded-r-xl">
                                <h4 className="font-bold text-on-surface mb-2 flex items-center gap-2">
                                    <span className="material-symbols-outlined text-primary text-[18px]">settings</span>
                                    Configuration
                                </h4>
                                <p className="text-sm text-on-surface-variant leading-relaxed mb-2">
                                    Kelola IP whitelist di <strong>Reseller Hub → Credentials → IP Whitelist</strong>
                                </p>
                                <ul className="text-xs text-on-surface-variant space-y-1 ml-4">
                                    <li>• Support individual IP: <code className="font-mono">203.0.113.50</code></li>
                                    <li>• Support CIDR notation: <code className="font-mono">203.0.113.0/24</code></li>
                                    <li>• Multiple IP entries allowed</li>
                                </ul>
                            </div>
                        </div>

                        <div className="mb-12">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6 flex items-center gap-3">
                                <span className="material-symbols-outlined text-primary text-[28px]">code</span>
                                API Standards
                            </h3>

                            <div className="space-y-6">
                                <div>
                                    <h4 className="font-semibold text-on-surface mb-3">Global Response Headers</h4>
                                    <div className="glass-panel rounded-xl overflow-hidden">
                                        <table className="w-full text-left border-collapse">
                                            <thead>
                                                <tr className="bg-white/5 border-b border-white/10">
                                                    <th className="p-4 font-label-md text-label-md text-on-surface-variant">Header</th>
                                                    <th className="p-4 font-label-md text-label-md text-on-surface-variant">Value</th>
                                                    <th className="p-4 font-label-md text-label-md text-on-surface-variant">Description</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-white/5">
                                                <tr className="hover:bg-primary/5 transition-colors">
                                                    <td className="p-4 font-mono text-primary text-sm">X-API-Version</td>
                                                    <td className="p-4 text-on-surface-variant text-sm">
                                                        <code className="bg-surface-container-highest px-1.5 py-0.5 rounded">1</code> (Live)<br/>
                                                        <code className="bg-surface-container-highest px-1.5 py-0.5 rounded">1-sandbox</code> (Sandbox)
                                                    </td>
                                                    <td className="p-4 text-on-surface-variant text-sm">API version identifier untuk future compatibility</td>
                                                </tr>
                                                <tr className="hover:bg-primary/5 transition-colors">
                                                    <td className="p-4 font-mono text-primary text-sm">Content-Type</td>
                                                    <td className="p-4 text-on-surface-variant text-sm">
                                                        <code className="bg-surface-container-highest px-1.5 py-0.5 rounded">application/json</code>
                                                    </td>
                                                    <td className="p-4 text-on-surface-variant text-sm">Semua response dalam format JSON</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div>
                                    <h4 className="font-semibold text-on-surface mb-3">API Versioning Strategy</h4>
                                    <div className="bg-surface-container-highest/50 rounded-xl p-5 space-y-3">
                                        <div className="flex items-start gap-3">
                                            <span className="material-symbols-outlined text-primary text-[20px]">info</span>
                                            <div>
                                                <p className="text-sm text-on-surface leading-relaxed mb-2">
                                                    <strong>Current Version:</strong> v2.4.0-stable (Effective: June 8, 2026)
                                                </p>
                                                <p className="text-sm text-on-surface-variant leading-relaxed">
                                                    API version ditentukan via <code className="font-mono">X-API-Version</code> response header. Clients dapat detect versi dan adjust behavior accordingly untuk backward compatibility.
                                                </p>
                                            </div>
                                        </div>
                                        <div className="bg-tertiary/10 rounded-lg p-3">
                                            <p className="text-xs text-on-surface-variant">
                                                <strong>Future-proofing:</strong> Breaking changes di masa depan akan menggunakan versioning pattern berbeda (e.g., v2) untuk backward compatibility guarantee.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <section className="bg-surface-container-lowest/50 p-container-margin sticky top-16 h-[calc(100vh-64px)] overflow-y-auto border-l border-white/5">
                        <div className="max-w-xl mx-auto">
                            <div className="mb-8">
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Autentikasi Header</span>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden group">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-secondary/50"></div>
                                    <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto">
<span className="text-on-surface-variant">Authorization: </span><span className="text-secondary">Bearer &lt;API_KEY&gt;</span>
<br />
<span className="text-on-surface-variant">Content-Type: </span><span className="text-secondary">application/json</span>
<br />
<span className="text-on-surface-variant">Accept: </span><span className="text-secondary">application/json</span>
                                    </pre>
                                </div>
                                <p className="mt-4 text-xs text-on-surface-variant/60 leading-relaxed">
                                    * Tidak perlu mengirimkan header tambahan apa pun selain standard header di atas. Identitas integrasi secara absolut ditentukan dari token yang digunakan.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            );
        }

        if (activeTab === 'authentication') {
            return (
                <div className="grid grid-cols-1 xl:grid-cols-2 gap-0 min-h-[calc(100vh-64px)]">
                    <section className="p-container-margin border-r border-white/5 max-w-4xl mx-auto xl:mx-0">
                        <header className="mb-12">
                            <h2 className="font-headline-lg text-headline-lg text-on-surface mb-4">Autentikasi</h2>
                            <p className="font-body-lg text-body-lg text-on-surface-variant leading-relaxed mb-6">
                                Autentikasi dilakukan menggunakan Bearer Token (API Key). Token ini dikirimkan melalui HTTP Header pada setiap request ke API.
                            </p>
                        </header>

                        <div className="mb-12">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6">Header Wajib</h3>
                            <div className="glass-panel rounded-xl overflow-hidden">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-white/5 border-b border-white/10">
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Header</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-white/5">
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm">Authorization</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code className="bg-surface-container-highest px-1.5 py-0.5 rounded">Bearer YOUR_API_KEY</code></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="space-y-6">
                            <h3 className="font-headline-md text-headline-md text-on-surface">Penanganan Error</h3>
                            <p className="font-body-md text-body-md text-on-surface-variant">
                                Jika token tidak disertakan, tidak valid, atau IP Anda tidak terdaftar di Whitelist, server akan merespon dengan status <code>403 Forbidden</code> atau <code>401 Unauthorized</code>.
                            </p>

                            <div className="mt-8">
                                <h4 className="font-headline-sm text-headline-sm text-on-surface mb-4">Common Error Codes</h4>
                                <div className="glass-panel rounded-xl overflow-hidden">
                                    <table className="w-full text-left border-collapse">
                                        <thead>
                                            <tr className="bg-white/5 border-b border-white/10">
                                                <th className="p-4 font-label-md text-label-md text-on-surface-variant">Error Code</th>
                                                <th className="p-4 font-label-md text-label-md text-on-surface-variant">HTTP</th>
                                                <th className="p-4 font-label-md text-label-md text-on-surface-variant">Solusi</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-white/5">
                                            <tr className="hover:bg-primary/5 transition-colors">
                                                <td className="p-4 font-mono text-error text-sm">ACCESS_TOKEN_REQUIRED</td>
                                                <td className="p-4 text-on-surface-variant text-sm">403</td>
                                                <td className="p-4 text-on-surface-variant text-sm">Tambahkan header Authorization: Bearer</td>
                                            </tr>
                                            <tr className="hover:bg-primary/5 transition-colors">
                                                <td className="p-4 font-mono text-error text-sm">INVALID_TOKEN</td>
                                                <td className="p-4 text-on-surface-variant text-sm">403</td>
                                                <td className="p-4 text-on-surface-variant text-sm">Token invalid/expired, regenerate di Credentials</td>
                                            </tr>
                                            <tr className="hover:bg-primary/5 transition-colors">
                                                <td className="p-4 font-mono text-error text-sm">IP_NOT_WHITELISTED</td>
                                                <td className="p-4 text-on-surface-variant text-sm">403</td>
                                                <td className="p-4 text-on-surface-variant text-sm">Tambah IP di Credentials panel (Live only)</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>
                    <section className="bg-surface-container-lowest/50 p-container-margin sticky top-16 h-[calc(100vh-64px)] overflow-y-auto border-l border-white/5">
                        <div className="max-w-xl mx-auto">
                            <div className="mb-8">
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Contoh Response Error (403)</span>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden group">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-error/50"></div>
                                    <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto">
<span className="text-on-surface">{`{`}</span>
  <span className="text-primary-container">"error"</span>: <span className="text-secondary">true</span>,
  <span className="text-primary-container">"code"</span>: <span className="text-on-surface">403</span>,
  <span className="text-primary-container">"message"</span>: <span className="text-tertiary">"Invalid Token"</span>,
  <span className="text-primary-container">"error_code"</span>: <span className="text-tertiary">"INVALID_TOKEN"</span>
<span className="text-on-surface">{`}`}</span></pre>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            );
        }

        if (activeTab === 'balance') {
            return (
                <div className="grid grid-cols-1 xl:grid-cols-2 gap-0 min-h-[calc(100vh-64px)]">
                    <section className="p-container-margin border-r border-white/5 max-w-4xl mx-auto xl:mx-0">
                        <header className="mb-12">
                            <div className="flex items-center gap-3 mb-4">
                                <span className="bg-tertiary/10 text-tertiary px-3 py-1 rounded text-[12px] font-bold uppercase tracking-widest border border-tertiary/20">POST</span>
                                <code className="text-primary font-mono text-lg">{env === 'live' ? '/api/v1/balance' : '/api/v1/sandbox/balance'}</code>
                            </div>
                            <h2 className="font-headline-lg text-headline-lg text-on-surface mb-4">Cek Saldo</h2>
                            <p className="font-body-lg text-body-lg text-on-surface-variant leading-relaxed mb-8">
                                Digunakan untuk mengecek sisa saldo Host-to-Host yang bisa Anda gunakan. Endpoint ini tidak memerlukan parameter request apa pun.
                            </p>
                        </header>

                        <div className="bg-error/10 border-l-4 border-error p-4 rounded-r-lg mb-8">
                            <h4 className="text-error font-bold mb-1 flex items-center gap-2">
                                <span className="material-symbols-outlined text-[18px]">warning</span> Perhatian
                            </h4>
                            <p className="text-on-surface-variant text-sm">
                                Response JSON akan dibungkus oleh variabel <code className="bg-surface-container-highest px-1.5 py-0.5 rounded text-primary font-mono">data</code>, pastikan Anda melakukan parsing dengan benar.
                            </p>
                        </div>

                        <div className="mb-12 mt-8">
                             <h3 className="font-headline-md text-headline-md text-on-surface mb-6">Parameter Response</h3>
                             <div className="glass-panel rounded-xl overflow-hidden">
                                 <table className="w-full text-left border-collapse">
                                     <thead>
                                         <tr className="bg-white/5 border-b border-white/10">
                                             <th className="p-4 font-label-md text-label-md text-on-surface-variant">Parameter</th>
                                             <th className="p-4 font-label-md text-label-md text-on-surface-variant">Tipe Data</th>
                                             <th className="p-4 font-label-md text-label-md text-on-surface-variant">Deskripsi</th>
                                         </tr>
                                     </thead>
                                     <tbody className="divide-y divide-white/5">
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm">error</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>Boolean</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Indikator error. <code>false</code> berarti berhasil.</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm">code</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>Integer</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Kode HTTP status response (misal: 200).</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm">message</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Pesan status dari server (misal: "Success").</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm">data</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>Object</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Objek penampung (wrapper) kembalian utama.</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm pl-8">↳ name</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Nama akun reseller.</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm pl-8">↳ telp</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Nomor WhatsApp terdaftar akun.</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm pl-8">↳ membership</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Tingkatan membership (Member, Gold, Platinum).</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm pl-8">↳ balance</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>Float</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Sisa saldo akun dalam Rupiah.</td>
                                         </tr>
                                     </tbody>
                                 </table>
                             </div>
                         </div>
                    </section>
                    
                    <section className="bg-surface-container-lowest/50 p-container-margin sticky top-16 h-[calc(100vh-64px)] overflow-y-auto border-l border-white/5">
                        <div className="max-w-xl mx-auto">
                            <div className="mb-8">
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Contoh Request</span>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden group">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-primary/50"></div>
                                    <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto">
<span className="text-tertiary">curl</span> --request POST \
  --url https://api.namadomain.com{env === 'live' ? '/api/v1/balance' : '/api/v1/sandbox/balance'} \
  --header <span className="text-secondary">'Authorization: Bearer YOUR_TOKEN'</span> \
  --header <span className="text-secondary">'Content-Type: application/json'</span> \
  --data <span className="text-primary-container">'{`{}`}'</span></pre>
                                </div>
                            </div>
                            
                            <div>
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Contoh Response</span>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden">
                                     <div className="absolute top-0 left-0 w-1 h-full bg-tertiary/50"></div>
                                     <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto">
<span className="text-on-surface">{`{`}</span>
  <span className="text-primary-container">"error"</span>: <span className="text-secondary">false</span>,
  <span className="text-primary-container">"code"</span>: <span className="text-on-surface">200</span>,
  <span className="text-primary-container">"message"</span>: <span className="text-tertiary">"Success"</span>,
  <span className="text-primary-container">"data"</span>: {`{`}
    <span className="text-primary-container">"name"</span>: <span className="text-tertiary">"Budi Reseller"</span>,
    <span className="text-primary-container">"telp"</span>: <span className="text-tertiary">"081234567890"</span>,
    <span className="text-primary-container">"membership"</span>: <span className="text-tertiary">"Gold"</span>,
    <span className="text-primary-container">"balance"</span>: <span className="text-on-surface">500000</span>
  {`}`}
<span className="text-on-surface">{`}`}</span></pre>
                                 </div>
                            </div>
                        </div>
                    </section>
                </div>
            );
        }

        if (activeTab === 'category') {
            return (
                <div className="grid grid-cols-1 xl:grid-cols-2 gap-0 min-h-[calc(100vh-64px)]">
                    <section className="p-container-margin border-r border-white/5 max-w-4xl mx-auto xl:mx-0">
                        <header className="mb-12">
                            <div className="flex items-center gap-3 mb-4">
                                <span className="bg-tertiary/10 text-tertiary px-3 py-1 rounded text-[12px] font-bold uppercase tracking-widest border border-tertiary/20">POST</span>
                                <code className="text-primary font-mono text-lg">{env === 'live' ? '/api/v1/category' : '/api/v1/sandbox/category'}</code>
                            </div>
                            <h2 className="font-headline-lg text-headline-lg text-on-surface mb-4">Daftar Kategori</h2>
                            <p className="font-body-lg text-body-lg text-on-surface-variant leading-relaxed">
                                Mengambil daftar lengkap kategori layanan utama yang tersedia (misal: PUBG Mobile, Mobile Legends). Endpoint ini tidak memerlukan parameter request apa pun.
                            </p>
                        </header>

                        <div className="bg-error/20 border-l-4 border-error p-6 rounded-r-lg mb-6">
                            <h4 className="text-error font-bold mb-2 flex items-center gap-2">
                                <span className="material-symbols-outlined text-[20px]">warning</span> 
                                BREAKING CHANGE (v2.4)
                            </h4>
                            <p className="text-on-surface text-sm mb-3 leading-relaxed">
                                Endpoint <code className="bg-error/20 px-2 py-1 rounded text-error font-mono font-bold">/product</code> telah diganti menjadi <code className="bg-primary/20 px-2 py-1 rounded text-primary font-mono font-bold">/category</code> untuk semantic clarity.
                            </p>
                            <p className="text-on-surface-variant text-sm leading-relaxed">
                                <strong>Impact:</strong> Request ke <code className="font-mono">/api/v1/product</code> akan return <strong>HTTP 404 Not Found</strong>. Response structure <strong>TIDAK berubah</strong> - hanya URL endpoint yang berbeda.
                            </p>
                        </div>

                        <div className="bg-error/10 border-l-4 border-error p-4 rounded-r-lg mb-8">
                            <h4 className="text-error font-bold mb-1 flex items-center gap-2">
                                <span className="material-symbols-outlined text-[18px]">warning</span> Perhatian
                            </h4>
                            <p className="text-on-surface-variant text-sm">
                                Response JSON akan dibungkus oleh array variabel <code className="bg-surface-container-highest px-1.5 py-0.5 rounded text-primary font-mono">data</code>, pastikan Anda melakukan perulangan (looping/iterasi) dengan benar.
                            </p>
                        </div>

                        <div className="mb-12 mt-8">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6">Parameter Response</h3>
                            <div className="glass-panel rounded-xl overflow-hidden">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-white/5 border-b border-white/10">
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Parameter</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Tipe Data</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Deskripsi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-white/5">
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm">error</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>Boolean</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Indikator error. <code>false</code> berarti berhasil.</td>
                                        </tr>
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm">data</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>Array</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Array berisi kumpulan objek kategori.</td>
                                        </tr>
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm pl-8">↳ code</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Kode unik kategori. Digunakan sebagai parameter di endpoint <code>/variant</code>.</td>
                                        </tr>
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm pl-8">↳ name</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Nama komersial kategori (misal: PUBG Mobile).</td>
                                        </tr>
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm pl-8">↳ type</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Tipe layanan kategori (misal: <code>game</code>, <code>pulsa</code>).</td>
                                        </tr>
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm pl-8">↳ is_active</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>Boolean</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Status keaktifan kategori.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </section>
                    
                    <section className="bg-surface-container-lowest/50 p-container-margin sticky top-16 h-[calc(100vh-64px)] overflow-y-auto border-l border-white/5">
                        <div className="max-w-xl mx-auto">
                            <div className="mb-8">
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Contoh Request</span>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden group">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-primary/50"></div>
                                    <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto">
<span className="text-tertiary">curl</span> --request POST \
  --url https://api.namadomain.com{env === 'live' ? '/api/v1/category' : '/api/v1/sandbox/category'} \
  --header <span className="text-secondary">'Authorization: Bearer YOUR_TOKEN'</span> \
  --header <span className="text-secondary">'Content-Type: application/json'</span> \
  --data <span className="text-primary-container">'{`{}`}'</span></pre>
                                </div>
                            </div>
                            
                            <div>
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Contoh Response</span>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-tertiary/50"></div>
                                    <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto">
<span className="text-on-surface">{`{`}</span>
  <span className="text-primary-container">"error"</span>: <span className="text-secondary">false</span>,
  <span className="text-primary-container">"code"</span>: <span className="text-on-surface">200</span>,
  <span className="text-primary-container">"message"</span>: <span className="text-tertiary">"Success"</span>,
  <span className="text-primary-container">"data"</span>: <span className="text-on-surface">[</span>
    {`{`}
      <span className="text-primary-container">"code"</span>: <span className="text-tertiary">"PUBGM"</span>,
      <span className="text-primary-container">"name"</span>: <span className="text-tertiary">"PUBG Mobile"</span>,
      <span className="text-primary-container">"type"</span>: <span className="text-tertiary">"game"</span>,
      <span className="text-primary-container">"is_active"</span>: <span className="text-secondary">true</span>
    {`}`}
  <span className="text-on-surface">]</span>
<span className="text-on-surface">{`}`}</span></pre>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            );
        }

        if (activeTab === 'variant') {
            return (
                <div className="grid grid-cols-1 xl:grid-cols-2 gap-0 min-h-[calc(100vh-64px)]">
                    <section className="p-container-margin border-r border-white/5 max-w-4xl mx-auto xl:mx-0">
                        <header className="mb-12">
                            <div className="flex items-center gap-3 mb-4">
                                <span className="bg-tertiary/10 text-tertiary px-3 py-1 rounded text-[12px] font-bold uppercase tracking-widest border border-tertiary/20">POST</span>
                                <code className="text-primary font-mono text-lg">{env === 'live' ? '/api/v1/variant' : '/api/v1/sandbox/variant'}</code>
                            </div>
                            <h2 className="font-headline-lg text-headline-lg text-on-surface mb-4">Daftar Layanan</h2>
                            <p className="font-body-lg text-body-lg text-on-surface-variant leading-relaxed">
                                Menarik daftar spesifik item layanan atau varian beserta harga H2H berdasarkan kode kategori (misal: daftar pecahan diamond yang ditawarkan).
                            </p>
                        </header>

                        <div className="bg-error/10 border-l-4 border-error p-4 rounded-r-lg mb-8">
                            <h4 className="text-error font-bold mb-1 flex items-center gap-2">
                                <span className="material-symbols-outlined text-[18px]">warning</span> Perhatian
                            </h4>
                            <p className="text-on-surface-variant text-sm">
                                Sama seperti endpoint kategori, kembalian akan berwujud array di dalam properti <code className="bg-surface-container-highest px-1.5 py-0.5 rounded text-primary font-mono">data</code>.
                            </p>
                        </div>

                        <div className="mb-12">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6">Parameter Request</h3>
                            <div className="glass-panel rounded-xl overflow-hidden">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-white/5 border-b border-white/10">
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Field</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Tipe</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Wajib</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Deskripsi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-white/5">
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm">code</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                            <td className="p-4"><span className="text-error font-bold text-[10px] uppercase">YA</span></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Kode kategori dari endpoint <code>/category</code>.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="mb-12 mt-8">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6">Parameter Response</h3>
                            <div className="glass-panel rounded-xl overflow-hidden">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-white/5 border-b border-white/10">
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Parameter</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Tipe Data</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Deskripsi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-white/5">
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm">data</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>Array</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Array berisi kumpulan objek item layanan.</td>
                                        </tr>
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm pl-8">↳ code</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Kode SKU layanan. Gunakan nilai ini sebagai <code>code</code> saat membuat pesanan.</td>
                                        </tr>
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm pl-8">↳ name</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Nama komersial item layanan.</td>
                                        </tr>
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm pl-8">↳ is_active</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>Boolean</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Status ketersediaan layanan. <code>true</code> = tersedia, <code>false</code> = tidak tersedia.</td>
                                        </tr>
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm pl-8">↳ price</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>Integer</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Harga khusus mitra H2H sesuai membership Anda.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </section>
                    
                    <section className="bg-surface-container-lowest/50 p-container-margin sticky top-16 h-[calc(100vh-64px)] overflow-y-auto border-l border-white/5">
                        <div className="max-w-xl mx-auto">
                            <div className="mb-8">
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Contoh Request</span>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden group">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-primary/50"></div>
                                    <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto">
<span className="text-tertiary">curl</span> --request POST \
  --url https://api.namadomain.com{env === 'live' ? '/api/v1/variant' : '/api/v1/sandbox/variant'} \
  --header <span className="text-secondary">'Authorization: Bearer YOUR_TOKEN'</span> \
  --header <span className="text-secondary">'Content-Type: application/json'</span> \
  --data <span className="text-primary-container">{`'{ "code": "PUBGM" }'`}</span></pre>
                                </div>
                            </div>

                            <div>
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Contoh Response</span>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-tertiary/50"></div>
                                    <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto">
<span className="text-on-surface">{`{`}</span>
  <span className="text-primary-container">"error"</span>: <span className="text-secondary">false</span>,
  <span className="text-primary-container">"code"</span>: <span className="text-on-surface">200</span>,
  <span className="text-primary-container">"message"</span>: <span className="text-tertiary">"Success"</span>,
  <span className="text-primary-container">"data"</span>: <span className="text-on-surface">[</span>
    {`{`}
      <span className="text-primary-container">"code"</span>: <span className="text-tertiary">"pubgm-60uc"</span>,
      <span className="text-primary-container">"name"</span>: <span className="text-tertiary">"60 UC"</span>,
      <span className="text-primary-container">"is_active"</span>: <span className="text-secondary">true</span>,
      <span className="text-primary-container">"price"</span>: <span className="text-on-surface">15000</span>
    {`}`}
  <span className="text-on-surface">]</span>
<span className="text-on-surface">{`}`}</span></pre>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            );
        }

        if (activeTab === 'order') {
            return (
                <div className="grid grid-cols-1 xl:grid-cols-2 gap-0 min-h-[calc(100vh-64px)]">
                    <section className="p-container-margin border-r border-white/5 max-w-4xl mx-auto xl:mx-0">
                        <header className="mb-12">
                            <div className="flex items-center gap-3 mb-4">
                                <span className="bg-tertiary/10 text-tertiary px-3 py-1 rounded text-[12px] font-bold uppercase tracking-widest border border-tertiary/20">POST</span>
                                <code className="text-primary font-mono text-lg">{env === 'live' ? '/api/v1/order' : '/api/v1/sandbox/order'}</code>
                            </div>
                            <h2 className="font-headline-lg text-headline-lg text-on-surface mb-4">Buat Pesanan Baru</h2>
                            <p className="font-body-lg text-body-lg text-on-surface-variant leading-relaxed">
                                Endpoint ini memungkinkan Anda untuk menginisiasi transaksi H2H baru. Pastikan saldo akun Anda mencukupi sebelum membuat request. {env === 'sandbox' && <span className="text-tertiary font-bold">Pesanan di Sandbox tidak akan memotong saldo sungguhan.</span>}
                            </p>
                        </header>
                        
                        <div className="mb-12">
                             <h3 className="font-headline-md text-headline-md text-on-surface mb-6">Parameter Request</h3>
                             <div className="glass-panel rounded-xl overflow-hidden">
                                 <table className="w-full text-left border-collapse">
                                     <thead>
                                         <tr className="bg-white/5 border-b border-white/10">
                                             <th className="p-4 font-label-md text-label-md text-on-surface-variant">Field</th>
                                             <th className="p-4 font-label-md text-label-md text-on-surface-variant">Tipe</th>
                                             <th className="p-4 font-label-md text-label-md text-on-surface-variant">Wajib</th>
                                             <th className="p-4 font-label-md text-label-md text-on-surface-variant">Deskripsi</th>
                                         </tr>
                                     </thead>
                                     <tbody className="divide-y divide-white/5">
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm">code</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                             <td className="p-4"><span className="text-error font-bold text-[10px] uppercase">YA</span></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Kode SKU produk dari endpoint <code>/variant</code>.</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm">referenceNumber</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                             <td className="p-4"><span className="text-error font-bold text-[10px] uppercase">YA</span></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Nomor referensi unik dari sistem Anda. Berfungsi sebagai idempoten — jika sama, order tidak diproses ulang.</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm">user_id</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                             <td className="p-4"><span className="text-error font-bold text-[10px] uppercase">YA</span></td>
                                             <td className="p-4 text-on-surface-variant text-sm">ID game atau nomor pelanggan target.</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm">zone_id</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                             <td className="p-4"><span className="text-on-surface-variant/40 font-bold text-[10px] uppercase">TIDAK</span></td>
                                             <td className="p-4 text-on-surface-variant text-sm">ID zona/server game (jika diperlukan oleh game tersebut).</td>
                                         </tr>
                                     </tbody>
                                 </table>
                             </div>
                         </div>

                         <div className="mb-12 mt-8">
                             <h3 className="font-headline-md text-headline-md text-on-surface mb-6">Parameter Response</h3>
                             <div className="glass-panel rounded-xl overflow-hidden">
                                 <table className="w-full text-left border-collapse">
                                     <thead>
                                         <tr className="bg-white/5 border-b border-white/10">
                                             <th className="p-4 font-label-md text-label-md text-on-surface-variant">Parameter</th>
                                             <th className="p-4 font-label-md text-label-md text-on-surface-variant">Tipe Data</th>
                                             <th className="p-4 font-label-md text-label-md text-on-surface-variant">Deskripsi</th>
                                         </tr>
                                     </thead>
                                     <tbody className="divide-y divide-white/5">
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm">error</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>Boolean</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Indikator error. <code>false</code> berarti berhasil.</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm pl-8">↳ invoiceNumber</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Nomor invoice yang dihasilkan sistem. Gunakan untuk cek status.</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm pl-8">↳ referenceNumber</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Nomor referensi yang Anda kirimkan (echo back).</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm pl-8">↳ code</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">SKU produk yang dipesan (echo back).</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm pl-8">↳ user_id</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">ID game target (echo back).</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm pl-8">↳ zone_id</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String | null</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">ID zona game (echo back). Null jika tidak relevan.</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm pl-8">↳ price</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>Integer</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Harga yang dibebankan untuk pesanan ini.</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm pl-8">↳ buyer_last_saldo</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>Float</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Saldo akun Anda setelah transaksi ini berhasil diproses.</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm pl-8">↳ status</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Status awal pesanan (<code>pending</code> atau <code>success</code>).</td>
                                         </tr>
                                         <tr className="hover:bg-primary/5 transition-colors">
                                             <td className="p-4 font-mono text-primary text-sm pl-8">↳ message</td>
                                             <td className="p-4 text-on-surface-variant text-sm"><code>String | null</code></td>
                                             <td className="p-4 text-on-surface-variant text-sm">Pesan tambahan dari provider. Berisi keterangan error jika pesanan gagal.</td>
                                         </tr>
                                     </tbody>
                                 </table>
                             </div>
                         </div>
                        
                        <div className="divider-gradient my-12"></div>
                        
                        <div className="space-y-6 mb-12">
                            <h3 className="font-headline-md text-headline-md text-on-surface flex items-center gap-3">
                                <span className="material-symbols-outlined text-primary">sync</span>
                                Idempotency (Duplicate Detection)
                            </h3>
                            <p className="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                                Jika Anda mengirim order dengan <code className="bg-surface-container-highest px-1.5 py-0.5 rounded text-primary font-mono">referenceNumber</code> yang sama 2x atau lebih (misal: karena network timeout atau retry logic), API akan:
                            </p>
                            <ul className="space-y-3 ml-6">
                                <li className="flex gap-3 text-on-surface-variant">
                                    <span className="material-symbols-outlined text-tertiary text-[20px]">check_circle</span>
                                    <span>Return HTTP 200 (Success) dengan data order yang sudah ada</span>
                                </li>
                                <li className="flex gap-3 text-on-surface-variant">
                                    <span className="material-symbols-outlined text-tertiary text-[20px]">check_circle</span>
                                    <span>Tambahan field <code className="font-mono text-primary">"isDuplicate": true</code> sebagai indicator</span>
                                </li>
                                <li className="flex gap-3 text-on-surface-variant">
                                    <span className="material-symbols-outlined text-tertiary text-[20px]">check_circle</span>
                                    <span><strong>Saldo TIDAK dipotong lagi</strong> (order hanya diproses 1x)</span>
                                </li>
                            </ul>
                            <div className="bg-tertiary/10 border-l-4 border-tertiary p-4 rounded-r-lg">
                                <p className="text-sm text-on-surface-variant">
                                    <strong>Use Case:</strong> Network timeout, retry logic, atau accidental double-click. Sistem kami menjamin order dengan <code className="font-mono">referenceNumber</code> yang sama hanya diproses sekali.
                                </p>
                            </div>
                        </div>

                        <div className="divider-gradient my-12"></div>
                        
                        <div className="space-y-6">
                            <h3 className="font-headline-md text-headline-md text-on-surface flex items-center gap-3">
                                <span className="material-symbols-outlined text-error">error</span>
                                Order Gagal (Failure Response)
                            </h3>
                            <p className="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                                Jika order gagal karena provider error (maintenance, saldo habis, dll), API akan return:
                            </p>
                            
                            <div className="bg-error/10 border border-error/20 rounded-xl p-6">
                                <h4 className="text-on-surface font-bold mb-3 text-sm">Contoh Failure Response (HTTP 400)</h4>
                                <div className="code-block p-4 rounded-lg bg-surface-container-highest/50">
                                    <pre className="text-xs text-on-surface leading-relaxed overflow-x-auto">
<span className="text-error">{"{"}</span>
  <span className="text-primary-container">"error"</span>: <span className="text-error">true</span>,
  <span className="text-primary-container">"code"</span>: <span className="text-on-surface">400</span>,
  <span className="text-primary-container">"message"</span>: <span className="text-tertiary">"Order Failed"</span>,
  <span className="text-primary-container">"data"</span>: {"{"}
    <span className="text-primary-container">"invoiceNumber"</span>: <span className="text-secondary">null</span>,
    <span className="text-primary-container">"referenceNumber"</span>: <span className="text-tertiary">"ORDER-QA-001"</span>,
    <span className="text-primary-container">"code"</span>: <span className="text-tertiary">"pubgm-60uc"</span>,
    <span className="text-primary-container">"user_id"</span>: <span className="text-tertiary">"12345678"</span>,
    <span className="text-primary-container">"zone_id"</span>: <span className="text-tertiary">"1234"</span>,
    <span className="text-primary-container">"price"</span>: <span className="text-on-surface">15000</span>,
    <span className="text-primary-container">"buyer_last_saldo"</span>: <span className="text-on-surface">500000</span>,
    <span className="text-primary-container">"status"</span>: <span className="text-error">"failed"</span>,
    <span className="text-primary-container">"message"</span>: <span className="text-tertiary">"Provider sedang gangguan"</span>
  {"}"}
{"}"}</pre>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div className="glass-panel p-4 rounded-lg">
                                    <p className="text-sm font-semibold text-on-surface mb-2">✓ Key Points:</p>
                                    <ul className="text-xs text-on-surface-variant space-y-1 list-disc list-inside">
                                        <li>Struktur sama dengan success response (uniform parsing)</li>
                                        <li><code className="font-mono">invoiceNumber</code> akan <code>null</code></li>
                                        <li><code className="font-mono">buyer_last_saldo</code> tidak berubah (no charge)</li>
                                    </ul>
                                </div>
                                <div className="glass-panel p-4 rounded-lg">
                                    <p className="text-sm font-semibold text-on-surface mb-2">⚡ Error Message Sanitization:</p>
                                    <ul className="text-xs text-on-surface-variant space-y-1 list-disc list-inside">
                                        <li>Provider error di-sanitize untuk security</li>
                                        <li>Kata sensitif (api_key, token) dihapus</li>
                                        <li>Max 200 karakter</li>
                                    </ul>
                                </div>
                            </div>

                            <p className="font-body-md text-body-md text-on-surface-variant leading-relaxed mt-4">
                                Anda bisa <strong>retry</strong> order yang gagal dengan <code className="font-mono">referenceNumber</code> yang sama tanpa risiko double charge.
                            </p>
                        </div>
                    </section>
                    
                    <section className="bg-surface-container-lowest/50 p-container-margin sticky top-16 h-[calc(100vh-64px)] overflow-y-auto border-l border-white/5">
                        <div className="max-w-xl mx-auto">
                            <div>
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Contoh Request</span>
                                    <button id="copy-btn-curl-code" className="flex items-center gap-1.5 text-[12px] text-primary hover:text-white transition-colors" onClick={() => copyCode('curl-code')}>
                                        <span className="material-symbols-outlined text-[14px]">content_copy</span> Copy
                                    </button>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden group">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-primary/50"></div>
                                    <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto" id="curl-code">
<span className="text-tertiary">curl</span> --request POST \
  --url https://api.namadomain.com{env === 'live' ? '/api/v1/order' : '/api/v1/sandbox/order'} \
  --header <span className="text-secondary">'Authorization: Bearer YOUR_TOKEN'</span> \
  --header <span className="text-secondary">'Content-Type: application/json'</span> \
  --data <span className="text-primary-container">{`'{
    "code": "pubgm-60uc",
    "referenceNumber": "ORDER-QA-001",
    "user_id": "12345678",
    "zone_id": "1234"
}'`}</span></pre>
                                </div>
                            </div>
                            
                            <div className="mt-8">
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Contoh Response (200 OK)</span>
                                    <div className="flex gap-4">
                                        <span className="text-[12px] text-tertiary flex items-center gap-1"><span className="w-2 h-2 rounded-full bg-tertiary"></span> 200 OK</span>
                                    </div>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-tertiary/50"></div>
                                    <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto">
<span className="text-on-surface">{`{`}</span>
  <span className="text-primary-container">"error"</span>: <span className="text-secondary">false</span>,
  <span className="text-primary-container">"code"</span>: <span className="text-on-surface">200</span>,
  <span className="text-primary-container">"message"</span>: <span className="text-tertiary">"Success"</span>,
  <span className="text-primary-container">"data"</span>: {`{`}
    <span className="text-primary-container">"invoiceNumber"</span>: <span className="text-tertiary">"{env === 'live' ? 'WEJIZY-H2H-260607143012ABC123' : 'WEJIZY-SBX143012ABCD'}"​</span>,
    <span className="text-primary-container">"referenceNumber"</span>: <span className="text-tertiary">"ORDER-QA-001"</span>,
    <span className="text-primary-container">"code"</span>: <span className="text-tertiary">"pubgm-60uc"</span>,
    <span className="text-primary-container">"user_id"</span>: <span className="text-tertiary">"12345678"</span>,
    <span className="text-primary-container">"zone_id"</span>: <span className="text-tertiary">"1234"</span>,
    <span className="text-primary-container">"price"</span>: <span className="text-on-surface">15000</span>,
    <span className="text-primary-container">"buyer_last_saldo"</span>: <span className="text-on-surface">485000</span>,
    <span className="text-primary-container">"status"</span>: <span className="text-tertiary">"pending"</span>,
    <span className="text-primary-container">"message"</span>: <span className="text-secondary">null</span>
  {`}`}
<span className="text-on-surface">{`}`}</span></pre>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            );
        }

        if (activeTab === 'status') {
            return (
                <div className="grid grid-cols-1 xl:grid-cols-2 gap-0 min-h-[calc(100vh-64px)]">
                    <section className="p-container-margin border-r border-white/5 max-w-4xl mx-auto xl:mx-0">
                        <header className="mb-12">
                            <div className="flex items-center gap-3 mb-4">
                                <span className="bg-tertiary/10 text-tertiary px-3 py-1 rounded text-[12px] font-bold uppercase tracking-widest border border-tertiary/20">POST</span>
                                <code className="text-primary font-mono text-lg">{env === 'live' ? '/api/v1/status-order/{invoice}' : '/api/v1/sandbox/status-order/{invoice}'}</code>
                            </div>
                            <h2 className="font-headline-lg text-headline-lg text-on-surface mb-4">Status Pesanan</h2>
                            <p className="font-body-lg text-body-lg text-on-surface-variant leading-relaxed">
                                Mengecek status pesanan menggunakan Nomor Invoice yang dikembalikan saat pembuatan order (Endpoint Pesanan).
                            </p>
                        </header>

                        <div className="bg-error/20 border-l-4 border-error p-6 rounded-r-lg mb-8">
                            <h4 className="text-error font-bold mb-2 flex items-center gap-2">
                                <span className="material-symbols-outlined text-[20px]">warning</span> 
                                BREAKING CHANGE (v2.4)
                            </h4>
                            <p className="text-on-surface text-sm mb-3 leading-relaxed">
                                Field <code className="bg-error/20 px-2 py-1 rounded text-error font-mono font-bold">userData</code> (pipe-separated string) telah <strong>dihapus</strong> dan diganti dengan field terpisah:
                            </p>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                                <div className="bg-surface-container/50 rounded-lg p-3">
                                    <p className="text-xs font-semibold text-error mb-2">❌ Old Format (v2.3):</p>
                                    <code className="text-xs text-on-surface-variant font-mono">
                                        "userData": "12345678|1234"
                                    </code>
                                </div>
                                <div className="bg-surface-container/50 rounded-lg p-3">
                                    <p className="text-xs font-semibold text-primary mb-2">✅ New Format (v2.4):</p>
                                    <code className="text-xs text-on-surface-variant font-mono">
                                        "user_id": "12345678",<br/>
                                        "zone_id": "1234"
                                    </code>
                                </div>
                            </div>
                            <p className="text-on-surface-variant text-sm leading-relaxed">
                                <strong>Migration:</strong> Update parsing logic dari <code className="font-mono">userData.split("|")</code> menjadi akses langsung <code className="font-mono">user_id</code> dan <code className="font-mono">zone_id</code>.
                            </p>
                        </div>
                        
                        <div className="mb-12">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6">URL Parameter</h3>
                            <div className="glass-panel rounded-xl overflow-hidden">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-white/5 border-b border-white/10">
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Parameter</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Tipe</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Deskripsi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-white/5">
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm">invoice</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Nomor Invoice pesanan Anda.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="mb-12 mt-8">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6">Parameter Response</h3>
                            <div className="glass-panel rounded-xl overflow-hidden">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-white/5 border-b border-white/10">
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Parameter</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Tipe Data</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Deskripsi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-white/5">
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm">success</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>Boolean</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Indikator kesuksesan proses pengecekan status.</td>
                                        </tr>
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm">status</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Status pesanan terkini (contoh: <code>pending</code>, <code>success</code>, <code>failed</code>, <code>processing</code>).</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                    
                    <section className="bg-surface-container-lowest/50 p-container-margin sticky top-16 h-[calc(100vh-64px)] overflow-y-auto border-l border-white/5">
                        <div className="max-w-xl mx-auto">
                            <div className="mb-8">
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Contoh Request</span>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden group">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-primary/50"></div>
                                    <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto">
<span className="text-tertiary">curl</span> --request POST \
  --url https://api.namadomain.com{env === 'live' ? '/api/v1/status-order/INV-123456' : '/api/v1/sandbox/status-order/INV-SANDBOX-123'} \
  --header <span className="text-secondary">'Authorization: Bearer YOUR_TOKEN'</span> \
  --header <span className="text-secondary">'Content-Type: application/json'</span> \
  --data <span className="text-primary-container">'{`{}`}'</span></pre>
                                </div>
                            </div>
                            
                            <div>
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Contoh Response</span>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-tertiary/50"></div>
                                    <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto">
<span className="text-on-surface">{`{`}</span>
  <span className="text-primary-container">"error"</span>: <span className="text-secondary">false</span>,
  <span className="text-primary-container">"code"</span>: <span className="text-on-surface">200</span>,
  <span className="text-primary-container">"message"</span>: <span className="text-tertiary">"Success"</span>,
  <span className="text-primary-container">"data"</span>: {`{`}
    <span className="text-primary-container">"invoiceNumber"</span>: <span className="text-tertiary">"WEJIZY-H2H-260607ABCD1234"</span>,
    <span className="text-primary-container">"productName"</span>: <span className="text-tertiary">"60 UC"</span>,
    <span className="text-primary-container">"user_id"</span>: <span className="text-tertiary">"12345678"</span>,
    <span className="text-primary-container">"zone_id"</span>: <span className="text-tertiary">"1234"</span>,
    <span className="text-primary-container">"statusCode"</span>: <span className="text-tertiary">"success"</span>,
    <span className="text-primary-container">"sn"</span>: <span className="text-secondary">null</span>,
    <span className="text-primary-container">"keteranganSn"</span>: <span className="text-secondary">null</span>
  {`}`}
<span className="text-on-surface">{`}`}</span></pre>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            );
        }

        if (activeTab === 'sandbox') {
            return (
                <div className="grid grid-cols-1 xl:grid-cols-2 gap-0 min-h-[calc(100vh-64px)]">
                    <section className="p-container-margin border-r border-white/5 max-w-4xl mx-auto xl:mx-0">
                        <header className="mb-12">
                            <div className="flex items-center gap-3 mb-4">
                                <span className="bg-tertiary/10 text-tertiary px-3 py-1 rounded text-[12px] font-bold uppercase tracking-widest border border-tertiary/20">POST</span>
                                <code className="text-tertiary font-mono text-lg">/api/v1/sandbox/simulate-status/{`{invoice}`}</code>
                            </div>
                            <h2 className="font-headline-lg text-headline-lg text-on-surface mb-4">Sandbox Simulator</h2>
                            <p className="font-body-lg text-body-lg text-on-surface-variant leading-relaxed">
                                Endpoint eksklusif Sandbox untuk manually trigger status change. <strong>Perfect untuk testing webhook integration</strong> tanpa menunggu real order completion.
                            </p>
                        </header>

                        <div className="bg-tertiary/20 border-l-4 border-tertiary p-6 rounded-r-lg mb-8">
                            <h4 className="text-tertiary font-bold mb-2 flex items-center gap-2">
                                <span className="material-symbols-outlined text-[18px]">science</span> 
                                Sandbox Only Feature
                            </h4>
                            <p className="text-on-surface-variant text-sm leading-relaxed">
                                Endpoint ini <strong>HANYA tersedia di Sandbox</strong>. Live environment tidak memiliki simulate-status karena order processing real-time dari provider.
                            </p>
                        </div>

                        <div className="mb-12">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6">URL Parameters</h3>
                            <div className="glass-panel rounded-xl overflow-hidden">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-white/5 border-b border-white/10">
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Parameter</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Tipe</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Deskripsi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-white/5">
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm">invoice</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">Invoice number dari sandbox order yang ingin di-simulate</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="mb-12">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6">Request Body</h3>
                            <div className="glass-panel rounded-xl overflow-hidden">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-white/5 border-b border-white/10">
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Field</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Tipe</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Values</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-white/5">
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm">status</td>
                                            <td className="p-4 text-on-surface-variant text-sm"><code>String</code></td>
                                            <td className="p-4 text-on-surface-variant text-sm">
                                                <code className="bg-surface-container-highest px-1.5 py-0.5 rounded mr-2">pending</code>
                                                <code className="bg-surface-container-highest px-1.5 py-0.5 rounded mr-2">processing</code>
                                                <code className="bg-surface-container-highest px-1.5 py-0.5 rounded mr-2">success</code>
                                                <code className="bg-surface-container-highest px-1.5 py-0.5 rounded mr-2">failed</code>
                                                <code className="bg-surface-container-highest px-1.5 py-0.5 rounded">cancelled</code>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="space-y-6">
                            <h3 className="font-headline-md text-headline-md text-on-surface">Use Cases</h3>
                            <div className="grid gap-4">
                                <div className="glass-panel p-5 rounded-xl">
                                    <div className="flex items-start gap-3">
                                        <span className="material-symbols-outlined text-tertiary text-[24px]">webhook</span>
                                        <div>
                                            <h4 className="font-bold text-on-surface mb-2">Test Webhook Integration</h4>
                                            <p className="text-sm text-on-surface-variant leading-relaxed">
                                                Trigger status change → webhook fired → verify your server receives callback correctly. No need to wait for real provider processing.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div className="glass-panel p-5 rounded-xl">
                                    <div className="flex items-start gap-3">
                                        <span className="material-symbols-outlined text-tertiary text-[24px]">bug_report</span>
                                        <div>
                                            <h4 className="font-bold text-on-surface mb-2">Debug Status Handling</h4>
                                            <p className="text-sm text-on-surface-variant leading-relaxed">
                                                Test semua status scenarios (success, failed, cancelled) untuk ensure UI/logic Anda handle dengan benar.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="bg-surface-container-lowest/50 p-container-margin sticky top-16 h-[calc(100vh-64px)] overflow-y-auto border-l border-white/5">
                        <div className="max-w-xl mx-auto">
                            <div className="mb-8">
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Contoh Request</span>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden group">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-tertiary/50"></div>
                                    <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto">
<span className="text-tertiary">curl</span> --request POST \
  --url https://sandbox-api.namadomain.com/api/v1/sandbox/simulate-status/WEJIZY-SBX-123 \
  --header <span className="text-secondary">'Authorization: Bearer YOUR_SANDBOX_TOKEN'</span> \
  --header <span className="text-secondary">'Content-Type: application/json'</span> \
  --data <span className="text-primary-container">{`'{ "status": "success" }'`}</span></pre>
                                </div>
                            </div>

                            <div>
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Contoh Response</span>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-tertiary/50"></div>
                                    <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto">
<span className="text-on-surface">{`{`}</span>
  <span className="text-primary-container">"error"</span>: <span className="text-secondary">false</span>,
  <span className="text-primary-container">"code"</span>: <span className="text-on-surface">200</span>,
  <span className="text-primary-container">"message"</span>: <span className="text-tertiary">"Status updated successfully"</span>,
  <span className="text-primary-container">"data"</span>: {`{`}
    <span className="text-primary-container">"invoiceNumber"</span>: <span className="text-tertiary">"WEJIZY-SBX-123"</span>,
    <span className="text-primary-container">"old_status"</span>: <span className="text-tertiary">"pending"</span>,
    <span className="text-primary-container">"new_status"</span>: <span className="text-tertiary">"success"</span>,
    <span className="text-primary-container">"webhook_triggered"</span>: <span className="text-secondary">true</span>
  {`}`}
<span className="text-on-surface">{`}`}</span></pre>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            );
        }

        if (activeTab === 'webhooks') {
            return (
                <div className="grid grid-cols-1 xl:grid-cols-2 gap-0 min-h-[calc(100vh-64px)]">
                    <section className="p-container-margin border-r border-white/5 max-w-4xl mx-auto xl:mx-0">
                        <header className="mb-12">
                            <h2 className="font-headline-lg text-headline-lg text-on-surface mb-4">Webhooks</h2>
                            <p className="font-body-lg text-body-lg text-on-surface-variant leading-relaxed mb-6">
                                Sistem kami secara otomatis mengirim notifikasi webhook ke server Anda saat status order berubah. Perfect untuk real-time order updates tanpa polling.
                            </p>
                        </header>

                        <div className="mb-12">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6">Configuration</h3>
                            <div className="bg-primary/10 border-l-4 border-primary p-5 rounded-r-xl mb-4">
                                <p className="text-sm text-on-surface leading-relaxed mb-2">
                                    <strong>Setup Location:</strong> Reseller Hub → Credentials → Webhook Configuration
                                </p>
                                <ul className="text-sm text-on-surface-variant space-y-1 ml-4">
                                    <li>• <strong>Webhook URL:</strong> Your server endpoint (HTTPS recommended)</li>
                                    <li>• <strong>Webhook Secret:</strong> Generate random string untuk signature validation</li>
                                </ul>
                            </div>
                        </div>

                        <div className="mb-12">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6">Webhook Events</h3>
                            <div className="glass-panel rounded-xl overflow-hidden">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-white/5 border-b border-white/10">
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Event</th>
                                            <th className="p-4 font-label-md text-label-md text-on-surface-variant">Trigger</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-white/5">
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm">order.success</td>
                                            <td className="p-4 text-on-surface-variant text-sm">Order berhasil diproses provider</td>
                                        </tr>
                                        <tr className="hover:bg-primary/5 transition-colors">
                                            <td className="p-4 font-mono text-primary text-sm">order.failed</td>
                                            <td className="p-4 text-on-surface-variant text-sm">Order gagal permanen</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="mb-12">
                            <h3 className="font-headline-md text-headline-md text-on-surface mb-6">Payload Structure</h3>
                            <div className="bg-surface-container-highest/50 rounded-xl p-5">
                                <pre className="text-xs text-on-surface-variant font-mono leading-relaxed overflow-x-auto">
{`{
  "event": "order.success",
  "timestamp": "2026-06-08T14:30:12Z",
  "data": {
    "invoiceNumber": "WEJIZY-H2H-123",
    "referenceNumber": "ORDER-QA-001",
    "status": "success",
    "productName": "86 Diamonds",
    "user_id": "12345678",
    "zone_id": "1234",
    "price": 18500,
    "sn": "SN123456789",
    "completedAt": "2026-06-08T14:30:10Z"
  }
}`}
                                </pre>
                            </div>
                        </div>

                        <div className="space-y-6">
                            <h3 className="font-headline-md text-headline-md text-on-surface">Signature Verification</h3>
                            <div className="bg-error/10 border-l-4 border-error p-5 rounded-r-xl mb-4">
                                <h4 className="text-error font-bold mb-2">⚠️ Security Critical</h4>
                                <p className="text-sm text-on-surface-variant leading-relaxed">
                                    <strong>ALWAYS verify webhook signature</strong> untuk mencegah spoofing attacks. Reject webhooks tanpa valid signature.
                                </p>
                            </div>

                            <div>
                                <h4 className="font-semibold text-on-surface mb-3">Verification Header</h4>
                                <div className="bg-surface-container-highest/50 rounded-lg p-4 mb-4">
                                    <code className="text-sm font-mono text-primary">
                                        X-Callback-Signature: sha256=&lt;signature&gt;
                                    </code>
                                </div>
                            </div>

                            <div>
                                <h4 className="font-semibold text-on-surface mb-3">Response Requirements</h4>
                                <ul className="space-y-2 text-sm text-on-surface-variant ml-6">
                                    <li className="flex gap-2">
                                        <span className="text-primary">✓</span>
                                        <span>Return HTTP 200-299 untuk success</span>
                                    </li>
                                    <li className="flex gap-2">
                                        <span className="text-primary">✓</span>
                                        <span>Respond dalam 10 detik (otherwise timeout)</span>
                                    </li>
                                    <li className="flex gap-2">
                                        <span className="text-primary">✓</span>
                                        <span>Be idempotent (handle duplicate webhooks)</span>
                                    </li>
                                </ul>
                            </div>

                            <div className="bg-tertiary/10 border-l-4 border-tertiary p-5 rounded-r-xl">
                                <h4 className="font-bold text-on-surface mb-2 flex items-center gap-2">
                                    <span className="material-symbols-outlined text-tertiary text-[18px]">autorenew</span>
                                    Auto-Retry Logic
                                </h4>
                                <p className="text-sm text-on-surface-variant leading-relaxed">
                                    Jika webhook gagal (non-2xx response atau timeout), sistem akan retry 3x dengan exponential backoff (1s, 5s, 15s).
                                </p>
                            </div>
                        </div>
                    </section>

                    <section className="bg-surface-container-lowest/50 p-container-margin sticky top-16 h-[calc(100vh-64px)] overflow-y-auto border-l border-white/5">
                        <div className="max-w-xl mx-auto">
                            <div className="mb-8">
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Verification Code (PHP)</span>
                                </div>
                                <div className="code-block p-6 rounded-xl border border-white/10 relative overflow-hidden">
                                    <div className="absolute top-0 left-0 w-1 h-full bg-primary/50"></div>
                                    <pre className="text-sm text-on-primary-fixed-variant leading-6 overflow-x-auto">
<span className="text-tertiary">// Get raw payload</span>
<span className="text-primary">$payload</span> = file_get_contents(<span className="text-secondary">'php://input'</span>);

<span className="text-tertiary">// Get signature from header</span>
<span className="text-primary">$signature</span> = <span className="text-primary">$_SERVER</span>[<span className="text-secondary">'HTTP_X_CALLBACK_SIGNATURE'</span>];
<span className="text-primary">$signature</span> = str_replace(<span className="text-secondary">'sha256='</span>, <span className="text-secondary">''</span>, <span className="text-primary">$signature</span>);

<span className="text-tertiary">// Calculate expected signature</span>
<span className="text-primary">$expected</span> = hash_hmac(
    <span className="text-secondary">'sha256'</span>, 
    <span className="text-primary">$payload</span>, 
    <span className="text-primary">$webhookSecret</span>
);

<span className="text-tertiary">// Verify with timing-safe comparison</span>
<span className="text-on-surface">if</span> (!hash_equals(<span className="text-primary">$expected</span>, <span className="text-primary">$signature</span>)) {`{`}
    http_response_code(<span className="text-on-surface">403</span>);
    <span className="text-on-surface">die</span>(<span className="text-secondary">'Invalid signature'</span>);
{`}`}

<span className="text-tertiary">// Safe to process</span>
<span className="text-primary">$data</span> = json_decode(<span className="text-primary">$payload</span>, <span className="text-on-surface">true</span>);
<span className="text-tertiary">// Your business logic here...</span></pre>
                                </div>
                            </div>

                            <div>
                                <div className="flex justify-between items-center mb-3">
                                    <span className="text-[11px] font-bold text-on-surface-variant/60 uppercase tracking-widest">Testing with Sandbox</span>
                                </div>
                                <div className="bg-tertiary/10 rounded-xl p-5">
                                    <p className="text-sm text-on-surface-variant leading-relaxed mb-3">
                                        Gunakan <strong>Sandbox Simulator</strong> endpoint untuk trigger test webhooks:
                                    </p>
                                    <code className="text-xs font-mono text-tertiary block">
                                        POST /api/v1/sandbox/simulate-status/{`{invoice}`}
                                    </code>
                                    <p className="text-xs text-on-surface-variant/60 mt-3">
                                        → Status change → Webhook fired → Verify your server
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            );
        }

        return (
            <div className="flex items-center justify-center min-h-[calc(100vh-64px)]">
                <div className="text-center">
                    <span className="material-symbols-outlined text-[64px] text-on-surface-variant/30 mb-4 block">construction</span>
                    <h2 className="text-headline-md text-on-surface-variant">Konten sedang dalam pengembangan</h2>
                </div>
            </div>
        );
    };

    return (
        <>
            <Head title="API Documentation" />

            <aside className="fixed left-0 top-0 h-full w-[280px] border-r border-white/10 bg-surface-container-low/40 backdrop-blur-xl flex flex-col py-section-gap px-container-margin z-50">
                <div className="mb-8">
                    <h1 className="font-headline-md text-headline-md font-bold text-on-surface">API Docs</h1>
                    <p className="font-label-sm text-label-sm text-on-surface-variant opacity-60">v2.4.0-stable</p>
                </div>
                
                <nav className="flex-1 space-y-2 overflow-y-auto">
                    {renderSidebarLink('introduction', 'info', 'Pendahuluan')}
                    {renderSidebarLink('authentication', 'lock', 'Autentikasi')}
                    
                    <div className="pt-4 pb-2">
                        <span className="px-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant/40">Endpoints</span>
                    </div>
                    
                    {renderSidebarLink('balance', 'account_balance_wallet', 'Saldo')}
                    {renderSidebarLink('category', 'category', 'Kategori')}
                    {renderSidebarLink('variant', 'list_alt', 'Layanan')}
                    {renderSidebarLink('order', 'shopping_cart', 'Pesanan')}
                    {renderSidebarLink('status', 'query_stats', 'Status Pesanan')}
                    
                    <div className="pt-4 pb-2">
                        <span className="px-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant/40">Tools</span>
                    </div>
                    
                    {renderSidebarLink('sandbox', 'science', 'Sandbox')}
                    {renderSidebarLink('webhooks', 'webhook', 'Webhooks')}
                </nav>
                
                <div className="mt-auto space-y-2 border-t border-white/5 pt-6">
                    <a className="flex items-center gap-3 px-4 py-2 rounded-lg text-on-surface-variant hover:text-on-surface transition-colors" href="#">
                        <span className="material-symbols-outlined">help_outline</span>
                        <span className="font-label-md text-label-md">Bantuan</span>
                    </a>
                </div>
            </aside>

            <header className="sticky top-0 z-40 h-16 ml-[280px] bg-background/80 backdrop-blur-md border-b border-white/5 px-container-margin flex justify-between items-center">
                <div className="flex items-center gap-4">
                    <div className="flex items-center gap-2 text-on-surface-variant font-label-md text-label-md">
                        <span>{appName}</span>
                        <span className="material-symbols-outlined text-[16px]">chevron_right</span>
                        <span className="text-on-surface font-semibold">Dokumentasi</span>
                    </div>
                </div>
                <div className="flex items-center gap-6">
                    <div className="flex bg-surface-container-highest p-1 rounded-lg">
                        <button 
                            className={`px-4 py-1.5 rounded-md text-label-md font-label-md transition-all duration-200 ${env === 'live' ? 'bg-primary text-on-primary neon-glow' : 'text-on-surface-variant hover:text-on-surface'}`}
                            onClick={() => setEnv('live')}
                        >
                            Live
                        </button>
                        <button 
                            className={`px-4 py-1.5 rounded-md text-label-md font-label-md transition-all duration-200 ${env === 'sandbox' ? 'bg-primary text-on-primary neon-glow' : 'text-on-surface-variant hover:text-on-surface'}`}
                            onClick={() => setEnv('sandbox')}
                        >
                            Sandbox
                        </button>
                    </div>
                </div>
            </header>

            <main className="ml-[280px]">
                {renderContent()}
            </main>
        </>
    );
}

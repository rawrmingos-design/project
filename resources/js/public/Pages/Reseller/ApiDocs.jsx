import React, { useState, useEffect, useRef } from 'react';
import { Head } from '@inertiajs/react';
import ResellerLayout from '@/public/Layouts/ResellerLayout.jsx';

const SECTIONS = [
    { id: 'authentication', label: 'Authentication' },
    { id: 'endpoints', label: 'Endpoints' },
    { id: 'data-fields', label: 'Identifiers (user_id & zone_id)' },
    { id: 'webhook', label: 'Webhooks & Callbacks' },
    { id: 'error-codes', label: 'Error Codes Reference' },
];

function CodeBlock({ children, language = 'bash' }) {
    const [copied, setCopied] = useState(false);
    const handleCopy = () => {
        navigator.clipboard.writeText(children.trim());
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };
    return (
        <div className="relative group mt-3 mb-6 rounded-xl overflow-hidden shadow-2xl ring-1 ring-white/10">
            <div className="flex items-center px-4 py-2 bg-slate-900/90 backdrop-blur border-b border-white/10">
                <div className="flex gap-1.5">
                    <div className="w-3 h-3 rounded-full bg-rose-500/80"></div>
                    <div className="w-3 h-3 rounded-full bg-amber-500/80"></div>
                    <div className="w-3 h-3 rounded-full bg-emerald-500/80"></div>
                </div>
                <span className="ml-4 text-xs font-medium text-slate-400 uppercase tracking-wider">{language}</span>
            </div>
            <pre className="bg-[#0f111a] text-slate-300 p-5 overflow-x-auto text-sm font-mono leading-relaxed">
                <code>{children.trim()}</code>
            </pre>
            <button
                onClick={handleCopy}
                className={`absolute top-12 right-4 transition-all duration-200 px-3 py-1.5 rounded-md text-xs font-medium shadow-sm backdrop-blur-md ${
                    copied 
                    ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/50' 
                    : 'bg-white/5 text-slate-300 border border-white/10 opacity-0 group-hover:opacity-100 hover:bg-white/10'
                }`}
            >
                {copied ? '✓ Copied' : 'Copy Code'}
            </button>
        </div>
    );
}

function Badge({ children, variant = 'blue' }) {
    const variants = {
        blue: 'bg-blue-500/10 text-blue-600 border border-blue-500/20',
        green: 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20',
        amber: 'bg-amber-500/10 text-amber-600 border border-amber-500/20',
        red: 'bg-rose-500/10 text-rose-600 border border-rose-500/20',
        gray: 'bg-slate-500/10 text-slate-600 border border-slate-500/20',
        purple: 'bg-purple-500/10 text-purple-600 border border-purple-500/20',
    };
    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold tracking-wide ${variants[variant]}`}>
            {children}
        </span>
    );
}

function Section({ id, title, children }) {
    return (
        <section id={id} className="scroll-mt-32 mb-16 group">
            <h2 className="text-2xl font-bold text-slate-900 border-b border-slate-200 pb-4 mb-8 flex items-center gap-3 transition-colors group-hover:text-purple-600">
                <span className="text-purple-500 opacity-50 text-xl font-light">#</span>
                {title}
            </h2>
            <div className="prose prose-slate max-w-none prose-headings:font-semibold prose-a:text-purple-600 hover:prose-a:text-purple-500">
                {children}
            </div>
        </section>
    );
}

function EnvironmentCard({ title, baseUrl, isSandbox, hint }) {
    return (
        <div className={`relative overflow-hidden rounded-2xl p-6 border transition-all duration-300 hover:shadow-xl ${
            isSandbox 
                ? 'bg-gradient-to-br from-amber-50 to-orange-50 border-amber-200/50 hover:border-amber-300' 
                : 'bg-gradient-to-br from-indigo-50 to-purple-50 border-indigo-200/50 hover:border-indigo-300'
        }`}>
            <div className="absolute top-0 right-0 p-4 opacity-10">
                <svg className="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
                    {isSandbox 
                        ? <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                        : <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                    }
                </svg>
            </div>
            
            <div className="relative z-10">
                <div className="flex items-center gap-3 mb-6">
                    <h3 className={`text-lg font-bold ${isSandbox ? 'text-amber-900' : 'text-indigo-900'}`}>{title}</h3>
                    <Badge variant={isSandbox ? 'amber' : 'purple'}>{isSandbox ? 'Testing' : 'Production'}</Badge>
                </div>
                
                <div className="space-y-4">
                    <div>
                        <span className={`block text-xs font-bold uppercase tracking-wider mb-1 ${isSandbox ? 'text-amber-700/70' : 'text-indigo-700/70'}`}>Base URL</span>
                        <code className={`block text-sm font-mono p-2.5 rounded-lg border bg-white/60 backdrop-blur-sm ${
                            isSandbox ? 'text-amber-800 border-amber-200' : 'text-indigo-800 border-indigo-200'
                        }`}>{baseUrl}</code>
                    </div>
                    
                    <div>
                        <span className={`block text-xs font-bold uppercase tracking-wider mb-1 ${isSandbox ? 'text-amber-700/70' : 'text-indigo-700/70'}`}>API Key Hint</span>
                        <div className="flex items-center justify-between">
                            <code className="text-sm font-mono font-medium text-slate-700">{hint ?? 'Not configured'}</code>
                            <span className="text-xs text-slate-500">Rotate via Credentials</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function ApiDocs({ canonical_url, live_base_url, sandbox_base_url, live, sandbox }) {
    const [activeSection, setActiveSection] = useState('authentication');
    const observerRef = useRef(null);

    useEffect(() => {
        const sections = SECTIONS.map(s => document.getElementById(s.id)).filter(Boolean);
        observerRef.current = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) setActiveSection(entry.target.id);
                });
            },
            { rootMargin: '-100px 0px -60% 0px', threshold: 0 }
        );
        sections.forEach(s => observerRef.current.observe(s));
        return () => observerRef.current?.disconnect();
    }, []);

    const scrollTo = (id) => {
        document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    return (
        <ResellerLayout>
            <Head title="API Documentation — Reseller Hub" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                {/* Hero Header */}
                <div className="relative mb-16 p-8 rounded-3xl bg-slate-900 overflow-hidden shadow-2xl">
                    <div className="absolute inset-0 bg-gradient-to-r from-purple-500/20 to-indigo-500/20"></div>
                    <div className="absolute -top-24 -right-24 w-96 h-96 bg-purple-500/30 blur-3xl rounded-full"></div>
                    <div className="absolute bottom-0 left-10 w-72 h-72 bg-blue-500/20 blur-3xl rounded-full"></div>
                    
                    <div className="relative z-10">
                        <div className="flex items-center gap-3 mb-4">
                            <Badge variant="purple">Developer API</Badge>
                            <Badge variant="blue">v1.0</Badge>
                        </div>
                        <h1 className="text-4xl sm:text-5xl font-extrabold text-white tracking-tight mb-4">
                            API Reference
                        </h1>
                        <p className="text-lg text-slate-300 max-w-2xl leading-relaxed">
                            Integrasikan platform Anda dengan layanan Host-to-Host Egymarket. 
                            Dokumentasi ini telah diperbarui untuk mendukung arsitektur Bearer Token terbaru.
                        </p>
                    </div>
                </div>

                <div className="flex flex-col lg:flex-row gap-12 relative">
                    {/* Sticky Sidebar Navigation */}
                    <nav className="hidden lg:block w-64 shrink-0">
                        <div className="sticky top-28 bg-white/80 backdrop-blur-xl border border-slate-200/60 rounded-2xl p-5 shadow-sm">
                            <p className="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">On this page</p>
                            <ul className="space-y-1.5">
                                {SECTIONS.map(s => (
                                    <li key={s.id}>
                                        <button
                                            onClick={() => scrollTo(s.id)}
                                            className={`w-full flex items-center text-left text-sm px-4 py-2.5 rounded-xl transition-all duration-200 ${
                                                activeSection === s.id
                                                    ? 'bg-purple-50 text-purple-700 font-semibold shadow-sm'
                                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                            }`}
                                        >
                                            {activeSection === s.id && (
                                                <div className="w-1.5 h-1.5 rounded-full bg-purple-600 mr-2.5"></div>
                                            )}
                                            {s.label}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </nav>

                    {/* Main Content Area */}
                    <div className="flex-1 min-w-0 bg-white rounded-3xl p-8 lg:p-12 shadow-sm border border-slate-200/60">

                        {/* ── Section 1: Authentication ── */}
                        <Section id="authentication" title="Authentication">
                            <p className="text-slate-600 text-lg mb-8 leading-relaxed">
                                Autentikasi API menggunakan standar industri <strong className="text-slate-900 font-semibold">Bearer Token</strong>. 
                                Anda hanya perlu menyertakan satu header <code className="bg-slate-100 text-purple-700 px-1.5 py-0.5 rounded-md text-sm">Authorization</code> 
                                pada setiap request. Header lama seperti <del className="text-slate-400">X-Reseller-Integration-Code</del> sudah <strong>tidak diperlukan</strong>.
                            </p>

                            <div className="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
                                <EnvironmentCard 
                                    title="Live Environment" 
                                    baseUrl={live_base_url} 
                                    isSandbox={false} 
                                    hint={live?.api_key_hint} 
                                />
                                <EnvironmentCard 
                                    title="Sandbox Environment" 
                                    baseUrl={sandbox_base_url} 
                                    isSandbox={true} 
                                    hint={sandbox?.api_key_hint} 
                                />
                            </div>

                            <div className="bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl p-5 text-sm text-emerald-800 flex gap-4 items-start shadow-sm">
                                <svg className="w-6 h-6 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <div>
                                    <h4 className="font-bold text-emerald-900 text-base mb-1">IP Whitelist Enforcement</h4>
                                    <p className="opacity-90">Lingkungan Live mewajibkan alamat IP server Anda didaftarkan pada halaman Credentials demi keamanan. Request dari IP tidak terdaftar akan otomatis ditolak (HTTP 403).</p>
                                </div>
                            </div>
                        </Section>

                        {/* ── Section 2: Endpoints ── */}
                        <Section id="endpoints" title="Endpoints">
                            <div className="space-y-6">

                                {/* Balance Endpoint */}
                                <div className="border border-slate-200 rounded-2xl overflow-hidden shadow-sm transition-all hover:shadow-md">
                                    <div className="bg-slate-50 border-b border-slate-200 px-6 py-4 flex items-center gap-4">
                                        <Badge variant="blue">POST</Badge>
                                        <code className="text-base font-bold text-slate-800">/api/v1/balance</code>
                                        <span className="text-slate-500 text-sm ml-auto hidden sm:block">Cek saldo reseller</span>
                                    </div>
                                    <div className="p-6 bg-white">
                                        <CodeBlock>{`curl -X POST "${live_base_url}/balance" \\
  -H "Authorization: Bearer {API_KEY}" \\
  -H "Accept: application/json"`}</CodeBlock>
                                        <p className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 mt-4">Success Response</p>
                                        <CodeBlock language="json">{`{
  "error": false,
  "code": 200,
  "message": "Success",
  "data": {
    "name": "Nama Anda",
    "telp": "0812xxxx",
    "membership": "Member",
    "balance": 500000
  }
}`}</CodeBlock>
                                    </div>
                                </div>

                                {/* Order Endpoint */}
                                <div className="border border-slate-200 rounded-2xl overflow-hidden shadow-sm transition-all hover:shadow-md">
                                    <div className="bg-slate-50 border-b border-slate-200 px-6 py-4 flex items-center gap-4">
                                        <Badge variant="emerald">POST</Badge>
                                        <code className="text-base font-bold text-slate-800">/api/v1/order</code>
                                        <span className="text-slate-500 text-sm ml-auto hidden sm:block">Buat order baru</span>
                                    </div>
                                    <div className="p-6 bg-white">
                                        <div className="mb-6 overflow-hidden rounded-xl border border-slate-200 shadow-sm">
                                            <table className="w-full text-sm text-left">
                                                <thead className="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                                                    <tr>
                                                        <th className="px-4 py-3">Payload Field</th>
                                                        <th className="px-4 py-3">Type</th>
                                                        <th className="px-4 py-3">Required</th>
                                                        <th className="px-4 py-3">Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-slate-100">
                                                    <tr>
                                                        <td className="px-4 py-3 font-mono text-purple-700">code</td>
                                                        <td className="px-4 py-3 text-slate-500">string</td>
                                                        <td className="px-4 py-3"><Badge variant="red">Yes</Badge></td>
                                                        <td className="px-4 py-3 text-slate-600">Kode produk valid dari katalog /variant</td>
                                                    </tr>
                                                    <tr>
                                                        <td className="px-4 py-3 font-mono text-purple-700">referenceNumber</td>
                                                        <td className="px-4 py-3 text-slate-500">string</td>
                                                        <td className="px-4 py-3"><Badge variant="red">Yes</Badge></td>
                                                        <td className="px-4 py-3 text-slate-600">ID unik order dari sistem Anda (Idempotency Key)</td>
                                                    </tr>
                                                    <tr>
                                                        <td className="px-4 py-3 font-mono text-purple-700">user_id</td>
                                                        <td className="px-4 py-3 text-slate-500">string</td>
                                                        <td className="px-4 py-3"><Badge variant="red">Yes</Badge></td>
                                                        <td className="px-4 py-3 text-slate-600">ID akun game target / Nomor HP pembeli</td>
                                                    </tr>
                                                    <tr>
                                                        <td className="px-4 py-3 font-mono text-purple-700">zone_id</td>
                                                        <td className="px-4 py-3 text-slate-500">string | null</td>
                                                        <td className="px-4 py-3"><Badge variant="gray">Optional</Badge></td>
                                                        <td className="px-4 py-3 text-slate-600">Zone/Server ID (khusus game tertentu seperti MLBB)</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <CodeBlock>{`curl -X POST "${live_base_url}/order" \\
  -H "Authorization: Bearer {API_KEY}" \\
  -H "Content-Type: application/json" \\
  -H "Accept: application/json" \\
  -d '{
    "code": "ML-DIAMOND-100",
    "referenceNumber": "INV-2026060301",
    "user_id": "12345678",
    "zone_id": "9001"
  }'`}</CodeBlock>
                                    </div>
                                </div>

                                {/* Status Order Endpoint */}
                                <div className="border border-slate-200 rounded-2xl overflow-hidden shadow-sm transition-all hover:shadow-md">
                                    <div className="bg-slate-50 border-b border-slate-200 px-6 py-4 flex items-center gap-4">
                                        <Badge variant="blue">POST</Badge>
                                        <code className="text-base font-bold text-slate-800">/api/v1/status-order/{'{invoice}'}</code>
                                        <span className="text-slate-500 text-sm ml-auto hidden sm:block">Cek status order spesifik</span>
                                    </div>
                                    <div className="p-6 bg-white">
                                        <p className="text-slate-600 text-sm mb-4">
                                            Path parameter <code className="bg-slate-100 text-purple-700 px-1.5 py-0.5 rounded font-mono">invoice</code> adalah 
                                            nomor tagihan unik yang dikembalikan server kami pada response endpoint <code className="bg-slate-100 text-purple-700 px-1.5 py-0.5 rounded font-mono">/order</code>.
                                        </p>
                                        <CodeBlock>{`curl -X POST "${live_base_url}/status-order/WEJIZY-RAPI123456" \\
  -H "Authorization: Bearer {API_KEY}" \\
  -H "Accept: application/json"`}</CodeBlock>
                                    </div>
                                </div>
                                
                                <div className="bg-amber-50 rounded-xl p-5 text-sm text-amber-800 border border-amber-200/50 shadow-sm">
                                    <strong className="text-amber-900 block mb-1">Testing di Sandbox</strong>
                                    Di environment Sandbox, saldo Anda tidak akan terpotong dan transaksi tidak dikirim ke provider asli. 
                                    Anda dapat memanggil endpoint <code className="bg-amber-100 px-1.5 py-0.5 rounded-md font-mono text-amber-900">/api/v1/sandbox/simulate-status/{'{invoice}'}</code> untuk mengubah status order menjadi Sukses/Gagal untuk mengetes respon sistem Anda.
                                </div>
                            </div>
                        </Section>

                        {/* ── Section 3: Data Fields ── */}
                        <Section id="data-fields" title="Identifiers: user_id & zone_id">
                            <p className="text-slate-600 mb-6 leading-relaxed">
                                Keperluan pengisian target tujuan bervariasi bergantung pada kategori produk. Beberapa game membutuhkan dua parameter (User ID & Zone ID), sementara produk lain hanya satu.
                            </p>

                            <div className="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
                                <table className="w-full text-sm text-left">
                                    <thead className="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                                        <tr>
                                            <th className="px-5 py-4">Kategori Produk</th>
                                            <th className="px-5 py-4">Format user_id</th>
                                            <th className="px-5 py-4">Format zone_id</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 bg-white">
                                        {[
                                            ['Mobile Legends', 'User ID (contoh: 12345678)', <span className="text-rose-600 font-medium">Zone ID (contoh: 9001)</span>],
                                            ['Free Fire', 'Player ID / UID', <span className="text-slate-400 italic">kosong / null</span>],
                                            ['PUBG Mobile', 'Player ID', <span className="text-slate-400 italic">kosong / null</span>],
                                            ['Genshin Impact', 'UID Server', <span className="text-slate-400 italic">kosong / null</span>],
                                            ['Pulsa / Paket Data', 'Nomor HP (08xxx atau 62xxx)', <span className="text-slate-400 italic">kosong / null</span>],
                                            ['Voucher Game', 'Kode target / Email', <span className="text-slate-400 italic">kosong / null</span>],
                                        ].map(([category, uid, zid], i) => (
                                            <tr key={i} className="hover:bg-slate-50 transition-colors">
                                                <td className="px-5 py-4 font-medium text-slate-800">{category}</td>
                                                <td className="px-5 py-4 text-slate-600 font-mono text-xs bg-slate-50/50">{uid}</td>
                                                <td className="px-5 py-4 font-mono text-xs">{zid}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </Section>

                        {/* ── Section 4: Webhook ── */}
                        <Section id="webhook" title="Webhooks & Callbacks">
                            <p className="text-slate-600 mb-6 leading-relaxed">
                                Webhook adalah cara efisien bagi server kami memberi tahu server Anda seketika (real-time) 
                                saat status pesanan (Live/Sandbox) berubah mencapai status final (Sukses atau Gagal). 
                                Kami menggunakan HTTP POST dengan payload JSON terenkripsi HMAC-SHA256.
                            </p>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                                <div className="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                    <div className="font-mono text-xs font-bold text-purple-700 mb-2">h2h.order.updated</div>
                                    <p className="text-xs text-slate-600">Dipicu saat order di lingkungan Live berhasil dikonfirmasi atau dibatalkan oleh provider.</p>
                                </div>
                                <div className="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                    <div className="font-mono text-xs font-bold text-purple-700 mb-2">h2h.sandbox.order.updated</div>
                                    <p className="text-xs text-slate-600">Dipicu saat order Sandbox disimulasikan selesai/gagal via endpoint simulasi.</p>
                                </div>
                                <div className="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                    <div className="font-mono text-xs font-bold text-purple-700 mb-2">h2h.webhook.test</div>
                                    <p className="text-xs text-slate-600">Ping sintetis untuk menguji konfigurasi Webhook Secret dan konektivitas firewall.</p>
                                </div>
                            </div>

                            <h3 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                                <svg className="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Keamanan & Verifikasi Signature
                            </h3>
                            <p className="text-slate-600 text-sm mb-4">
                                Demi keamanan, abaikan semua HTTP POST ke Webhook URL Anda yang tidak memiliki header 
                                <code className="bg-slate-100 text-purple-700 px-1.5 py-0.5 mx-1 rounded font-mono">X-Callback-Signature</code> yang valid. 
                                Header ini di-generate menggunakan algoritma HMAC-SHA256 dari <strong className="text-slate-800">raw request body</strong> dikalikan dengan 
                                <strong className="text-slate-800"> Webhook Secret</strong> yang Anda simpan di halaman Credentials.
                            </p>
                            
                            <CodeBlock language="php">{`<?php
// Contoh Verifikasi Webhook di PHP

$rawBody = file_get_contents('php://input');
$secret = getenv('WEBHOOK_SECRET'); // Ambil dari file .env sistem Anda

// 1. Hitung Signature
$expectedSignature = hash_hmac('sha256', $rawBody, $secret);

// 2. Ambil Signature dari Header
$receivedSignature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';

// 3. Validasi
if (!hash_equals($expectedSignature, $receivedSignature)) {
    http_response_code(401);
    exit(json_encode(['error' => 'Invalid signature']));
}

// 4. Proses Payload secara aman
$payload = json_decode($rawBody, true);
// ...update status order di database Anda...

// 5. Kembalikan 200 OK agar server kami berhenti meretry
http_response_code(200);
echo json_encode(['status' => 'success']);`}</CodeBlock>
                        </Section>

                        {/* ── Section 5: Error Codes ── */}
                        <Section id="error-codes" title="Error Codes Reference">
                            <p className="text-slate-600 mb-6">
                                Kami merespons dengan HTTP Status Code yang tepat. Selalu periksa nilai <code className="bg-slate-100 text-purple-700 px-1.5 py-0.5 rounded font-mono">error_code</code> untuk menangani exception secara programmatic.
                            </p>
                            
                            <div className="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
                                <table className="w-full text-sm text-left">
                                    <thead className="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                                        <tr>
                                            <th className="px-5 py-4 w-1/4">Error Code</th>
                                            <th className="px-5 py-4 w-1/6">HTTP Status</th>
                                            <th className="px-5 py-4">Resolusi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 bg-white">
                                        {[
                                            ['INVALID_TOKEN', '403 Forbidden', 'API Key salah, dihapus, atau tidak aktif. Periksa kembali header Authorization: Bearer Anda.'],
                                            ['IP_NOT_WHITELISTED', '403 Forbidden', 'Alamat IP server Anda diblokir karena tidak terdaftar. Tambahkan IP tersebut di menu Credentials panel kami.'],
                                            ['CODE_NOT_FOUND', '404 Not Found', 'Produk tidak dikenali. Selalu ambil list product_code terbaru dari endpoint /variant kami.'],
                                            ['INSUFFICIENT_BALANCE', '400 Bad Request', 'Saldo deposit Anda tidak mencukupi untuk melakukan transaksi ini. Segera Top Up.'],
                                            ['DUPLICATE_REFERENCE', '200 OK', 'referenceNumber ini sudah Anda kirim sebelumnya. Server mengembalikan order yang sama (idempotent).'],
                                            ['ORDER_FAILED', '200 OK', 'Transaksi diterima namun provider kami menggagalkannya (contoh: zone id salah). Saldo Anda dikembalikan otomatis.'],
                                            ['VALIDATION_ERROR', '422 Unprocessable', 'Request body Anda tidak sesuai skema (misalnya kurang field user_id atau referenceNumber).'],
                                        ].map(([code, status, desc], i) => (
                                            <tr key={i} className="hover:bg-slate-50 transition-colors">
                                                <td className="px-5 py-4 font-mono text-xs font-bold text-rose-600">{code}</td>
                                                <td className="px-5 py-4">
                                                    <Badge variant={status.includes('200') ? 'green' : status.includes('403') || status.includes('404') ? 'red' : 'amber'}>
                                                        {status}
                                                    </Badge>
                                                </td>
                                                <td className="px-5 py-4 text-slate-600 leading-relaxed">{desc}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </Section>

                    </div>
                </div>
            </div>
        </ResellerLayout>
    );
}

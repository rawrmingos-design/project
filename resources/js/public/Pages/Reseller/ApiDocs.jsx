import React, { useState, useEffect, useRef } from 'react';
import { Head } from '@inertiajs/react';
import ResellerLayout from '@/public/Layouts/ResellerLayout.jsx';

const SECTIONS = [
    { id: 'authentication', label: 'Authentication' },
    { id: 'endpoints', label: 'Endpoints' },
    { id: 'data-fields', label: 'user_id & zone_id' },
    { id: 'webhook', label: 'Webhook / Callback' },
    { id: 'error-codes', label: 'Error Codes' },
];

function CodeBlock({ children, language = 'bash' }) {
    const [copied, setCopied] = useState(false);
    const handleCopy = () => {
        navigator.clipboard.writeText(children.trim());
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };
    return (
        <div className="relative group">
            <pre className="bg-slate-900 text-slate-100 rounded-lg p-4 overflow-x-auto text-sm font-mono leading-relaxed mt-2 mb-4">
                <code>{children.trim()}</code>
            </pre>
            <button
                onClick={handleCopy}
                className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs px-2 py-1 rounded"
            >
                {copied ? '✓ Copied' : 'Copy'}
            </button>
        </div>
    );
}

function Badge({ children, variant = 'blue' }) {
    const colors = {
        blue: 'bg-blue-100 text-blue-800',
        green: 'bg-green-100 text-green-800',
        yellow: 'bg-yellow-100 text-yellow-800',
        red: 'bg-red-100 text-red-800',
        gray: 'bg-gray-100 text-gray-700',
    };
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold ${colors[variant]}`}>
            {children}
        </span>
    );
}

function Section({ id, title, children }) {
    return (
        <section id={id} className="scroll-mt-24 mb-12">
            <h2 className="text-xl font-bold text-gray-900 border-b border-gray-200 pb-3 mb-6">
                {title}
            </h2>
            {children}
        </section>
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
            { rootMargin: '-80px 0px -60% 0px', threshold: 0 }
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

            {/* Page Header */}
            <div className="mb-8">
                <div className="flex items-center gap-3 mb-2">
                    <h1 className="text-2xl font-bold text-gray-900">API Documentation</h1>
                    <Badge variant="blue">v1</Badge>
                </div>
                <p className="text-gray-500 text-sm">
                    Technical reference untuk integrasi H2H API.
                    Credentials Anda sudah diisi otomatis di bawah.
                </p>
            </div>

            <div className="flex gap-8">
                {/* Sidebar */}
                <nav className="hidden lg:block w-48 shrink-0 sticky top-24 self-start">
                    <p className="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Contents</p>
                    <ul className="space-y-1">
                        {SECTIONS.map(s => (
                            <li key={s.id}>
                                <button
                                    onClick={() => scrollTo(s.id)}
                                    className={`w-full text-left text-sm px-3 py-1.5 rounded-md transition-colors ${
                                        activeSection === s.id
                                            ? 'bg-purple-50 text-purple-700 font-medium'
                                            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                                    }`}
                                >
                                    {s.label}
                                </button>
                            </li>
                        ))}
                    </ul>
                </nav>

                {/* Content */}
                <div className="flex-1 min-w-0">

                    {/* ── Section 1: Authentication ── */}
                    <Section id="authentication" title="Authentication">
                        <p className="text-gray-700 text-sm mb-4">
                            Semua request ke API harus menyertakan header <code className="bg-gray-100 px-1 py-0.5 rounded text-xs">Authorization</code> dengan format Bearer token.
                        </p>

                        <h3 className="font-semibold text-gray-800 mb-2 mt-4">Live API</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                            <div className="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <span className="text-xs font-bold text-gray-500 uppercase tracking-wide">Base URL</span>
                                <code className="block text-sm text-purple-700 mt-1 break-all">{live_base_url}</code>
                            </div>
                            <div className="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <span className="text-xs font-bold text-gray-500 uppercase tracking-wide">Integration Code Header</span>
                                <code className="block text-sm mt-1 break-all">{live?.integration_code ?? '—'}</code>
                            </div>
                            <div className="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <span className="text-xs font-bold text-gray-500 uppercase tracking-wide">API Key Hint</span>
                                <code className="block text-sm mt-1">{live?.api_key_hint ?? '—'}</code>
                                <p className="text-xs text-gray-400 mt-1">Rotate via Credentials page. Key hanya tampil sekali saat rotasi.</p>
                            </div>
                        </div>

                        <h3 className="font-semibold text-gray-800 mb-2 mt-6">Sandbox API</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                            <div className="bg-amber-50 rounded-lg p-4 border border-amber-200">
                                <span className="text-xs font-bold text-amber-600 uppercase tracking-wide">Base URL</span>
                                <code className="block text-sm text-amber-700 mt-1 break-all">{sandbox_base_url}</code>
                            </div>
                            <div className="bg-amber-50 rounded-lg p-4 border border-amber-200">
                                <span className="text-xs font-bold text-amber-600 uppercase tracking-wide">Integration Code Header</span>
                                <code className="block text-sm mt-1 break-all">{sandbox?.integration_code ?? '—'}</code>
                            </div>
                            <div className="bg-amber-50 rounded-lg p-4 border border-amber-200">
                                <span className="text-xs font-bold text-amber-600 uppercase tracking-wide">Sandbox API Key Hint</span>
                                <code className="block text-sm mt-1">{sandbox?.api_key_hint ?? '—'}</code>
                            </div>
                        </div>

                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                            <strong>Catatan:</strong> Header <code className="bg-blue-100 px-1 rounded text-xs">X-Reseller-Integration-Code</code> wajib disertakan pada endpoint <code className="bg-blue-100 px-1 rounded text-xs">/order</code>. Endpoint lain (<code className="bg-blue-100 px-1 rounded text-xs">balance</code>, <code className="bg-blue-100 px-1 rounded text-xs">product</code>, <code className="bg-blue-100 px-1 rounded text-xs">variant</code>) hanya butuh Bearer token.
                        </div>
                    </Section>

                    {/* ── Section 2: Endpoints ── */}
                    <Section id="endpoints" title="Endpoints">
                        <div className="space-y-4">

                            {/* Balance */}
                            <details className="bg-white border border-gray-200 rounded-lg group">
                                <summary className="flex items-center gap-3 px-4 py-3 cursor-pointer select-none">
                                    <Badge variant="blue">POST</Badge>
                                    <code className="text-sm font-mono">/api/v1/balance</code>
                                    <span className="text-gray-500 text-sm ml-auto">Cek saldo reseller</span>
                                </summary>
                                <div className="px-4 pb-4 border-t border-gray-100 mt-1">
                                    <p className="text-sm text-gray-600 mt-3 mb-2">Tidak membutuhkan request body.</p>
                                    <CodeBlock>{`curl -X POST "${live_base_url}/balance" \\
  -H "Authorization: Bearer {API_KEY}" \\
  -H "Content-Type: application/json"`}</CodeBlock>
                                    <p className="text-xs font-semibold text-gray-500 uppercase mb-1">Response</p>
                                    <CodeBlock>{`{
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
                            </details>

                            {/* Order */}
                            <details className="bg-white border border-gray-200 rounded-lg">
                                <summary className="flex items-center gap-3 px-4 py-3 cursor-pointer select-none">
                                    <Badge variant="blue">POST</Badge>
                                    <code className="text-sm font-mono">/api/v1/order</code>
                                    <span className="text-gray-500 text-sm ml-auto">Buat order baru</span>
                                </summary>
                                <div className="px-4 pb-4 border-t border-gray-100 mt-1">
                                    <p className="text-sm text-gray-600 mt-3 mb-2">Wajib menyertakan header <code className="bg-gray-100 px-1 rounded text-xs">X-Reseller-Integration-Code</code>.</p>
                                    <div className="mb-3">
                                        <p className="text-xs font-semibold text-gray-500 uppercase mb-2">Request Body Fields</p>
                                        <table className="w-full text-sm border-collapse">
                                            <thead>
                                                <tr className="bg-gray-50">
                                                    <th className="text-left p-2 font-semibold text-gray-600 border border-gray-200">Field</th>
                                                    <th className="text-left p-2 font-semibold text-gray-600 border border-gray-200">Type</th>
                                                    <th className="text-left p-2 font-semibold text-gray-600 border border-gray-200">Wajib</th>
                                                    <th className="text-left p-2 font-semibold text-gray-600 border border-gray-200">Keterangan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td className="p-2 border border-gray-200"><code className="text-xs">code</code></td>
                                                    <td className="p-2 border border-gray-200 text-gray-500">string</td>
                                                    <td className="p-2 border border-gray-200"><Badge variant="red">Ya</Badge></td>
                                                    <td className="p-2 border border-gray-200 text-gray-600">Kode produk dari /variant</td>
                                                </tr>
                                                <tr className="bg-gray-50">
                                                    <td className="p-2 border border-gray-200"><code className="text-xs">referenceNumber</code></td>
                                                    <td className="p-2 border border-gray-200 text-gray-500">string</td>
                                                    <td className="p-2 border border-gray-200"><Badge variant="red">Ya</Badge></td>
                                                    <td className="p-2 border border-gray-200 text-gray-600">ID unik dari sistem Anda (idempotency key)</td>
                                                </tr>
                                                <tr>
                                                    <td className="p-2 border border-gray-200"><code className="text-xs">user_id</code></td>
                                                    <td className="p-2 border border-gray-200 text-gray-500">string</td>
                                                    <td className="p-2 border border-gray-200"><Badge variant="red">Ya</Badge></td>
                                                    <td className="p-2 border border-gray-200 text-gray-600">ID akun target (User ID game, nomor HP, dll)</td>
                                                </tr>
                                                <tr className="bg-gray-50">
                                                    <td className="p-2 border border-gray-200"><code className="text-xs">zone_id</code></td>
                                                    <td className="p-2 border border-gray-200 text-gray-500">string | null</td>
                                                    <td className="p-2 border border-gray-200"><Badge variant="gray">Opsional</Badge></td>
                                                    <td className="p-2 border border-gray-200 text-gray-600">Zone/Server ID (hanya untuk produk yang butuh, misal Mobile Legends)</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <CodeBlock>{`curl -X POST "${live_base_url}/order" \\
  -H "Authorization: Bearer {API_KEY}" \\
  -H "X-Reseller-Integration-Code: ${live?.integration_code ?? '{INTEGRATION_CODE}'}" \\
  -H "Content-Type: application/json" \\
  -d '{
    "code": "ML-DIAMOND-100",
    "referenceNumber": "INV-2026060301",
    "user_id": "12345678",
    "zone_id": "9001"
  }'`}</CodeBlock>
                                </div>
                            </details>

                            {/* Status Order */}
                            <details className="bg-white border border-gray-200 rounded-lg">
                                <summary className="flex items-center gap-3 px-4 py-3 cursor-pointer select-none">
                                    <Badge variant="blue">POST</Badge>
                                    <code className="text-sm font-mono">/api/v1/status-order/{'{invoice}'}</code>
                                    <span className="text-gray-500 text-sm ml-auto">Cek status order</span>
                                </summary>
                                <div className="px-4 pb-4 border-t border-gray-100 mt-1">
                                    <p className="text-sm text-gray-600 mt-3 mb-2">Invoice adalah <code className="bg-gray-100 px-1 rounded text-xs">invoiceNumber</code> yang dikembalikan dari endpoint <code className="bg-gray-100 px-1 rounded text-xs">/order</code>.</p>
                                    <CodeBlock>{`curl -X POST "${live_base_url}/status-order/WEJIZY-RAPI123456" \\
  -H "Authorization: Bearer {API_KEY}"`}</CodeBlock>
                                </div>
                            </details>

                            {/* Sandbox note */}
                            <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                                <strong>Sandbox:</strong> Semua endpoint di atas tersedia dengan prefix <code className="bg-amber-100 px-1 rounded text-xs">/api/v1/sandbox/</code> — tidak memotong saldo, tidak memanggil provider real. Tambahkan <code className="bg-amber-100 px-1 rounded text-xs">/simulate-status/{'{invoice}'}</code> untuk trigger perubahan status.
                            </div>
                        </div>
                    </Section>

                    {/* ── Section 3: user_id & zone_id ── */}
                    <Section id="data-fields" title="Field user_id & zone_id">
                        <p className="text-sm text-gray-700 mb-4">
                            Field <code className="bg-gray-100 px-1 py-0.5 rounded text-xs font-mono">user_id</code> selalu wajib diisi.
                            Field <code className="bg-gray-100 px-1 py-0.5 rounded text-xs font-mono">zone_id</code> hanya diperlukan untuk produk yang membutuhkan dua identifier (contoh: Mobile Legends). Jika tidak diperlukan, kirimkan <code className="bg-gray-100 px-1 py-0.5 rounded text-xs font-mono">null</code> atau hilangkan field dari request.
                        </p>

                        <div className="overflow-x-auto rounded-lg border border-gray-200">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-gray-50 border-b border-gray-200">
                                        <th className="text-left p-3 font-semibold text-gray-600">Kategori Produk</th>
                                        <th className="text-left p-3 font-semibold text-gray-600">user_id</th>
                                        <th className="text-left p-3 font-semibold text-gray-600">zone_id</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {[
                                        ['Mobile Legends', 'User ID akun ML (contoh: 12345678)', 'Zone ID (contoh: 9001) — WAJIB'],
                                        ['Free Fire', 'Player ID / UID FF', '— (kosongkan)'],
                                        ['PUBG Mobile', 'Player ID PUBG', '— (kosongkan)'],
                                        ['Genshin Impact', 'UID Genshin', '— (kosongkan)'],
                                        ['Pulsa / Data', 'Nomor HP (08xxx atau 62xxx)', '— (kosongkan)'],
                                        ['Steam Wallet', 'Steam ID / email Steam', '— (kosongkan)'],
                                        ['Voucher Game', 'ID / kode target', 'Tergantung produk — cek keterangan di /variant'],
                                    ].map(([category, uid, zid], i) => (
                                        <tr key={i} className={i % 2 === 0 ? '' : 'bg-gray-50'}>
                                            <td className="p-3 border-b border-gray-100 font-medium text-gray-800">{category}</td>
                                            <td className="p-3 border-b border-gray-100 text-gray-600 font-mono text-xs">{uid}</td>
                                            <td className={`p-3 border-b border-gray-100 font-mono text-xs ${zid.startsWith('—') ? 'text-gray-400' : 'text-red-600 font-medium'}`}>{zid}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                            <strong>Cara cek per produk:</strong> Panggil endpoint <code className="bg-blue-100 px-1 rounded text-xs">/api/v1/variant</code> dengan <code className="bg-blue-100 px-1 rounded text-xs">{"{ \"code\": \"ml\" }"}</code> — lihat nama produk untuk memahami apakah butuh zone ID.
                        </div>
                    </Section>

                    {/* ── Section 4: Webhook ── */}
                    <Section id="webhook" title="Webhook / Callback">
                        <p className="text-sm text-gray-700 mb-4">
                            Sistem mengirim HTTP POST ke URL webhook Anda saat status order berubah menjadi final (Sukses / Gagal). Anda harus setup webhook URL dan secret di halaman <strong>Credentials</strong>.
                        </p>

                        <h3 className="font-semibold text-gray-800 mt-4 mb-2">Event Types</h3>
                        <div className="overflow-x-auto rounded-lg border border-gray-200 mb-4">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-gray-50 border-b border-gray-200">
                                        <th className="text-left p-3 font-semibold text-gray-600">Event</th>
                                        <th className="text-left p-3 font-semibold text-gray-600">Deskripsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td className="p-3 border-b border-gray-100 font-mono text-xs">h2h.order.updated</td>
                                        <td className="p-3 border-b border-gray-100 text-gray-600">Live order — status berubah ke final</td>
                                    </tr>
                                    <tr className="bg-gray-50">
                                        <td className="p-3 border-b border-gray-100 font-mono text-xs">h2h.sandbox.order.updated</td>
                                        <td className="p-3 border-b border-gray-100 text-gray-600">Sandbox order — status berubah ke final</td>
                                    </tr>
                                    <tr>
                                        <td className="p-3 border-b border-gray-100 font-mono text-xs">h2h.webhook.test</td>
                                        <td className="p-3 border-b border-gray-100 text-gray-600">Synthetic test — dikirim dari tombol "Test Webhook" di Callback Logs page</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h3 className="font-semibold text-gray-800 mt-4 mb-2">Verifikasi Signature</h3>
                        <p className="text-sm text-gray-700 mb-2">Header <code className="bg-gray-100 px-1 rounded text-xs">X-Callback-Signature</code> berisi HMAC-SHA256 dari raw request body menggunakan Webhook Secret Anda.</p>
                        <CodeBlock>{`// PHP — verifikasi signature
$rawBody  = file_get_contents('php://input');
$secret   = getenv('WEBHOOK_SECRET');
$expected = hash_hmac('sha256', $rawBody, $secret);
$received = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';

if (!hash_equals($expected, $received)) {
    http_response_code(401);
    exit('Unauthorized');
}

$payload = json_decode($rawBody, true);
// Proses $payload['event'], $payload['invoiceNumber'],
// $payload['userId'], $payload['zoneId'], $payload['statusCode'], dst.`}</CodeBlock>
                    </Section>

                    {/* ── Section 5: Error Codes ── */}
                    <Section id="error-codes" title="Error Codes Reference">
                        <p className="text-sm text-gray-700 mb-4">
                            Semua error response menggunakan format: <code className="bg-gray-100 px-1 rounded text-xs">{'{ "error": true, "error_code": "CODE", "message": "..." }'}</code>
                        </p>
                        <div className="overflow-x-auto rounded-lg border border-gray-200">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-gray-50 border-b border-gray-200">
                                        <th className="text-left p-3 font-semibold text-gray-600">error_code</th>
                                        <th className="text-left p-3 font-semibold text-gray-600">HTTP</th>
                                        <th className="text-left p-3 font-semibold text-gray-600">Arti & Cara Resolve</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {[
                                        ['UNAUTHENTICATED', '401', 'API key tidak valid atau tidak ditemukan. Pastikan format "Bearer {key}" benar.'],
                                        ['INVALID_INTEGRATION_CODE', '403', 'X-Reseller-Integration-Code tidak valid, bukan milik Anda, atau integration tidak aktif.'],
                                        ['IP_NOT_WHITELISTED', '403', 'IP caller tidak ada di whitelist integration. Tambah IP di halaman Credentials.'],
                                        ['CODE_NOT_FOUND', '404', 'Kode produk tidak ditemukan di katalog. Gunakan /product dan /variant untuk cek kode yang valid.'],
                                        ['INVOICE_NOT_FOUND', '404', 'Invoice tidak ditemukan atau bukan milik Anda di environment ini.'],
                                        ['INSUFFICIENT_BALANCE', '400', 'Saldo tidak cukup. Top up saldo terlebih dahulu.'],
                                        ['DUPLICATE_REFERENCE', '200', 'referenceNumber sudah pernah dipakai. Response berisi order yang sudah ada (idempotent).'],
                                        ['ORDER_FAILED', '200', 'Order diterima tapi provider gagal. Saldo tidak terpotong. Bisa retry dengan referenceNumber yang sama.'],
                                        ['VALIDATION_ERROR', '422', 'Request body tidak valid. Cek field yang wajib (code, referenceNumber, user_id).'],
                                    ].map(([code, status, desc], i) => (
                                        <tr key={i} className={i % 2 === 0 ? '' : 'bg-gray-50'}>
                                            <td className="p-3 border-b border-gray-100 font-mono text-xs text-red-600">{code}</td>
                                            <td className="p-3 border-b border-gray-100 text-center"><Badge variant={status === '200' ? 'green' : status.startsWith('4') ? 'red' : 'gray'}>{status}</Badge></td>
                                            <td className="p-3 border-b border-gray-100 text-gray-600">{desc}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Section>

                </div>
            </div>
        </ResellerLayout>
    );
}

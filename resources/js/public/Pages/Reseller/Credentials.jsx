import React, { useState } from 'react';
import axios from 'axios';
import { Head, usePage, router } from '@inertiajs/react';
import ResellerLayout from '../../Layouts/ResellerLayout';

export default function Credentials() {
    const { live, sandbox, auth } = usePage().props;
    
    // Rotate Key State
    const [rotateModalOpen, setRotateModalOpen] = useState(false);
    const [rotateType, setRotateType] = useState(null); // 'live' or 'sandbox'
    const [rotateStep, setRotateStep] = useState(1); // 1: confirm, 2: 2fa, 3: result
    const [totpCode, setTotpCode] = useState('');
    const [rotateError, setRotateError] = useState('');
    const [newRawKey, setNewRawKey] = useState('');
    const [isRotating, setIsRotating] = useState(false);

    // IP Whitelist State
    const [ipInput, setIpInput] = useState('');
    const [ipError, setIpError] = useState('');
    const [isAddingIp, setIsAddingIp] = useState(false);

    // authUser is injected globally by HandleInertiaRequests with twoFactorEnabled
    const { authUser } = usePage().props;
    const is2faEnabled = authUser?.twoFactorEnabled ?? false;

    // Handlers
    const openRotateModal = (type) => {
        if (!is2faEnabled) {
            alert('Silakan aktifkan 2FA di Pengaturan terlebih dahulu.');
            return;
        }
        setRotateType(type);
        setRotateStep(1);
        setRotateModalOpen(true);
        setTotpCode('');
        setRotateError('');
    };

    const handleRotateConfirm = () => {
        setRotateStep(2);
    };

    const handleRotateSubmit = async (e) => {
        e.preventDefault();
        setIsRotating(true);
        setRotateError('');

        try {
            const endpoint = rotateType === 'live' 
                ? '/id/reseller/credentials/rotate-live' 
                : '/id/reseller/credentials/rotate-sandbox';
            
            const response = await axios.post(endpoint, { totp_code: totpCode });
            
            setNewRawKey(response.data.raw_key);
            setRotateStep(3);
        } catch (error) {
            setRotateError(error.response?.data?.message || 'Terjadi kesalahan saat memverifikasi 2FA.');
        } finally {
            setIsRotating(false);
        }
    };

    // Modal for New Webhook Secret
    const [webhookSecretModalOpen, setWebhookSecretModalOpen] = useState(false);
    const flash = usePage().props.flash;

    React.useEffect(() => {
        if (flash?.new_webhook_secret) {
            setWebhookSecretModalOpen(true);
        }
    }, [flash?.new_webhook_secret]);

    const handleCloseModal = () => {
        setRotateModalOpen(false);
        if (rotateStep === 3) {
            router.reload();
        }
    };

    const handleAddIp = async (e) => {
        e.preventDefault();
        if (!ipInput.trim()) return;

        setIsAddingIp(true);
        setIpError('');

        try {
            await axios.post('/id/reseller/ip-whitelist', { ip: ipInput.trim() });
            setIpInput('');
            router.reload({ only: ['live'] });
        } catch (error) {
            setIpError(error.response?.data?.message || error.response?.data?.errors?.ip?.[0] || 'Gagal menambahkan IP.');
        } finally {
            setIsAddingIp(false);
        }
    };

    const handleRemoveIp = async (ip) => {
        if (!confirm(`Hapus IP ${ip} dari whitelist?`)) return;

        try {
            await axios.delete(`/id/reseller/ip-whitelist/${encodeURIComponent(ip)}`);
            router.reload({ only: ['live'] });
        } catch (error) {
            alert(error.response?.data?.message || 'Gagal menghapus IP.');
        }
    };

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text);
        alert('Disalin ke clipboard!');
    };

    return (
        <ResellerLayout headerTitle="API Credentials">
            <Head title="API Credentials" />

            <section style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '24px', marginBottom: '32px' }}>
                {/* Live Credentials */}
                <article className="rh-card">
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '20px', height: '100%' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <h3 style={{ margin: 0, fontSize: '1.2rem', color: '#fff' }}>Live API Key</h3>
                            <span className={`rh-badge rh-badge--${live?.is_active ? 'success' : 'danger'}`}>
                                {live?.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>

                        <div style={{ flex: 1 }}>
                            <label style={{ fontSize: '0.85rem', color: 'var(--on-surface-variant)', display: 'block', marginBottom: '8px' }}>Secret Key</label>
                            
                            {live?.is_new_key ? (
                                // NEW KEY - Show full key with copy button
                                <>
                                    <div style={{ background: 'rgba(251, 191, 36, 0.1)', border: '1px solid rgba(251, 191, 36, 0.3)', borderRadius: 'var(--radius-md)', padding: '12px', marginBottom: '12px' }}>
                                        <div style={{ display: 'flex', gap: '8px', alignItems: 'flex-start' }}>
                                            <span style={{ fontSize: '1.2rem' }}>⚠️</span>
                                            <div style={{ flex: 1, fontSize: '0.85rem', color: '#fbbf24' }}>
                                                <strong>PENTING:</strong> Salin key sekarang! Key hanya ditampilkan sekali. Full key juga sudah dikirim ke email/WhatsApp Anda.
                                            </div>
                                        </div>
                                    </div>
                                    <div style={{ display: 'flex', gap: '8px' }}>
                                        <div style={{ flex: 1, fontFamily: 'var(--font-mono, monospace)', letterSpacing: '1px', background: 'rgba(0,0,0,0.3)', padding: '10px 12px', borderRadius: 'var(--radius-md)', border: '1px solid rgba(16, 185, 129, 0.3)', color: '#10b981', wordBreak: 'break-all' }}>
                                            {live?.api_key_full}
                                        </div>
                                        <button className="rh-button rh-button--primary" onClick={() => copyToClipboard(live?.api_key_full)}>
                                            Copy
                                        </button>
                                    </div>
                                </>
                            ) : (
                                // EXISTING KEY - Show hint only, no copy button
                                <>
                                    <div style={{ fontFamily: 'var(--font-mono, monospace)', letterSpacing: '1px', background: 'rgba(0,0,0,0.3)', padding: '10px 12px', borderRadius: 'var(--radius-md)', border: '1px solid rgba(255,255,255,0.05)', color: '#fff', marginBottom: '8px' }}>
                                        {live?.api_key_hint || 'Belum diatur'}
                                    </div>
                                    <div style={{ fontSize: '0.8rem', color: 'rgba(255,255,255,0.4)' }}>
                                        Full key dikirim ke email/WhatsApp saat approval. Gunakan "Rotate Key" untuk generate key baru jika hilang.
                                    </div>
                                </>
                            )}
                        </div>

                        <button 
                            className="rh-button rh-button--danger" 
                            style={{ width: '100%' }}
                            onClick={() => openRotateModal('live')}
                            disabled={!is2faEnabled}
                        >
                            Rotate Live Key
                        </button>
                    </div>
                </article>

                {/* Sandbox Credentials */}
                <article className="rh-card">
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '20px', height: '100%' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <h3 style={{ margin: 0, fontSize: '1.2rem', color: '#fff' }}>Sandbox API Key</h3>
                            <span className={`rh-badge rh-badge--${sandbox?.is_active ? 'success' : 'warning'}`}>
                                {sandbox?.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>

                        <div style={{ flex: 1 }}>
                            <label style={{ fontSize: '0.85rem', color: 'var(--on-surface-variant)', display: 'block', marginBottom: '8px' }}>Sandbox Key</label>
                            
                            {sandbox?.is_new_key ? (
                                // NEW KEY - Show full key with copy button
                                <>
                                    <div style={{ background: 'rgba(251, 191, 36, 0.1)', border: '1px solid rgba(251, 191, 36, 0.3)', borderRadius: 'var(--radius-md)', padding: '12px', marginBottom: '12px' }}>
                                        <div style={{ display: 'flex', gap: '8px', alignItems: 'flex-start' }}>
                                            <span style={{ fontSize: '1.2rem' }}>⚠️</span>
                                            <div style={{ flex: 1, fontSize: '0.85rem', color: '#fbbf24' }}>
                                                <strong>PENTING:</strong> Salin key sekarang! Key hanya ditampilkan sekali. Full key juga sudah dikirim ke email/WhatsApp Anda.
                                            </div>
                                        </div>
                                    </div>
                                    <div style={{ display: 'flex', gap: '8px' }}>
                                        <div style={{ flex: 1, fontFamily: 'var(--font-mono, monospace)', letterSpacing: '1px', background: 'rgba(0,0,0,0.3)', padding: '10px 12px', borderRadius: 'var(--radius-md)', border: '1px solid rgba(16, 185, 129, 0.3)', color: '#10b981', wordBreak: 'break-all' }}>
                                            {sandbox?.api_key_full}
                                        </div>
                                        <button className="rh-button rh-button--primary" onClick={() => copyToClipboard(sandbox?.api_key_full)}>
                                            Copy
                                        </button>
                                    </div>
                                </>
                            ) : (
                                // EXISTING KEY - Show hint only, no copy button
                                <>
                                    <div style={{ fontFamily: 'var(--font-mono, monospace)', letterSpacing: '1px', background: 'rgba(0,0,0,0.3)', padding: '10px 12px', borderRadius: 'var(--radius-md)', border: '1px solid rgba(255,255,255,0.05)', color: '#fff', marginBottom: '8px' }}>
                                        {sandbox?.api_key_hint || 'Belum diatur'}
                                    </div>
                                    <div style={{ fontSize: '0.8rem', color: 'rgba(255,255,255,0.4)' }}>
                                        Full key dikirim ke email/WhatsApp saat approval. Digunakan khusus untuk testing.
                                    </div>
                                </>
                            )}
                        </div>

                        <button 
                            className="rh-button rh-button--secondary" 
                            style={{ width: '100%' }}
                            onClick={() => openRotateModal('sandbox')}
                            disabled={!is2faEnabled}
                        >
                            Rotate Sandbox Key
                        </button>
                    </div>
                </article>
            </section>

            {/* Webhook Settings Section */}
            <section style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '24px', marginBottom: '32px' }}>
                {/* Live Webhook */}
                <article className="rh-card">
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '20px', height: '100%' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <h3 style={{ margin: 0, fontSize: '1.2rem', color: '#fff' }}>Live Webhook</h3>
                            <span className={`rh-badge rh-badge--${live?.webhook?.is_enabled ? 'success' : 'warning'}`}>
                                {live?.webhook?.is_enabled ? 'Active' : 'Not Configured'}
                            </span>
                        </div>

                        <form style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: '16px' }} onSubmit={(e) => { e.preventDefault(); router.post('/id/reseller/credentials/webhook', { mode: 'live', url: e.target.elements.url.value, generate_secret: false }, { preserveScroll: true }); }}>
                            <div>
                                <label style={{ fontSize: '0.85rem', color: 'var(--on-surface-variant)', display: 'block', marginBottom: '8px' }}>Webhook URL</label>
                                <input 
                                    type="url" 
                                    name="url"
                                    id="live-url"
                                    className="rh-input" 
                                    placeholder="https://your-server.com/api/callback" 
                                    defaultValue={live?.webhook?.url || ''}
                                    required
                                />
                            </div>
                            
                            <div>
                                <label style={{ fontSize: '0.85rem', color: 'var(--on-surface-variant)', display: 'block', marginBottom: '8px' }}>Webhook Secret</label>
                                <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                                    <div style={{ flex: 1, fontFamily: 'var(--font-mono, monospace)', letterSpacing: '1px', background: 'rgba(0,0,0,0.3)', padding: '10px 12px', borderRadius: 'var(--radius-md)', border: '1px solid rgba(255,255,255,0.05)', color: live?.webhook?.has_secret ? '#fff' : 'rgba(255,255,255,0.3)' }}>
                                        {live?.webhook?.has_secret ? '••••••••••••••••••••••••••••' : 'Belum digenerate'}
                                    </div>
                                    <button type="button" className="rh-button rh-button--secondary" onClick={() => { const currentUrl = document.getElementById('live-url').value; if(!currentUrl) return alert('Silakan isi Webhook URL terlebih dahulu!'); if(confirm('Generate secret baru? Secret lama akan langsung tidak berlaku!')) { router.post('/id/reseller/credentials/webhook', { mode: 'live', url: currentUrl, generate_secret: true }, { preserveScroll: true }); } }}>
                                        Generate
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" className="rh-button rh-button--primary" style={{ alignSelf: 'flex-start' }}>Simpan URL</button>
                        </form>
                    </div>
                </article>

                {/* Sandbox Webhook */}
                <article className="rh-card">
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '20px', height: '100%' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <h3 style={{ margin: 0, fontSize: '1.2rem', color: '#fff' }}>Sandbox Webhook</h3>
                            <span className={`rh-badge rh-badge--${sandbox?.webhook?.is_enabled ? 'success' : 'warning'}`}>
                                {sandbox?.webhook?.is_enabled ? 'Active' : 'Not Configured'}
                            </span>
                        </div>

                        <form style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: '16px' }} onSubmit={(e) => { e.preventDefault(); router.post('/id/reseller/credentials/webhook', { mode: 'sandbox', url: e.target.elements.url.value, generate_secret: false }, { preserveScroll: true }); }}>
                            <div>
                                <label style={{ fontSize: '0.85rem', color: 'var(--on-surface-variant)', display: 'block', marginBottom: '8px' }}>Sandbox Webhook URL</label>
                                <input 
                                    type="url" 
                                    name="url"
                                    id="sandbox-url"
                                    className="rh-input" 
                                    placeholder="https://your-server.com/api/callback/sandbox" 
                                    defaultValue={sandbox?.webhook?.url || ''}
                                    required
                                />
                            </div>
                            
                            <div>
                                <label style={{ fontSize: '0.85rem', color: 'var(--on-surface-variant)', display: 'block', marginBottom: '8px' }}>Sandbox Webhook Secret</label>
                                <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                                    <div style={{ flex: 1, fontFamily: 'var(--font-mono, monospace)', letterSpacing: '1px', background: 'rgba(0,0,0,0.3)', padding: '10px 12px', borderRadius: 'var(--radius-md)', border: '1px solid rgba(255,255,255,0.05)', color: sandbox?.webhook?.has_secret ? '#fff' : 'rgba(255,255,255,0.3)' }}>
                                        {sandbox?.webhook?.has_secret ? '••••••••••••••••••••••••••••' : 'Belum digenerate'}
                                    </div>
                                    <button type="button" className="rh-button rh-button--secondary" onClick={() => { const currentUrl = document.getElementById('sandbox-url').value; if(!currentUrl) return alert('Silakan isi Sandbox Webhook URL terlebih dahulu!'); if(confirm('Generate secret sandbox baru?')) { router.post('/id/reseller/credentials/webhook', { mode: 'sandbox', url: currentUrl, generate_secret: true }, { preserveScroll: true }); } }}>
                                        Generate
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" className="rh-button rh-button--primary" style={{ alignSelf: 'flex-start' }}>Simpan URL</button>
                        </form>
                    </div>
                </article>
            </section>

            {/* IP Whitelist Section */}
            <section className="rh-card" style={{ marginTop: '32px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
                    <div>
                        <h2 style={{ fontSize: '1.2rem', color: '#fff', marginBottom: '4px' }}>IP Whitelist</h2>
                        <p style={{ color: 'var(--on-surface-variant)', fontSize: '0.9rem', margin: 0 }}>Batasi akses API hanya dari alamat IP server Anda.</p>
                    </div>
                    <span className="rh-badge" style={{ background: 'var(--primary-container)', color: '#fff' }}>
                        {(live?.allowed_ips?.length || 0)} / 20 IPs
                    </span>
                </div>

                <div>
                    <form onSubmit={handleAddIp} style={{ display: 'flex', gap: '12px', marginBottom: '24px' }}>
                        <div style={{ flex: 1 }}>
                            <input 
                                type="text" 
                                className="rh-input"
                                placeholder="Contoh: 192.168.1.1 atau 10.0.0.0/24" 
                                value={ipInput}
                                onChange={(e) => setIpInput(e.target.value)}
                                disabled={isAddingIp}
                            />
                            {ipError && <div style={{ color: 'var(--accent-danger)', fontSize: '12px', marginTop: '4px' }}>{ipError}</div>}
                        </div>
                        <button type="submit" className="rh-button rh-button--primary" disabled={isAddingIp || !ipInput.trim()}>
                            {isAddingIp ? 'Menambahkan...' : 'Tambah IP'}
                        </button>
                    </form>

                    <div className="rh-table-container">
                        <table className="rh-table">
                            <thead>
                                <tr>
                                    <th>IP Address / CIDR</th>
                                    <th style={{ textAlign: 'right' }}>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {(!live?.allowed_ips || live.allowed_ips.length === 0) ? (
                                    <tr>
                                        <td colSpan="2" style={{ textAlign: 'center', padding: '32px 0', color: 'rgba(255,255,255,0.3)' }}>
                                            Belum ada IP yang diizinkan. Semua IP akan ditolak saat fitur ini aktif di backend.
                                        </td>
                                    </tr>
                                ) : (
                                    live.allowed_ips.map((ip, index) => (
                                        <tr key={index}>
                                            <td style={{ fontFamily: 'var(--font-mono, monospace)', color: 'var(--primary)' }}>{ip}</td>
                                            <td style={{ textAlign: 'right' }}>
                                                <button 
                                                    onClick={() => handleRemoveIp(ip)}
                                                    style={{ background: 'none', border: 'none', color: 'var(--accent-danger)', cursor: 'pointer', fontSize: '14px', textDecoration: 'underline' }}
                                                >
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {/* Rotate Modal */}
            {rotateModalOpen && (
                <div className="rh-modal-overlay">
                    <div className="rh-modal">
                        {rotateStep === 1 && (
                            <>
                                <h2 style={{ margin: '0 0 16px', color: rotateType === 'live' ? 'var(--accent-danger)' : 'var(--accent-primary)', fontSize: '1.5rem' }}>
                                    Rotasi {rotateType === 'live' ? 'Live' : 'Sandbox'} Key
                                </h2>
                                <p style={{ color: 'var(--on-surface-variant)', marginBottom: '24px', lineHeight: 1.6 }}>
                                    Peringatan: Merotasi kunci akan <strong style={{ color: '#fff' }}>langsung membatalkan</strong> kunci yang lama. Semua integrasi yang menggunakan kunci lama akan gagal (401 Unauthorized) hingga diperbarui.
                                </p>
                                <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end' }}>
                                    <button className="rh-button rh-button--secondary" onClick={handleCloseModal}>Batal</button>
                                    <button 
                                        className={`rh-button ${rotateType === 'live' ? 'rh-button--danger' : 'rh-button--primary'}`}
                                        onClick={handleRotateConfirm}
                                    >
                                        Ya, Lanjutkan
                                    </button>
                                </div>
                            </>
                        )}

                        {rotateStep === 2 && (
                            <>
                                <h2 style={{ margin: '0 0 16px', fontSize: '1.5rem', color: '#fff' }}>Verifikasi Keamanan</h2>
                                <p style={{ color: 'var(--on-surface-variant)', marginBottom: '24px', lineHeight: 1.6 }}>
                                    Masukkan 6 digit kode dari aplikasi Authenticator Anda untuk mengonfirmasi tindakan ini.
                                </p>
                                <form onSubmit={handleRotateSubmit}>
                                    <input 
                                        type="text" 
                                        className="rh-input"
                                        style={{ fontSize: '24px', textAlign: 'center', letterSpacing: '8px', marginBottom: '8px', padding: '16px' }}
                                        placeholder="000000"
                                        maxLength="6"
                                        value={totpCode}
                                        onChange={(e) => setTotpCode(e.target.value.replace(/\D/g, ''))}
                                        autoFocus
                                    />
                                    {rotateError && <div style={{ color: 'var(--accent-danger)', fontSize: '14px', marginBottom: '16px', textAlign: 'center' }}>{rotateError}</div>}
                                    
                                    <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end', marginTop: '24px' }}>
                                        <button type="button" className="rh-button rh-button--secondary" onClick={handleCloseModal}>Batal</button>
                                        <button type="submit" className="rh-button rh-button--primary" disabled={isRotating || totpCode.length !== 6}>
                                            {isRotating ? 'Memverifikasi...' : 'Verifikasi & Generate'}
                                        </button>
                                    </div>
                                </form>
                            </>
                        )}

                        {rotateStep === 3 && (
                            <>
                                <div style={{ textAlign: 'center', marginBottom: '24px' }}>
                                    <div style={{ width: '64px', height: '64px', borderRadius: '50%', background: 'rgba(16, 185, 129, 0.1)', color: 'var(--accent-success)', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 16px' }}>
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    </div>
                                    <h2 style={{ margin: '0 0 8px', fontSize: '1.5rem', color: '#fff' }}>Key Berhasil Dirotasi!</h2>
                                    <p style={{ color: 'var(--on-surface-variant)', margin: 0 }}>
                                        Harap salin kunci ini sekarang. Kunci tidak akan ditampilkan lagi setelah jendela ini ditutup.
                                    </p>
                                </div>

                                <div style={{ background: 'rgba(0,0,0,0.3)', padding: '16px', borderRadius: 'var(--radius-md)', border: '1px solid var(--accent-primary)', marginBottom: '24px', position: 'relative' }}>
                                    <div style={{ fontFamily: 'var(--font-mono, monospace)', color: 'var(--primary)', wordBreak: 'break-all', paddingRight: '40px' }}>
                                        {newRawKey}
                                    </div>
                                    <button 
                                        onClick={() => copyToClipboard(newRawKey)}
                                        style={{ position: 'absolute', top: '50%', right: '16px', transform: 'translateY(-50%)', background: 'none', border: 'none', color: '#fff', cursor: 'pointer' }}
                                        title="Copy to clipboard"
                                    >
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                        </svg>
                                    </button>
                                </div>

                                <button className="rh-button rh-button--primary" style={{ width: '100%' }} onClick={handleCloseModal}>
                                    Saya sudah menyalinnya, Tutup.
                                </button>
                            </>
                        )}
                    </div>
                </div>
            )}

            {/* Webhook Secret Modal */}
            {webhookSecretModalOpen && (
                <div className="rh-modal-overlay">
                    <div className="rh-modal">
                        <div style={{ textAlign: 'center', marginBottom: '24px' }}>
                            <div style={{ width: '64px', height: '64px', borderRadius: '50%', background: 'rgba(16, 185, 129, 0.1)', color: 'var(--accent-success)', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 16px' }}>
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                            <h2 style={{ margin: '0 0 8px', fontSize: '1.5rem', color: '#fff' }}>Webhook Secret Berhasil Dibuat!</h2>
                            <p style={{ color: 'var(--on-surface-variant)', margin: 0 }}>
                                Harap salin secret ini sekarang. Secret tidak akan ditampilkan lagi setelah jendela ini ditutup untuk alasan keamanan.
                            </p>
                        </div>

                        <div style={{ background: 'rgba(0,0,0,0.3)', padding: '16px', borderRadius: 'var(--radius-md)', border: '1px solid var(--accent-primary)', marginBottom: '24px', position: 'relative' }}>
                            <div style={{ fontFamily: 'var(--font-mono, monospace)', color: 'var(--primary)', wordBreak: 'break-all', paddingRight: '40px' }}>
                                {flash?.new_webhook_secret}
                            </div>
                            <button 
                                onClick={() => copyToClipboard(flash?.new_webhook_secret)}
                                style={{ position: 'absolute', top: '50%', right: '16px', transform: 'translateY(-50%)', background: 'none', border: 'none', color: '#fff', cursor: 'pointer' }}
                                title="Copy to clipboard"
                            >
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                            </button>
                        </div>

                        <button className="rh-button rh-button--primary" style={{ width: '100%' }} onClick={() => setWebhookSecretModalOpen(false)}>
                            Saya sudah menyalinnya, Tutup.
                        </button>
                    </div>
                </div>
            )}
        </ResellerLayout>
    );
}

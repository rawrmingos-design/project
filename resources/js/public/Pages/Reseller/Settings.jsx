import React, { useMemo, useState } from 'react';
import ResellerLayout from '../../Layouts/ResellerLayout';
import { useForm, usePage } from '@inertiajs/react';
import axios from 'axios';

export default function Settings() {
    const { settingsPage, meta, csrf_token } = usePage().props;
    const { profile, twoFactor, push, flash } = settingsPage;

    const [pushSupported] = useState(() => Boolean(window.isSecureContext && 'serviceWorker' in navigator && 'PushManager' in window));
    const [pushStatus, setPushStatus] = useState(push.enabled ? 'Subscribed' : 'Not subscribed');
    const [pushError, setPushError] = useState(null);
    const [pushBusy, setPushBusy] = useState(false);
    const [pushTestBusy, setPushTestBusy] = useState(false);
    const [pushTestMessage, setPushTestMessage] = useState(null);

    const notificationPermission = useMemo(() => {
        if (typeof window === 'undefined' || !('Notification' in window)) {
            return 'unsupported';
        }

        return Notification.permission;
    }, []);

    const toUint8Array = (base64String) => {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }

        return outputArray;
    };

    const getExistingSubscription = async () => {
        const registration = await navigator.serviceWorker.ready;
        return registration.pushManager.getSubscription();
    };

    const syncPushStatus = async () => {
        if (!pushSupported) {
            setPushStatus('Push not supported on this browser/device');
            return null;
        }

        const existingSubscription = await getExistingSubscription();
        setPushStatus(existingSubscription ? 'Subscribed' : 'Not subscribed');

        return existingSubscription;
    };

    const subscribePush = async () => {
        if (!push.configured) {
            setPushError('Konfigurasi VAPID web push belum lengkap di server.');
            return;
        }

        if (!push.vapidPublicKey) {
            setPushError('VAPID public key tidak tersedia.');
            return;
        }

        setPushBusy(true);
        setPushError(null);
        setPushTestMessage(null);

        try {
            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                setPushStatus(permission === 'denied' ? 'Permission denied' : 'Permission not granted');
                setPushError('Izin notifikasi belum diberikan di browser ini.');
                return;
            }

            const registration = await navigator.serviceWorker.ready;
            let subscription = await registration.pushManager.getSubscription();

            if (!subscription) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: toUint8Array(push.vapidPublicKey),
                });
            }

            await axios.post('/id/reseller/push-subscriptions', {
                subscription: subscription.toJSON(),
            }, {
                headers: {
                    'X-CSRF-TOKEN': csrf_token,
                },
            });

            setPushStatus('Subscribed');
        } catch (error) {
            setPushError(error.response?.data?.message || 'Gagal mengaktifkan push notification.');
        } finally {
            setPushBusy(false);
        }
    };

    const unsubscribePush = async () => {
        setPushBusy(true);
        setPushError(null);
        setPushTestMessage(null);

        try {
            const existingSubscription = await getExistingSubscription();

            if (!existingSubscription) {
                setPushStatus('Not subscribed');
                return;
            }

            await axios.delete('/id/reseller/push-subscriptions', {
                headers: {
                    'X-CSRF-TOKEN': csrf_token,
                },
                data: {
                    endpoint: existingSubscription.endpoint,
                },
            });

            await existingSubscription.unsubscribe();
            setPushStatus('Not subscribed');
        } catch (error) {
            setPushError(error.response?.data?.message || 'Gagal menonaktifkan push notification.');
        } finally {
            setPushBusy(false);
        }
    };

    const sendTestPush = async () => {
        setPushTestBusy(true);
        setPushError(null);
        setPushTestMessage(null);

        try {
            const response = await axios.post('/id/reseller/push-subscriptions/test', {}, {
                headers: {
                    'X-CSRF-TOKEN': csrf_token,
                },
            });

            setPushTestMessage(response.data?.message || 'Test push berhasil dikirim.');
        } catch (error) {
            setPushError(error.response?.data?.message || 'Gagal mengirim test push.');
        } finally {
            setPushTestBusy(false);
        }
    };

    // --- Profile Update Form ---
    const profileForm = useForm({
        name: profile.name,
        username: profile.username,
        no_wa: profile.phone,
    });

    const submitProfile = (e) => {
        e.preventDefault();
        profileForm.post('/id/settings');
    };

    // --- Change Password Form ---
    const passwordForm = useForm({
        current_password: '',
        new_password: '',
        new_password_confirmation: '',
    });

    const submitPassword = (e) => {
        e.preventDefault();
        passwordForm.post('/id/settings/change-password', {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    };

    // --- 2FA State ---
    const [qrData, setQrData] = useState(null);
    const [tfaCode, setTfaCode] = useState('');
    const [disablePassword, setDisablePassword] = useState('');
    const [tfaLoading, setTfaLoading] = useState(false);
    const [tfaError, setTfaError] = useState(null);

    const setup2FA = async () => {
        setTfaLoading(true);
        setTfaError(null);
        try {
            const res = await axios.post('/id/settings/2fa/setup');
            if (res.data.status === 'success') {
                setQrData(res.data.data);
            }
        } catch (error) {
            setTfaError('Failed to generate 2FA secret.');
        } finally {
            setTfaLoading(false);
        }
    };

    const enable2FA = async (e) => {
        e.preventDefault();
        setTfaLoading(true);
        setTfaError(null);
        try {
            const res = await axios.post('/id/settings/2fa/enable', { code: tfaCode });
            if (res.data.status === 'success') {
                // Reload page to reflect 2FA enabled state globally
                window.location.reload();
            }
        } catch (error) {
            setTfaError(error.response?.data?.message || 'Invalid authenticator code.');
        } finally {
            setTfaLoading(false);
        }
    };

    const disable2FA = async (e) => {
        e.preventDefault();
        setTfaLoading(true);
        setTfaError(null);
        try {
            const res = await axios.post('/id/settings/2fa/disable', { 
                current_password: disablePassword,
                code: tfaCode 
            });
            if (res.data.status === 'success') {
                setDisablePassword('');
                setTfaCode('');
                window.location.reload();
            }
        } catch (error) {
            setTfaError(error.response?.data?.message || 'Invalid password or code.');
        } finally {
            setTfaLoading(false);
        }
    };

    return (
        <ResellerLayout meta={meta} headerTitle="Settings">
            <div style={{ maxWidth: '900px', margin: '0 auto' }}>
                
                {flash?.success && (
                    <article className="rh-alert" style={{ background: 'rgba(16, 185, 129, 0.1)', borderColor: 'rgba(16, 185, 129, 0.2)' }}>
                        <span className="material-symbols-outlined" style={{ color: '#10b981' }}>check_circle</span>
                        <div>
                            <p style={{ margin: 0, color: '#10b981', fontSize: '14px', fontWeight: 600 }}>{flash.success}</p>
                        </div>
                    </article>
                )}

                <div className="rh-stat-grid" style={{ gap: '32px' }}>
                    
                    {/* Left Column: Profile & Security */}
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '32px' }}>
                        
                        {/* Profile Info */}
                        <div className="rh-card">
                            <h3 style={{ marginBottom: '24px', fontSize: '1.25rem' }}>Profile Information</h3>
                            <form onSubmit={submitProfile} style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '8px', fontSize: '14px', color: 'var(--on-surface-variant)' }}>Full Name</label>
                                    <input 
                                        type="text" 
                                        className="rh-input" 
                                        value={profileForm.data.name} 
                                        onChange={e => profileForm.setData('name', e.target.value)}
                                    />
                                    {profileForm.errors.name && <div style={{ color: 'var(--error)', fontSize: '12px', marginTop: '4px' }}>{profileForm.errors.name}</div>}
                                </div>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '8px', fontSize: '14px', color: 'var(--on-surface-variant)' }}>Username</label>
                                    <input 
                                        type="text" 
                                        className="rh-input" 
                                        value={profileForm.data.username} 
                                        onChange={e => profileForm.setData('username', e.target.value)}
                                    />
                                    {profileForm.errors.username && <div style={{ color: 'var(--error)', fontSize: '12px', marginTop: '4px' }}>{profileForm.errors.username}</div>}
                                </div>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '8px', fontSize: '14px', color: 'var(--on-surface-variant)' }}>WhatsApp Number</label>
                                    <input 
                                        type="text" 
                                        className="rh-input" 
                                        value={profileForm.data.no_wa} 
                                        onChange={e => profileForm.setData('no_wa', e.target.value)}
                                    />
                                    {profileForm.errors.no_wa && <div style={{ color: 'var(--error)', fontSize: '12px', marginTop: '4px' }}>{profileForm.errors.no_wa}</div>}
                                </div>
                                <button type="submit" disabled={profileForm.processing} className="rh-button rh-button--primary" style={{ marginTop: '8px' }}>
                                    {profileForm.processing ? 'Saving...' : 'Update Profile'}
                                </button>
                            </form>
                        </div>

                        {/* Change Password */}
                        <div className="rh-card">
                            <h3 style={{ marginBottom: '24px', fontSize: '1.25rem' }}>Change Password</h3>
                            <form onSubmit={submitPassword} style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '8px', fontSize: '14px', color: 'var(--on-surface-variant)' }}>Current Password</label>
                                    <input 
                                        type="password" 
                                        className="rh-input" 
                                        value={passwordForm.data.current_password} 
                                        onChange={e => passwordForm.setData('current_password', e.target.value)}
                                    />
                                    {passwordForm.errors.current_password && <div style={{ color: 'var(--error)', fontSize: '12px', marginTop: '4px' }}>{passwordForm.errors.current_password}</div>}
                                </div>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '8px', fontSize: '14px', color: 'var(--on-surface-variant)' }}>New Password</label>
                                    <input 
                                        type="password" 
                                        className="rh-input" 
                                        value={passwordForm.data.new_password} 
                                        onChange={e => passwordForm.setData('new_password', e.target.value)}
                                    />
                                    {passwordForm.errors.new_password && <div style={{ color: 'var(--error)', fontSize: '12px', marginTop: '4px' }}>{passwordForm.errors.new_password}</div>}
                                </div>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '8px', fontSize: '14px', color: 'var(--on-surface-variant)' }}>Confirm New Password</label>
                                    <input 
                                        type="password" 
                                        className="rh-input" 
                                        value={passwordForm.data.new_password_confirmation} 
                                        onChange={e => passwordForm.setData('new_password_confirmation', e.target.value)}
                                    />
                                </div>
                                <button type="submit" disabled={passwordForm.processing} className="rh-button rh-button--primary" style={{ marginTop: '8px' }}>
                                    {passwordForm.processing ? 'Updating...' : 'Change Password'}
                                </button>
                            </form>
                        </div>
                    </div>

                    {/* Right Column: 2FA */}
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '32px' }}>
                        <div className="rh-card">
                            <h3 style={{ marginBottom: '16px', fontSize: '1.25rem', display: 'flex', alignItems: 'center', gap: '8px' }}>
                                <span className="material-symbols-outlined" style={{ color: twoFactor.enabled ? '#10b981' : '#f59e0b' }}>
                                    {twoFactor.enabled ? 'verified_user' : 'gpp_maybe'}
                                </span>
                                Two-Factor Auth
                            </h3>
                            
                            <p style={{ color: 'var(--on-surface-variant)', fontSize: '14px', marginBottom: '24px' }}>
                                {twoFactor.enabled 
                                    ? "Two-Factor Authentication is currently active on your account. You will need to provide an authenticator code when logging in or performing sensitive actions."
                                    : "Add an extra layer of security to your account. Once enabled, you'll be prompted for a secure 6-digit code during login and sensitive actions (e.g. API key rotation)."
                                }
                            </p>

                            {tfaError && (
                                <div style={{ color: 'var(--error)', fontSize: '14px', marginBottom: '16px', padding: '12px', background: 'rgba(239, 68, 68, 0.1)', borderRadius: '8px' }}>
                                    {tfaError}
                                </div>
                            )}

                            {twoFactor.enabled ? (
                                <form onSubmit={disable2FA} style={{ display: 'flex', flexDirection: 'column', gap: '16px', background: 'rgba(255,255,255,0.02)', padding: '20px', borderRadius: '12px', border: '1px solid rgba(255,255,255,0.05)' }}>
                                    <p style={{ fontSize: '14px', fontWeight: 600, color: 'var(--error)' }}>Disable 2FA</p>
                                    <div>
                                        <input 
                                            type="password" 
                                            placeholder="Current Password"
                                            className="rh-input" 
                                            value={disablePassword} 
                                            onChange={e => setDisablePassword(e.target.value)}
                                            required
                                        />
                                    </div>
                                    <div>
                                        <input 
                                            type="text" 
                                            placeholder="6-digit Authenticator Code"
                                            className="rh-input" 
                                            value={tfaCode} 
                                            onChange={e => setTfaCode(e.target.value)}
                                            maxLength={6}
                                            required
                                        />
                                    </div>
                                    <button type="submit" disabled={tfaLoading || !tfaCode || !disablePassword} className="rh-button rh-button--danger">
                                        {tfaLoading ? 'Disabling...' : 'Disable 2FA'}
                                    </button>
                                </form>
                            ) : (
                                <div>
                                    {!qrData ? (
                                        <button onClick={setup2FA} disabled={tfaLoading} className="rh-button rh-button--primary" style={{ width: '100%' }}>
                                            {tfaLoading ? 'Generating Secret...' : 'Set Up 2FA'}
                                        </button>
                                    ) : (
                                        <div style={{ background: 'rgba(255,255,255,0.02)', padding: '20px', borderRadius: '12px', border: '1px solid rgba(255,255,255,0.05)' }}>
                                            <p style={{ fontSize: '13px', color: 'var(--on-surface-variant)', marginBottom: '16px' }}>
                                                1. Scan this QR code with your Google Authenticator or Authy app.
                                            </p>
                                            <div style={{ background: '#fff', padding: '16px', borderRadius: '8px', display: 'inline-block', marginBottom: '16px' }}>
                                                <img src={qrData.qr_image_url} alt="2FA QR Code" style={{ width: '150px', height: '150px' }} />
                                            </div>
                                            <p style={{ fontSize: '12px', color: 'var(--on-surface-variant)', marginBottom: '16px', wordBreak: 'break-all' }}>
                                                Manual setup key: <strong style={{ color: '#fff' }}>{qrData.secret}</strong>
                                            </p>
                                            <p style={{ fontSize: '13px', color: 'var(--on-surface-variant)', marginBottom: '12px' }}>
                                                2. Enter the 6-digit code from your app to verify.
                                            </p>
                                            <form onSubmit={enable2FA} style={{ display: 'flex', gap: '8px' }}>
                                                <input 
                                                    type="text" 
                                                    placeholder="000000"
                                                    className="rh-input" 
                                                    value={tfaCode} 
                                                    onChange={e => setTfaCode(e.target.value)}
                                                    maxLength={6}
                                                    style={{ letterSpacing: '4px', textAlign: 'center', fontSize: '18px', fontWeight: 'bold' }}
                                                    required
                                                />
                                                <button type="submit" disabled={tfaLoading || tfaCode.length < 6} className="rh-button rh-button--primary">
                                                    Verify
                                                </button>
                                            </form>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>

                        <div className="rh-card" id="reseller-push-card">
                            <h3 style={{ marginBottom: '16px', fontSize: '1.25rem', display: 'flex', alignItems: 'center', gap: '8px' }}>
                                <span className="material-symbols-outlined" style={{ color: pushStatus === 'Subscribed' ? '#10b981' : '#60a5fa' }}>
                                    notifications_active
                                </span>
                                Browser Push Notification
                            </h3>

                            <p style={{ color: 'var(--on-surface-variant)', fontSize: '14px', marginBottom: '20px' }}>
                                Aktifkan notifikasi browser untuk device ini, lalu kirim test manual untuk memastikan web push reseller sudah bekerja.
                            </p>

                            <div style={{ display: 'grid', gap: '10px', marginBottom: '16px' }}>
                                <div style={{ fontSize: '13px', color: 'var(--on-surface-variant)' }}>
                                    <strong style={{ color: '#fff' }}>Status:</strong> {pushStatus}
                                </div>
                                <div style={{ fontSize: '13px', color: 'var(--on-surface-variant)' }}>
                                    <strong style={{ color: '#fff' }}>Permission:</strong> {notificationPermission}
                                </div>
                                <div style={{ fontSize: '13px', color: 'var(--on-surface-variant)' }}>
                                    <strong style={{ color: '#fff' }}>Server config:</strong> {push.configured ? 'Ready' : 'Missing VAPID config'}
                                </div>
                                <div style={{ fontSize: '13px', color: 'var(--on-surface-variant)' }}>
                                    <strong style={{ color: '#fff' }}>Saved subscriptions:</strong> {push.subscriptionCount}
                                </div>
                            </div>

                            {!pushSupported && (
                                <div style={{ color: 'var(--error)', fontSize: '14px', marginBottom: '16px', padding: '12px', background: 'rgba(239, 68, 68, 0.1)', borderRadius: '8px' }}>
                                    Browser atau konteks saat ini tidak mendukung Web Push. Pastikan menggunakan HTTPS / secure context.
                                </div>
                            )}

                            {pushError && (
                                <div style={{ color: 'var(--error)', fontSize: '14px', marginBottom: '16px', padding: '12px', background: 'rgba(239, 68, 68, 0.1)', borderRadius: '8px' }}>
                                    {pushError}
                                </div>
                            )}

                            {pushTestMessage && (
                                <div style={{ color: '#10b981', fontSize: '14px', marginBottom: '16px', padding: '12px', background: 'rgba(16, 185, 129, 0.1)', borderRadius: '8px' }}>
                                    {pushTestMessage}
                                </div>
                            )}

                            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                                <button
                                    id="reseller-push-enable"
                                    type="button"
                                    onClick={subscribePush}
                                    disabled={!pushSupported || pushBusy || !push.configured}
                                    className="rh-button rh-button--primary"
                                >
                                    {pushBusy ? 'Processing...' : 'Aktifkan Push di Device Ini'}
                                </button>

                                <button
                                    id="reseller-push-test"
                                    type="button"
                                    onClick={sendTestPush}
                                    disabled={!pushSupported || pushTestBusy || pushBusy}
                                    className="rh-button"
                                    style={{ background: 'rgba(96, 165, 250, 0.14)', color: '#bfdbfe', border: '1px solid rgba(96, 165, 250, 0.28)' }}
                                >
                                    {pushTestBusy ? 'Sending Test...' : 'Kirim Notifikasi Test'}
                                </button>

                                <button
                                    id="reseller-push-disable"
                                    type="button"
                                    onClick={unsubscribePush}
                                    disabled={!pushSupported || pushBusy}
                                    className="rh-button rh-button--danger"
                                >
                                    {pushBusy ? 'Processing...' : 'Nonaktifkan Push di Device Ini'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ResellerLayout>
    );
}

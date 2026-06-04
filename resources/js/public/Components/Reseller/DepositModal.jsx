import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { usePage } from '@inertiajs/react';

export default function DepositModal({ isOpen, onClose }) {
    const { csrf_token } = usePage().props;
    const [step, setStep] = useState(1); // 1 = Form, 2 = Invoice
    const [methods, setMethods] = useState([]);
    const [isLoadingMethods, setIsLoadingMethods] = useState(false);
    
    // Form State
    const [amount, setAmount] = useState('');
    const [selectedMethod, setSelectedMethod] = useState(null);
    const [phone, setPhone] = useState('');
    
    // Submission State
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState(null);
    
    // Invoice State
    const [invoiceData, setInvoiceData] = useState(null);

    // Reset when modal closes
    useEffect(() => {
        if (!isOpen) {
            setTimeout(() => {
                setStep(1);
                setAmount('');
                setSelectedMethod(null);
                setPhone('');
                setError(null);
                setInvoiceData(null);
            }, 300); // Wait for transition
        } else {
            if (methods.length === 0) {
                fetchMethods();
            }
        }
    }, [isOpen]);

    const fetchMethods = async () => {
        setIsLoadingMethods(true);
        try {
            const res = await axios.get('/id/reseller/deposit-methods');
            if (res.data?.success) {
                setMethods(res.data.data);
            }
        } catch (err) {
            console.error('Failed to load deposit methods', err);
            setError('Gagal memuat metode pembayaran');
        } finally {
            setIsLoadingMethods(false);
        }
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(val || 0);
    };

    const handleQuickAmount = (val) => {
        setAmount(val.toString());
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(null);
        
        if (!amount || parseInt(amount) < 10000) {
            setError('Minimal deposit Rp 10.000');
            return;
        }
        if (!selectedMethod) {
            setError('Pilih metode pembayaran');
            return;
        }

        setIsSubmitting(true);
        try {
            const res = await axios.post('/id/deposit', {
                jumlah: amount,
                metode: selectedMethod,
                no_telfon: phone || '08123456789'
            }, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf_token
                }
            });

            if (res.data?.success) {
                setInvoiceData(res.data);
                setStep(2);
            } else {
                setError(res.data?.message || 'Gagal membuat deposit');
            }
        } catch (err) {
            const errMsg = err.response?.data?.errors?.msg || 
                           err.response?.data?.message || 
                           'Terjadi kesalahan saat memproses deposit.';
            setError(errMsg);
        } finally {
            setIsSubmitting(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="rh-modal-backdrop" style={backdropStyle}>
            <div className="rh-modal-content" style={contentStyle}>
                <button onClick={onClose} style={closeBtnStyle}>
                    <span className="material-symbols-outlined">close</span>
                </button>

                {step === 1 && (
                    <div style={{ padding: '32px' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '24px' }}>
                            <div style={{ width: '40px', height: '40px', borderRadius: '8px', background: 'var(--primary-container)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--on-primary-container)' }}>
                                <span className="material-symbols-outlined" style={{ fontVariationSettings: "'FILL' 1" }}>account_balance_wallet</span>
                            </div>
                            <div>
                                <h2 style={{ margin: 0, fontSize: '20px', fontFamily: 'var(--font-heading)', color: 'var(--primary)' }}>Add Credits</h2>
                                <p style={{ margin: 0, fontSize: '12px', color: 'var(--on-surface-variant)' }}>Top up your reseller balance.</p>
                            </div>
                        </div>

                        {error && (
                            <div style={{ padding: '12px', background: 'rgba(255, 71, 87, 0.1)', border: '1px solid var(--error)', borderRadius: '8px', color: 'var(--error)', fontSize: '13px', marginBottom: '24px' }}>
                                {error}
                            </div>
                        )}

                        <form onSubmit={handleSubmit}>
                            <div style={{ marginBottom: '24px' }}>
                                <label style={labelStyle}>Amount (IDR)</label>
                                <input 
                                    type="number" 
                                    min="10000"
                                    placeholder="Enter amount..."
                                    value={amount}
                                    onChange={(e) => setAmount(e.target.value)}
                                    style={inputStyle}
                                />
                                <div style={{ display: 'flex', gap: '8px', marginTop: '12px' }}>
                                    {[50000, 100000, 500000].map(val => (
                                        <button 
                                            key={val} 
                                            type="button" 
                                            onClick={() => handleQuickAmount(val)}
                                            style={quickBtnStyle}
                                        >
                                            {formatRupiah(val)}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div style={{ marginBottom: '24px' }}>
                                <label style={labelStyle}>Payment Method</label>
                                {isLoadingMethods ? (
                                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
                                        {[1, 2, 3, 4].map((n) => (
                                            <div 
                                                key={n} 
                                                style={{ 
                                                    ...methodCardStyle, 
                                                    background: 'rgba(255,255,255,0.02)', 
                                                    borderColor: 'rgba(255,255,255,0.05)' 
                                                }}
                                                className="animate-pulse"
                                            >
                                                <div style={{ width: '48px', height: '24px', background: 'rgba(255,255,255,0.1)', borderRadius: '4px', marginBottom: '8px' }}></div>
                                                <div style={{ width: '64px', height: '12px', background: 'rgba(255,255,255,0.1)', borderRadius: '4px' }}></div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', maxHeight: '200px', overflowY: 'auto' }} className="custom-scrollbar">
                                        {methods.map(method => (
                                            <div 
                                                key={method.code} 
                                                onClick={() => setSelectedMethod(method.code)}
                                                style={{
                                                    ...methodCardStyle,
                                                    borderColor: selectedMethod === method.code ? 'var(--primary)' : 'rgba(255,255,255,0.1)',
                                                    background: selectedMethod === method.code ? 'rgba(192, 193, 255, 0.1)' : 'rgba(0,0,0,0.2)'
                                                }}
                                            >
                                                {method.images && (
                                                    <img src={method.images.startsWith('http') ? method.images : (method.images.startsWith('/') ? method.images : `/${method.images}`)} alt={method.name} style={{ height: '24px', objectFit: 'contain', marginBottom: '8px' }} />
                                                )}
                                                <span style={{ fontSize: '12px', fontWeight: 600, color: selectedMethod === method.code ? 'var(--primary)' : 'var(--on-surface)' }}>{method.name}</span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {['OVO', 'DANA', 'SHOPEEPAY', 'LINKAJA', 'GOPAY', 'ASTRAPAY', 'VIRGO'].includes(selectedMethod?.toUpperCase()) && (
                                <div style={{ marginBottom: '24px' }}>
                                    <label style={labelStyle}>Phone Number (E-Wallet)</label>
                                    <input 
                                        type="text" 
                                        placeholder="08123xxxx"
                                        value={phone}
                                        onChange={(e) => setPhone(e.target.value)}
                                        style={inputStyle}
                                    />
                                </div>
                            )}

                            <button 
                                type="submit" 
                                disabled={isSubmitting} 
                                style={{
                                    ...submitBtnStyle,
                                    opacity: isSubmitting ? 0.6 : 1,
                                    cursor: isSubmitting ? 'not-allowed' : 'pointer',
                                    transform: isSubmitting ? 'none' : ''
                                }}
                            >
                                {isSubmitting ? 'Processing...' : 'Top Up Now'}
                            </button>
                        </form>
                    </div>
                )}

                {step === 2 && invoiceData && (
                    <div style={{ padding: '32px', textAlign: 'center' }}>
                        <div style={{ width: '64px', height: '64px', borderRadius: '50%', background: 'rgba(78, 222, 163, 0.1)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--tertiary)', margin: '0 auto 16px auto' }}>
                            <span className="material-symbols-outlined" style={{ fontSize: '32px' }}>check_circle</span>
                        </div>
                        <h2 style={{ margin: '0 0 8px 0', fontSize: '24px', fontFamily: 'var(--font-heading)', color: '#fff' }}>Deposit Created</h2>
                        <p style={{ margin: '0 0 24px 0', fontSize: '14px', color: 'var(--on-surface-variant)' }}>Please complete your payment before it expires.</p>

                        <div style={{ background: 'rgba(0,0,0,0.2)', border: '1px solid rgba(255,255,255,0.1)', borderRadius: '12px', padding: '16px', marginBottom: '24px' }}>
                            <div style={{ fontSize: '12px', color: 'var(--on-surface-variant)', marginBottom: '4px' }}>Order ID</div>
                            <div style={{ fontSize: '14px', fontFamily: 'monospace', color: 'var(--primary)', marginBottom: '16px' }}>{invoiceData.order_id}</div>
                            
                            <div style={{ fontSize: '12px', color: 'var(--on-surface-variant)', marginBottom: '4px' }}>Total Payment</div>
                            <div style={{ fontSize: '32px', fontWeight: 700, color: 'var(--tertiary)', fontFamily: 'var(--font-heading)', letterSpacing: '-1px' }}>
                                {formatRupiah(invoiceData.gross_amount)}
                            </div>
                        </div>

                        {invoiceData.pay_url && invoiceData.pay_url.includes('http') ? (
                            <a 
                                href={invoiceData.pay_url} 
                                target="_blank" 
                                rel="noreferrer"
                                style={submitBtnStyle}
                            >
                                Pay Now (External)
                            </a>
                        ) : invoiceData.va_number ? (
                            <div style={{ marginBottom: '24px' }}>
                                <div style={{ fontSize: '12px', color: 'var(--on-surface-variant)', marginBottom: '8px' }}>Virtual Account / Pay Code</div>
                                <div style={{ background: 'var(--primary-container)', color: 'var(--on-primary-container)', padding: '12px', borderRadius: '8px', fontSize: '20px', fontWeight: 700, letterSpacing: '2px', fontFamily: 'monospace' }}>
                                    {invoiceData.va_number}
                                </div>
                            </div>
                        ) : null}

                        <button onClick={onClose} style={{ ...submitBtnStyle, background: 'rgba(255,255,255,0.1)', color: 'var(--on-surface)', marginTop: '16px' }}>
                            Close & Go to Dashboard
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}

// Inline styles to match the Neon Fintech theme
const backdropStyle = {
    position: 'fixed',
    top: 0,
    left: 0,
    width: '100vw',
    height: '100vh',
    background: 'rgba(2, 6, 23, 0.8)',
    backdropFilter: 'blur(8px)',
    zIndex: 9999,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
};

const contentStyle = {
    width: '100%',
    maxWidth: '480px',
    background: 'rgba(21, 27, 45, 0.85)',
    backdropFilter: 'blur(24px)',
    border: '1px solid rgba(255, 255, 255, 0.1)',
    borderRadius: '24px',
    boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.5)',
    position: 'relative',
    overflow: 'hidden',
};

const closeBtnStyle = {
    position: 'absolute',
    top: '20px',
    right: '20px',
    background: 'transparent',
    border: 'none',
    color: 'var(--on-surface-variant)',
    cursor: 'pointer',
    padding: '4px',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: '50%',
    transition: 'all 0.2s',
};

const labelStyle = {
    display: 'block',
    fontSize: '12px',
    fontWeight: 600,
    color: 'var(--on-surface-variant)',
    textTransform: 'uppercase',
    letterSpacing: '0.05em',
    marginBottom: '8px',
};

const inputStyle = {
    width: '100%',
    background: 'rgba(0, 0, 0, 0.2)',
    border: '1px solid rgba(255, 255, 255, 0.1)',
    borderRadius: '8px',
    padding: '12px 16px',
    color: 'var(--on-surface)',
    fontFamily: 'var(--font-body)',
    fontSize: '16px',
    outline: 'none',
    transition: 'border-color 0.2s',
};

const quickBtnStyle = {
    background: 'rgba(255, 255, 255, 0.05)',
    border: '1px solid rgba(255, 255, 255, 0.1)',
    color: 'var(--on-surface-variant)',
    padding: '6px 12px',
    borderRadius: '100px',
    fontSize: '12px',
    cursor: 'pointer',
    transition: 'all 0.2s',
};

const methodCardStyle = {
    border: '1px solid',
    borderRadius: '12px',
    padding: '12px',
    cursor: 'pointer',
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
    transition: 'all 0.2s',
    textAlign: 'center'
};

const submitBtnStyle = {
    width: '100%',
    background: 'var(--primary)',
    color: 'var(--on-primary)',
    border: 'none',
    padding: '14px',
    borderRadius: '12px',
    fontSize: '14px',
    fontWeight: 600,
    cursor: 'pointer',
    fontFamily: 'var(--font-heading)',
    boxShadow: '0 0 15px rgba(192, 193, 255, 0.2)',
    transition: 'all 0.2s',
    display: 'inline-block',
    textDecoration: 'none',
    textAlign: 'center'
};

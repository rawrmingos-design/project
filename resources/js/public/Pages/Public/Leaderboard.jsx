import React, { useMemo, useState } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    })
        .format(Number(value || 0))
        .replace(/\u00A0/g, ' ');
}

const periods = [
    { key: 'daily', label: 'Hari Ini' },
    { key: 'weekly', label: 'Minggu Ini' },
    { key: 'monthly', label: 'Bulan Ini' },
];

export default function Leaderboard({ meta, leaderboards, companyName }) {
    const [period, setPeriod] = useState('daily');
    const activeItems = useMemo(() => (
        Array.isArray(leaderboards?.[period]) ? leaderboards[period] : []
    ), [leaderboards, period]);
    const podium = [activeItems[1], activeItems[0], activeItems[2]];
    const tableItems = activeItems.slice(3);

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section id="leaderboard" className="public-leaderboard public-leaderboard--bangjeff public-leaderboard--istanatopup">
                <div className="public-shell">
                    <header className="public-leaderboard__header">
                        <p className="public-leaderboard__eyebrow">Leaderboard</p>
                        <h1 className="public-leaderboard__title">Top 10 Pembelian Terbanyak di {(companyName || 'ISTANATOPUP').split(' - ')[0]}</h1>
                        <p className="public-leaderboard__description">
                            10 pembeli dengan total transaksi terbesar pada periode berjalan. Pemenang tiap periode mendapat hadiah saldo otomatis ke akun.
                        </p>
                    </header>

                    <div className="public-leaderboard__periods" role="tablist" aria-label="Periode leaderboard">
                        {periods.map((item) => (
                            <button
                                key={item.key}
                                type="button"
                                role="tab"
                                aria-selected={period === item.key}
                                className={`public-leaderboard__period ${period === item.key ? 'is-active' : ''}`}
                                onClick={() => setPeriod(item.key)}
                            >
                                {item.label}
                            </button>
                        ))}
                    </div>

                    {activeItems.length ? (
                        <>
                            <div className="public-leaderboard__podium" aria-label="Peringkat teratas">
                                {podium.map((item, index) => {
                                    const rank = [2, 1, 3][index];
                                    return (
                                        <article key={`${period}-podium-${rank}`} className={`public-leaderboard__podium-card ${rank === 1 ? 'is-first' : ''}`}>
                                            <span className="public-leaderboard__rank">{rank}</span>
                                            <strong>{item?.username || '-'}</strong>
                                            <span>{item ? `${item.total ? formatCurrency(item.total) : 'Rp 0'}` : '-'}</span>
                                            <small>{item ? 'Total transaksi' : 'Belum ada data'}</small>
                                        </article>
                                    );
                                })}
                            </div>

                            <div className="public-leaderboard__table-wrap">
                                <div className="public-leaderboard__table-row is-head">
                                    <span>Rank</span>
                                    <span>Nama</span>
                                    <span>Transaksi</span>
                                    <span>Total Belanja</span>
                                </div>
                                {tableItems.map((item, index) => (
                                    <div key={`${period}-row-${index}`} className="public-leaderboard__table-row">
                                        <span className="public-leaderboard__table-rank">{index + 4}</span>
                                        <strong>{item.username}</strong>
                                        <span>—</span>
                                        <strong className="public-leaderboard__table-total">{formatCurrency(item.total)}</strong>
                                    </div>
                                ))}
                            </div>

                            <div className="public-leaderboard__rewards">
                                <div><strong>Juara 1</strong><span>Saldo akun <b>Rp 100.000</b></span></div>
                                <div><strong>Juara 2</strong><span>Saldo akun <b>Rp 50.000</b></span></div>
                                <div><strong>Juara 3</strong><span>Saldo akun <b>Rp 25.000</b></span></div>
                            </div>
                            <p className="public-leaderboard__note">Nama disamarkan untuk menjaga privasi. Hadiah dikirim otomatis maksimal 1x24 jam setelah periode berakhir.</p>
                        </>
                    ) : (
                        <div className="public-leaderboard__empty">Belum ada data transaksi untuk periode ini.</div>
                    )}
                </div>
            </section>
        </PublicLayout>
    );
}

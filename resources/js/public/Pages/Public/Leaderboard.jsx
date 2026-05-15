import React from 'react';
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

const medalByIndex = ['🥇', '🥈', '🥉'];

function LeaderboardCard({ title, items = [] }) {
    return (
        <section className="public-leaderboard__panel" aria-label={title}>
            <h2 className="public-leaderboard__tab">{title}</h2>
            <div className="public-leaderboard__card">
                {items.length ? (
                    <ol className="public-leaderboard__list">
                        {items.map((item, index) => (
                            <li key={`${title}-${index}`} className="public-leaderboard__row">
                                <div className="public-leaderboard__identity">
                                    <span className="public-leaderboard__name">{index + 1}. {item.username}</span>
                                    {index <= 2 ? <span className="public-leaderboard__medal" aria-hidden="true">{medalByIndex[index]}</span> : null}
                                </div>
                                <span className="public-leaderboard__value">{formatCurrency(item.total)}</span>
                            </li>
                        ))}
                    </ol>
                ) : (
                    <p className="public-leaderboard__empty">Belum ada data transaksi.</p>
                )}
            </div>
        </section>
    );
}

export default function Leaderboard({ meta, leaderboards, companyName }) {
    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section id="leaderboard" className="public-leaderboard public-leaderboard--bangjeff">
                <div className="public-shell">
                    <header className="public-leaderboard__header">
                        <p className="public-leaderboard__eyebrow">Leaderboard</p>
                        <h1 className="public-leaderboard__title">Top 10 Pembelian Terbanyak di {companyName || 'BANGJEFF'}</h1>
                        <p className="public-leaderboard__description">
                            Berikut ini adalah daftar 10 pembelian terbanyak yang dilakukan oleh pelanggan kami.
                            Data ini diambil dari sistem kami dan selalu diperbaharui.
                        </p>
                    </header>

                    <div className="public-leaderboard__grid">
                        <LeaderboardCard title="Top 10 - Hari Ini" items={leaderboards?.daily || []} />
                        <LeaderboardCard title="Top 10 - Minggu Ini" items={leaderboards?.weekly || []} />
                        <LeaderboardCard title="Top 10 - Bulan Ini" items={leaderboards?.monthly || []} />
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

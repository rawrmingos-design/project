import React, { useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

const CALCULATOR_CONFIG = {
    winrate: {
        title: 'Kalkulator Win Rate',
        description: 'Digunakan untuk menghitung total jumlah pertandingan yang harus ditempuh untuk mencapai target win rate yang diinginkan.',
        route: '/id/calculator/winrate',
    },
    'magic-wheel': {
        title: 'Kalkulator Magic Wheel',
        description: 'Digunakan untuk mengetahui total maksimal diamond yang dibutuhkan untuk mendapatkan skin Legends.',
        route: '/id/calculator/magic-wheel',
    },
    zodiac: {
        title: 'Kalkulator Zodiac',
        description: 'Digunakan untuk mengetahui total diamond maksimal yang dibutuhkan untuk mendapatkan skin Zodiacs.',
        route: '/id/calculator/zodiac',
    },
};

function formatNumber(value) {
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value || 0));
}

function calculateWinRateMatches(totalMatch, totalWr, requestedWr) {
    const tWin = totalMatch * (totalWr / 100);
    const tLose = totalMatch - tWin;
    const remainingWr = 100 - requestedWr;
    const wrResult = 100 / remainingWr;
    const oneHundredPercent = tLose * wrResult;
    const finalResult = oneHundredPercent - totalMatch;

    return Math.max(0, Math.round(finalResult));
}

export default function Calculator({ meta, calculator }) {
    const selectedType = CALCULATOR_CONFIG[calculator?.type] ? calculator.type : 'winrate';
    const config = CALCULATOR_CONFIG[selectedType];

    const [totalMatch, setTotalMatch] = useState('');
    const [totalWr, setTotalWr] = useState('');
    const [requestedWr, setRequestedWr] = useState('');
    const [winrateResult, setWinrateResult] = useState(null);
    const [winrateError, setWinrateError] = useState('');

    const [magicWheelPoint, setMagicWheelPoint] = useState(100);
    const [zodiacPoint, setZodiacPoint] = useState(50);

    const magicWheelDiamond = useMemo(() => {
        const remainingSpin = 200 - magicWheelPoint;

        if (remainingSpin < 196) {
            return Math.ceil(remainingSpin / 5) * 270;
        }

        return remainingSpin * 60;
    }, [magicWheelPoint]);

    const zodiacDiamond = useMemo(() => {
        if (zodiacPoint < 90) {
            return Math.ceil((2000 - zodiacPoint * 20) * 850 / 1000);
        }

        return Math.ceil(2000 - zodiacPoint * 20);
    }, [zodiacPoint]);

    const handleCalculateWinRate = () => {
        const match = Number(totalMatch);
        const currentWr = Number(totalWr);
        const targetWr = Number(requestedWr);

        if (!Number.isFinite(match) || !Number.isFinite(currentWr) || !Number.isFinite(targetWr)) {
            setWinrateError('Semua field wajib diisi dengan angka yang valid.');
            setWinrateResult(null);
            return;
        }

        if (match <= 0) {
            setWinrateError('Total pertandingan harus lebih dari 0.');
            setWinrateResult(null);
            return;
        }

        if (currentWr < 0 || currentWr >= 100 || targetWr <= 0 || targetWr >= 100) {
            setWinrateError('Win rate harus berada di rentang 1 sampai 99.');
            setWinrateResult(null);
            return;
        }

        if (targetWr <= currentWr) {
            setWinrateError('Target win rate harus lebih tinggi dari win rate saat ini.');
            setWinrateResult(null);
            return;
        }

        setWinrateError('');
        setWinrateResult(calculateWinRateMatches(match, currentWr, targetWr));
    };

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section id="calculator" className="public-calculator public-calculator--bangjeff">
                <div className="public-shell">
                    <div className="public-calculator__wrap">
                        <nav className="public-calculator__nav" aria-label="Menu kalkulator">
                            {Object.entries(CALCULATOR_CONFIG).map(([key, item]) => (
                                <Link
                                    key={key}
                                    href={item.route}
                                    className={`public-calculator__nav-item ${selectedType === key ? 'is-active' : ''}`}
                                >
                                    {item.title.replace('Kalkulator ', '')}
                                </Link>
                            ))}
                        </nav>

                        <header className="public-calculator__header">
                            <h1>{config.title}</h1>
                            <p>{config.description}</p>
                        </header>

                        {selectedType === 'winrate' ? (
                            <div className="public-calculator__panel">
                                <div className="public-calculator__field-grid">
                                    <label className="public-calculator__field">
                                        <span>Total Pertandingan Anda Saat Ini</span>
                                        <input
                                            type="number"
                                            inputMode="numeric"
                                            min="1"
                                            placeholder="Contoh: 223"
                                            value={totalMatch}
                                            onChange={(event) => setTotalMatch(event.target.value)}
                                        />
                                    </label>

                                    <label className="public-calculator__field">
                                        <span>Total Win Rate Anda Saat Ini</span>
                                        <input
                                            type="number"
                                            inputMode="decimal"
                                            min="1"
                                            max="99"
                                            placeholder="Contoh: 54"
                                            value={totalWr}
                                            onChange={(event) => setTotalWr(event.target.value)}
                                        />
                                    </label>

                                    <label className="public-calculator__field">
                                        <span>Win Rate Total yang Anda Inginkan</span>
                                        <input
                                            type="number"
                                            inputMode="decimal"
                                            min="1"
                                            max="99"
                                            placeholder="Contoh: 70"
                                            value={requestedWr}
                                            onChange={(event) => setRequestedWr(event.target.value)}
                                        />
                                    </label>
                                </div>

                                <div className="public-calculator__actions">
                                    <button type="button" className="public-calculator__button" onClick={handleCalculateWinRate}>Hitung</button>
                                    <Link href="/id" className="public-calculator__button public-calculator__button--ghost">Pesan Joki</Link>
                                </div>

                                <div className={`public-calculator__result ${winrateError || winrateResult !== null ? 'is-visible' : ''}`}>
                                    {winrateError
                                        ? <span>{winrateError}</span>
                                        : (winrateResult !== null
                                            ? (
                                                <span>
                                                    You need about <strong>{formatNumber(winrateResult)} Win without Lose</strong> to get a <strong>{requestedWr}% Win Rate</strong>.
                                                </span>
                                            )
                                            : null)}
                                </div>
                            </div>
                        ) : null}

                        {selectedType === 'magic-wheel' ? (
                            <div className="public-calculator__panel">
                                <label className="public-calculator__field">
                                    <span>Geser sesuai dengan Titik Magic Wheel Anda</span>
                                    <input
                                        type="range"
                                        min="0"
                                        max="199"
                                        value={magicWheelPoint}
                                        onChange={(event) => setMagicWheelPoint(Number(event.target.value))}
                                    />
                                </label>

                                <div className="public-calculator__range-meta">
                                    <p>Poin Bintang Kamu <strong>{formatNumber(magicWheelPoint)}</strong></p>
                                    <p>Membutuhkan Maksimal <strong>{formatNumber(magicWheelDiamond)} Diamond</strong></p>
                                </div>

                                <div className="public-calculator__actions public-calculator__actions--single">
                                    <Link href="/id" className="public-calculator__button">Top Up Diamond Sekarang!</Link>
                                </div>
                            </div>
                        ) : null}

                        {selectedType === 'zodiac' ? (
                            <div className="public-calculator__panel">
                                <label className="public-calculator__field">
                                    <span>Geser sesuai dengan Titik Zodiac Anda</span>
                                    <input
                                        type="range"
                                        min="0"
                                        max="99"
                                        value={zodiacPoint}
                                        onChange={(event) => setZodiacPoint(Number(event.target.value))}
                                    />
                                </label>

                                <div className="public-calculator__range-meta">
                                    <p>Poin Bintang Kamu <strong>{formatNumber(zodiacPoint)}</strong></p>
                                    <p>Membutuhkan Maksimal <strong>{formatNumber(zodiacDiamond)} Diamond</strong></p>
                                </div>

                                <div className="public-calculator__actions public-calculator__actions--single">
                                    <Link href="/id" className="public-calculator__button">Top Up Diamond Sekarang!</Link>
                                </div>
                            </div>
                        ) : null}
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

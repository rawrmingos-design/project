import React, { useMemo, useState } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}

export default function PriceList({ meta, priceList }) {
    const categories = Array.isArray(priceList?.categories) ? priceList.categories : [];
    const products = Array.isArray(priceList?.products) ? priceList.products : [];
    const [query, setQuery] = useState('');
    const [categoryId, setCategoryId] = useState('');

    const visibleProducts = useMemo(() => {
        const normalizedQuery = query.trim().toLowerCase();

        return products.filter((product) => {
            const matchesCategory = !categoryId || String(product.categoryId) === categoryId;
            const searchable = `${product.categoryName || ''} ${product.name || ''} ${product.providerId || ''}`.toLowerCase();
            return matchesCategory && (!normalizedQuery || searchable.includes(normalizedQuery));
        });
    }, [categoryId, products, query]);

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-information-page">
                <div className="public-shell">
                    <header className="public-information-page__hero">
                        <p className="public-information-page__eyebrow">Katalog</p>
                        <h1>Daftar Harga</h1>
                        <p>Lihat daftar harga produk yang tersedia sebelum melanjutkan pembelian.</p>
                    </header>

                    <section className="public-information-card">
                        <div className="public-information-card__toolbar">
                            <label>
                                <span>Cari produk</span>
                                <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Nama item atau kode provider" />
                            </label>
                            <label>
                                <span>Kategori</span>
                                <select value={categoryId} onChange={(event) => setCategoryId(event.target.value)}>
                                    <option value="">Semua kategori</option>
                                    {categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
                                </select>
                            </label>
                        </div>

                        <p className="public-information-card__meta">
                            {visibleProducts.length} produk tersedia · {Number(priceList?.paymentMethodCount || 0)} metode pembayaran aktif
                        </p>

                        <div className="public-information-table-wrap">
                            <table className="public-information-table">
                                <thead>
                                    <tr>
                                        <th>Kategori</th>
                                        <th>Item produk</th>
                                        <th>Member</th>
                                        <th>Gold</th>
                                        <th>Platinum</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {visibleProducts.map((product) => (
                                        <tr key={product.id}>
                                            <td>{product.categoryName || '-'}</td>
                                            <td>
                                                <strong>{product.name || '-'}</strong>
                                                {product.providerId ? <small>{product.providerId}</small> : null}
                                            </td>
                                            <td>{formatCurrency(product.memberPrice)}</td>
                                            <td>{formatCurrency(product.goldPrice)}</td>
                                            <td>{formatCurrency(product.platinumPrice)}</td>
                                            <td><span className="public-information-status">{product.status || '-'}</span></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {!visibleProducts.length ? <p className="public-information-empty">Produk tidak ditemukan.</p> : null}
                    </section>
                </div>
            </section>
        </PublicLayout>
    );
}

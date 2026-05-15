import React from 'react';

export default function PaymentMethodCard({ method, selected, onSelect }) {
    return (
        <button
            type="button"
            className={`payment-card ${selected ? 'is-active' : ''}`}
            onClick={() => onSelect(method)}
        >
            <div className="payment-card__media">
                {method.image ? <img src={method.image} alt={method.name} /> : <span>{method.name.slice(0, 1)}</span>}
            </div>
            <div className="payment-card__body">
                <strong>{method.name}</strong>
                <span>{method.group} · {method.gateway}</span>
            </div>
        </button>
    );
}

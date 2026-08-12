import {
    getMethodFinalPrice,
    getSelectedBaseAmount,
    getSelectedFeeAmount,
    getSelectedFinalPrice,
} from '../../resources/js/public/orderPricing';

describe('order pricing display helpers', () => {
    const pricePreview = {
        harga: 20000,
        selected_final_price: 20600,
        method_prices: {
            DANA: {
                base_amount: 20000,
                fee_amount: 600,
                final_price: 20600,
            },
            QRIS: {
                base_amount: 20000,
                fee_amount: 240,
                final_price: 20240,
            },
        },
    };

    it('uses the backend final price for each payment method', () => {
        expect(getMethodFinalPrice(pricePreview, 'DANA')).toBe(20600);
        expect(getMethodFinalPrice(pricePreview, 'QRIS')).toBe(20240);
        expect(getSelectedFinalPrice(pricePreview, 'QRIS')).toBe(20240);
    });

    it('uses the backend fee and base amount fields directly', () => {
        expect(getSelectedFeeAmount(pricePreview, 'DANA')).toBe(600);
        expect(getSelectedBaseAmount(pricePreview, 'DANA')).toBe(20000);
    });

    it('falls back to the selected response total without inferring a fee', () => {
        const legacyPreview = {
            harga: 20500,
            selected_final_price: 20500,
        };

        expect(getMethodFinalPrice(legacyPreview, 'DANA')).toBeNull();
        expect(getSelectedFinalPrice(legacyPreview, 'DANA')).toBe(20500);
        expect(getSelectedFeeAmount(legacyPreview, 'DANA')).toBeNull();
    });

    it('does not use another method total as a fallback', () => {
        expect(getMethodFinalPrice(pricePreview, 'UNKNOWN')).toBeNull();
    });
});

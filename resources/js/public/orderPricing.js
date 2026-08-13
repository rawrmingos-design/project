export function getMethodPrice(pricePreview, methodCode) {
    if (!methodCode || !pricePreview?.method_prices || typeof pricePreview.method_prices !== 'object') {
        return null;
    }

    const price = pricePreview.method_prices[methodCode];
    return price && typeof price === 'object' ? price : null;
}

export function getFinitePriceValue(value, fallback = null) {
    const normalized = Number(value);

    return Number.isFinite(normalized) ? normalized : fallback;
}

export function getMethodFinalPrice(pricePreview, methodCode, fallback = null) {
    const methodPrice = getMethodPrice(pricePreview, methodCode);
    const methodFinalPrice = getFinitePriceValue(methodPrice?.final_price);

    if (methodFinalPrice !== null) {
        return methodFinalPrice;
    }

    return fallback;
}

export function getSelectedFinalPrice(pricePreview, selectedMethodCode, fallback = 0) {
    const methodFinalPrice = getFinitePriceValue(
        getMethodPrice(pricePreview, selectedMethodCode)?.final_price,
    );

    if (methodFinalPrice !== null) {
        return methodFinalPrice;
    }

    return getFinitePriceValue(
        pricePreview?.selected_final_price ?? pricePreview?.harga,
        fallback,
    );
}

export function getSelectedFeeAmount(pricePreview, selectedMethodCode, fallback = null) {
    return getFinitePriceValue(
        getMethodPrice(pricePreview, selectedMethodCode)?.fee_amount,
        fallback,
    );
}

export function getSelectedBaseAmount(pricePreview, selectedMethodCode, fallback = 0) {
    return getFinitePriceValue(
        getMethodPrice(pricePreview, selectedMethodCode)?.base_amount,
        fallback,
    );
}

export function getSelectedAmountBeforePoint(pricePreview, selectedMethodCode, fallback = null) {
    return getFinitePriceValue(
        getMethodPrice(pricePreview, selectedMethodCode)?.amount_before_point,
        fallback,
    );
}

export function getSelectedPointDiscount(pricePreview, selectedMethodCode, fallback = 0) {
    return getFinitePriceValue(
        getMethodPrice(pricePreview, selectedMethodCode)?.point_discount
            ?? pricePreview?.point_discount,
        fallback,
    );
}

/* Keep checkout and correction previews in centavos, matching DiscountPolicy. */
(() => {
    'use strict';
    const cents = value => Math.round((Number(value) + Number.EPSILON) * 100);
    const calculate = (subtotal, rule) => {
        const eligible = Math.max(0, cents(subtotal));
        if (eligible < cents(rule.minimum || 0)) return 0;
        const value = cents(rule.value);
        let discount = rule.type === 'percentage' ? Math.floor((eligible * value + 5000) / 10000) : value;
        if (rule.maximum != null) discount = Math.min(discount, cents(rule.maximum));
        return Math.min(eligible, Math.max(0, discount)) / 100;
    };
    window.PharxmacoDiscount = Object.freeze({ cents, calculate });
})();

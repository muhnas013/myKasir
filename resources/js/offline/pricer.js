// Estimasi total offline — cermin App\Services\PriceCalculator (docs/06 "Aturan perhitungan").
// HANYA untuk tampilan struk "Estimasi — belum sinkron"; server (sync) selalu menghitung ulang
// dan itulah yang berlaku (docs/21_SECURITY_RULES.md).

function applyBp(amount, bp) {
    return Math.floor((amount * bp + 5000) / 10000);
}

/**
 * @param {{price:number, qty:number}[]} lines
 * @param {{service_enabled:boolean, service_rate_bp:number, tax_enabled:boolean, tax_rate_bp:number, rounding_unit:number}} settings
 * @param {boolean} dineIn
 */
export function estimateTotal(lines, settings, dineIn) {
    const subtotal = lines.reduce((sum, l) => sum + l.price * l.qty, 0);
    const net = subtotal;
    const service = dineIn && settings.service_enabled ? applyBp(net, settings.service_rate_bp) : 0;
    const tax = settings.tax_enabled ? applyBp(net + service, settings.tax_rate_bp) : 0;

    const raw = net + service + tax;
    const unit = Math.max(1, settings.rounding_unit);
    const total = Math.floor((raw + Math.floor(unit / 2)) / unit) * unit;

    return { subtotal, service, tax, rounding: total - raw, total };
}

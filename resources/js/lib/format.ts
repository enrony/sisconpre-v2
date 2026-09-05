const money = new Intl.NumberFormat('es-CO', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});

/** Formatea un importe: 250000 -> "250.000". */
export function formatNumber(
    value: number | string | null | undefined,
): string {
    const n = typeof value === 'string' ? Number(value) : (value ?? 0);

    return Number.isFinite(n) ? money.format(n) : '0';
}

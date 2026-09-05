import dayjs, { type Dayjs } from 'dayjs';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';

dayjs.extend(isSameOrBefore);

/**
 * Motor de generación del calendario de cuotas de un préstamo.
 *
 * Port fiel de `generaListPays` / `calculateLastDate` / `applySurchage` del
 * store Vue 2 del sistema legado (`store/prestamos.ts`), de moment+lodash a
 * dayjs. La lógica financiera NO se cambia. Ver PLAN_MIGRACION.md §7.
 */

export const SIGLAS_DIAS = [
    'DOM',
    'LUN',
    'MAR',
    'MIÉ',
    'JUE',
    'VIE',
    'SÁB',
] as const;

/** Colores de texto por estado de cuota (0 futura, 2 vencida, 3 hoy). */
const TEXT_COLOR = [
    'text-gray-700',
    'text-green-700',
    'text-red-700',
    'text-blue-500',
];

export interface Holiday {
    date: string; // YYYY-MM-DD
}

export interface Cuota {
    date: string;
    apply: boolean;
    festivo: boolean;
    dom: boolean;
    cuota: number;
    date_change: boolean;
    date_before: string | null;
    sigla: string;
    textColorCuotas: string;
    diff: number;
    apply_surcharge: boolean;
    surcharge: number;
    days_apply_surcharge: number;
    day_apply_surcharge: string | null;
}

export interface GenerateParams {
    dateFirstPay: string | Dayjs;
    dateLastPay: string | Dayjs;
    montoPrestamo: number;
    tasa: number;
    /** cantidad + unidad de la frecuencia elegida (p. ej. 7 + 'days'). */
    frecuenciaCantidad: number;
    frecuenciaUnidad: dayjs.ManipulateType;
    incluirFestivos: boolean;
    incluirDomingos: boolean;
    cuotaSugerida: boolean;
    valorCuotaSugerida: number;
    holidays: Holiday[];
}

export interface GenerateResult {
    listPays: Cuota[];
    utilidad: number;
    total: number;
    cuota: number;
    cuotaEstablecida: number;
}

/** Última fecha de pago = primera + (cantidad · unidad) − 1 día. */
export function calcLastDate(
    dateFirstPay: string | Dayjs,
    cantidad: number,
    unidad: dayjs.ManipulateType,
): string {
    return dayjs(dateFirstPay)
        .add(cantidad, unidad)
        .add(-1, 'day')
        .format('YYYY-MM-DD');
}

function roundCuota(value: number): number {
    let divi = 1;
    let fixed = 1;

    if (value >= 100) {
        divi = 10;
        fixed = 0;
    }
    if (value >= 1000) {
        divi = 100;
        fixed = 0;
    }
    if (value >= 10000) {
        divi = 100;
        fixed = 0;
    }
    if (value >= 100000) {
        divi = 10000;
        fixed = 0;
    }

    return fixed === 0
        ? Number((Math.floor(value / divi) * divi).toFixed(0))
        : Number(((value / divi) * divi).toFixed(1));
}

export function generateSchedule(p: GenerateParams): GenerateResult {
    const first = dayjs(p.dateFirstPay);
    const last = dayjs(p.dateLastPay);
    const today = dayjs().startOf('day');
    const holidaySet = new Set(p.holidays.map((h) => h.date));

    const listPays: Cuota[] = [];
    let cursor = first;
    let firstRowDone = false;

    while (true) {
        if (firstRowDone) {
            cursor = cursor
                .add(p.frecuenciaCantidad, p.frecuenciaUnidad)
                .startOf('day');
        }
        firstRowDone = true;

        if (!cursor.isSameOrBefore(last, 'day')) {
            break;
        }

        const iso = cursor.format('YYYY-MM-DD');
        const esFestivo = holidaySet.has(iso);
        let apply = p.incluirFestivos ? true : !esFestivo;
        let dom = false;

        if (cursor.day() === 0) {
            dom = true;
            apply = p.incluirDomingos;
        }

        let estado = 0;
        if (cursor.isBefore(today, 'day')) {
            estado = 2;
        }
        if (iso === today.format('YYYY-MM-DD')) {
            estado = 3;
        }

        listPays.push({
            date: iso,
            apply,
            festivo: esFestivo,
            dom,
            cuota: 0,
            date_change: false,
            date_before: null,
            sigla: SIGLAS_DIAS[cursor.day()],
            textColorCuotas: apply ? TEXT_COLOR[estado] : TEXT_COLOR[0],
            diff: today.diff(cursor, 'day'),
            apply_surcharge: false,
            surcharge: 0,
            days_apply_surcharge: 0,
            day_apply_surcharge: null,
        });
    }

    let utilidad = 0;
    let total = 0;
    let cuotaBase = 0;
    let cuotaEstablecida = 0;

    if (listPays.length > 0) {
        utilidad = Math.ceil((p.montoPrestamo * p.tasa) / 100);
        total = Number(p.montoPrestamo) + utilidad;

        const aplicables = listPays.filter((c) => c.apply).length;
        cuotaBase = aplicables > 0 ? total / aplicables : 0;

        let cuota =
            p.cuotaSugerida && p.valorCuotaSugerida > 0
                ? p.valorCuotaSugerida
                : cuotaBase;

        cuota = roundCuota(cuota);
        cuotaEstablecida = cuota;

        const lastCuota = cuota + (total - cuota * aplicables);

        let acum = 0;
        for (const row of listPays) {
            if (!row.apply) {
                continue;
            }
            const before = acum;
            acum = cuota + acum;
            let asignada = cuota;

            if (acum > total) {
                asignada = before <= total ? total - before : 0;
            }
            row.cuota = asignada;
            if (asignada === 0) {
                row.apply = false;
            }
        }

        const lastApplied = [...listPays].reverse().find((c) => c.apply);
        if (lastApplied && lastApplied.cuota > 0 && lastCuota > 0) {
            lastApplied.cuota = lastCuota;
        }
    }

    return { listPays, utilidad, total, cuota: cuotaBase, cuotaEstablecida };
}

export interface SurchargeParams {
    applySurcharge: boolean;
    surcharge: number;
    daysApplySurcharge: number;
    incluirFestivosSurcharge: boolean;
    incluirDomingosSurcharge: boolean;
    holidays: Holiday[];
}

/** Marca en cada cuota la fecha en que aplica el recargo por mora. */
export function applySurcharge(listPays: Cuota[], p: SurchargeParams): void {
    if (
        listPays.length === 0 ||
        !p.applySurcharge ||
        p.surcharge <= 0 ||
        p.daysApplySurcharge <= 0
    ) {
        return;
    }

    const holidaySet = new Set(p.holidays.map((h) => h.date));

    for (const row of listPays) {
        let scan = dayjs(row.date);
        let validos = 0;

        for (let i = 1; i < 100; i++) {
            scan = scan.add(1, 'day');
            const esFestivo = holidaySet.has(scan.format('YYYY-MM-DD'));
            let apply = p.incluirFestivosSurcharge || !esFestivo;

            if (scan.day() === 0 && !p.incluirDomingosSurcharge) {
                apply = false;
            }
            if (apply) {
                validos++;
            }
            if (validos === p.daysApplySurcharge) {
                row.day_apply_surcharge = scan.format('YYYY-MM-DD');
                row.surcharge = p.surcharge;
                row.apply_surcharge = true;
                row.days_apply_surcharge = p.daysApplySurcharge;
                break;
            }
        }
    }
}

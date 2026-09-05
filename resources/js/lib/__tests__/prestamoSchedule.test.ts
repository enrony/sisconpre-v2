import { describe, expect, it } from 'vitest';
import {
    applySurcharge,
    calcLastDate,
    type Cuota,
    generateSchedule,
} from '@/lib/prestamoSchedule';

/**
 * Correctitud numérica del motor de calendario de cuotas, contra el
 * comportamiento del algoritmo `generaListPays` del sistema legado
 * (`store/prestamos.ts` de prestamos_16). Valores esperados calculados
 * trazando ese algoritmo a mano.
 */

const base = {
    montoPrestamo: 250000,
    tasa: 20, // => utilidad 50000, total 300000
    frecuenciaCantidad: 1,
    frecuenciaUnidad: 'day' as const,
    incluirFestivos: true,
    incluirDomingos: true,
    cuotaSugerida: false,
    valorCuotaSugerida: 0,
    holidays: [],
};

describe('calcLastDate', () => {
    it('suma cantidad·unidad y resta un día', () => {
        expect(calcLastDate('2026-01-05', 7, 'day')).toBe('2026-01-11');
    });

    it('recorta el mes corto (31-ene + 1 mes − 1 día)', () => {
        expect(calcLastDate('2026-01-31', 1, 'month')).toBe('2026-02-27');
    });
});

describe('generateSchedule — reparto exacto', () => {
    it('divide sin residuo: 10 cuotas iguales que suman el total', () => {
        const r = generateSchedule({
            ...base,
            dateFirstPay: '2026-01-05',
            dateLastPay: '2026-01-14',
        });

        expect(r.listPays).toHaveLength(10);
        expect(r.listPays.every((c) => c.apply)).toBe(true);
        expect(r.utilidad).toBe(50000);
        expect(r.total).toBe(300000);
        expect(r.cuotaEstablecida).toBe(30000);
        expect(r.listPays.every((c) => c.cuota === 30000)).toBe(true);
        expect(sum(r.listPays)).toBe(300000);
        expect(r.listPays[0].sigla).toBe('LUN');
        expect(r.listPays[6].sigla).toBe('DOM'); // 2026-01-11
    });

    it('redondea por tramos y ajusta la última cuota (7 cuotas)', () => {
        const r = generateSchedule({
            ...base,
            dateFirstPay: '2026-02-02',
            dateLastPay: '2026-02-08',
        });

        // 300000 / 7 = 42857.14 -> tramo >=10000 -> floor(.../100)*100 = 42800
        expect(r.cuotaEstablecida).toBe(42800);
        expect(r.listPays).toHaveLength(7);
        expect(r.listPays.slice(0, 6).every((c) => c.cuota === 42800)).toBe(
            true,
        );
        // última = 42800 + (300000 - 42800*7) = 43200
        expect(r.listPays[6].cuota).toBe(43200);
        expect(sum(r.listPays)).toBe(300000);
    });
});

describe('generateSchedule — exclusiones', () => {
    it('excluye domingos cuando incluirDomingos = false', () => {
        const r = generateSchedule({
            ...base,
            incluirDomingos: false,
            dateFirstPay: '2026-02-05',
            dateLastPay: '2026-02-09',
        });

        const domingo = r.listPays.find((c) => c.date === '2026-02-08')!;
        expect(domingo.dom).toBe(true);
        expect(domingo.apply).toBe(false);
        // jue, vie, sáb, lun aplican (el sábado NO se excluye)
        expect(r.listPays.filter((c) => c.apply)).toHaveLength(4);
    });

    it('excluye festivos cuando incluirFestivos = false', () => {
        const r = generateSchedule({
            ...base,
            incluirFestivos: false,
            holidays: [{ date: '2026-02-06' }],
            dateFirstPay: '2026-02-05',
            dateLastPay: '2026-02-07',
        });

        const festivo = r.listPays.find((c) => c.date === '2026-02-06')!;
        expect(festivo.festivo).toBe(true);
        expect(festivo.apply).toBe(false);
        expect(r.listPays.filter((c) => c.apply)).toHaveLength(2);
    });
});

describe('generateSchedule — cuota sugerida', () => {
    it('usa el valor sugerido y desmarca las cuotas que exceden el total', () => {
        const r = generateSchedule({
            ...base,
            cuotaSugerida: true,
            valorCuotaSugerida: 50000,
            dateFirstPay: '2026-01-05',
            dateLastPay: '2026-01-14', // 10 días
        });

        // total 300000 / cuota 50000 => 6 cuotas aplican, 4 quedan en 0/apply=false
        expect(r.cuotaEstablecida).toBe(50000);
        expect(r.listPays.filter((c) => c.apply)).toHaveLength(6);
        expect(sum(r.listPays)).toBe(300000);
    });
});

describe('applySurcharge', () => {
    it('marca el recargo N días hábiles después, saltando domingos', () => {
        const list: Cuota[] = [cuota('2026-02-05')];

        applySurcharge(list, {
            applySurcharge: true,
            surcharge: 10,
            daysApplySurcharge: 3,
            incluirFestivosSurcharge: false,
            incluirDomingosSurcharge: false,
            holidays: [],
        });

        // 06(vie) 07(sáb) -> 2 ; 08(dom) no cuenta ; 09(lun) -> 3
        expect(list[0].apply_surcharge).toBe(true);
        expect(list[0].surcharge).toBe(10);
        expect(list[0].days_apply_surcharge).toBe(3);
        expect(list[0].day_apply_surcharge).toBe('2026-02-09');
    });

    it('no hace nada si el recargo está desactivado', () => {
        const list: Cuota[] = [cuota('2026-02-05')];
        applySurcharge(list, {
            applySurcharge: false,
            surcharge: 10,
            daysApplySurcharge: 3,
            incluirFestivosSurcharge: false,
            incluirDomingosSurcharge: false,
            holidays: [],
        });
        expect(list[0].apply_surcharge).toBe(false);
        expect(list[0].day_apply_surcharge).toBeNull();
    });
});

function sum(list: Cuota[]): number {
    return list.reduce((acc, c) => acc + c.cuota, 0);
}

function cuota(date: string): Cuota {
    return {
        date,
        apply: true,
        festivo: false,
        dom: false,
        cuota: 0,
        date_change: false,
        date_before: null,
        sigla: '',
        textColorCuotas: '',
        diff: 0,
        apply_surcharge: false,
        surcharge: 0,
        days_apply_surcharge: 0,
        day_apply_surcharge: null,
    };
}

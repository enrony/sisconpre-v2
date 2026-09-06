import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { Cuota } from '@/lib/prestamoSchedule';
import { usePrestamosStore } from '@/stores/prestamos';

vi.mock('@inertiajs/vue3', () => ({ router: { put: vi.fn() } }));

function cuota(date: string, apply = true): Cuota {
    return {
        date,
        apply,
        festivo: false,
        dom: false,
        cuota: 60000,
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

describe('cambiarFechaCuota', () => {
    let store: ReturnType<typeof usePrestamosStore>;

    beforeEach(() => {
        setActivePinia(createPinia());
        store = usePrestamosStore();
        store.form.date_first_pay = '2026-03-02';
        store.form.date_last_pay = '2026-03-31';
        store.form.incluir_festivos = false;
        store.form.incluir_domingos = false;
        store.form.list_pays = [cuota('2026-03-05')];
        store.tables.CountryHoliday = [
            { date: '2026-03-09', country_id: 'COL' },
        ] as never;
        store.form.country_id = 'COL';
    });

    it('mueve la fecha dentro del rango y marca date_change', () => {
        const err = store.cambiarFechaCuota(0, '2026-03-10');
        expect(err).toBeNull();
        expect(store.form.list_pays[0].date).toBe('2026-03-10');
        expect(store.form.list_pays[0].date_change).toBe(true);
        expect(store.form.list_pays[0].date_before).toBe('2026-03-05');
    });

    it('rechaza fecha anterior a la primera cuota', () => {
        // 2026-02-27 es viernes (no domingo ni festivo), anterior a first_pay
        expect(store.cambiarFechaCuota(0, '2026-02-27')).toMatch(/anterior/i);
    });

    it('rechaza fecha posterior a la última cuota', () => {
        expect(store.cambiarFechaCuota(0, '2026-04-01')).toMatch(/posterior/i);
    });

    it('rechaza domingo si el préstamo no los incluye', () => {
        // 2026-03-08 es domingo
        expect(store.cambiarFechaCuota(0, '2026-03-08')).toMatch(/domingo/i);
    });

    it('rechaza festivo si el préstamo no los incluye', () => {
        expect(store.cambiarFechaCuota(0, '2026-03-09')).toMatch(/festivo/i);
    });

    it('permite festivo si el préstamo los incluye', () => {
        store.form.incluir_festivos = true;
        expect(store.cambiarFechaCuota(0, '2026-03-09')).toBeNull();
        expect(store.form.list_pays[0].festivo).toBe(true);
    });
});

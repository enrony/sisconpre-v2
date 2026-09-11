import { breakpointsTailwind, useBreakpoints } from '@vueuse/core';
import type { ComputedRef } from 'vue';

/**
 * `true` por debajo del breakpoint `md` de Tailwind (768&nbsp;px).
 *
 * Reactivo (se actualiza al redimensionar / rotar). En cliente devuelve el
 * valor correcto ya en el primer render; en SSR devuelve `false` (asume
 * escritorio) y se corrige al hidratar.
 */
export function useIsMobile(): ComputedRef<boolean> {
    return useBreakpoints(breakpointsTailwind).smaller('md');
}

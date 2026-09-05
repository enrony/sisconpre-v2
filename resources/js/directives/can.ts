import type { Directive } from 'vue';
import { can, canAny } from '@/lib/can';

/**
 * `v-can="'prestamos.editar'"`  o  `v-can="['a', 'b']"` (cualquiera).
 * Si el usuario no tiene el permiso, se elimina el elemento del DOM.
 */
function allowed(value: unknown): boolean {
    if (Array.isArray(value)) {
        return canAny(value as string[]);
    }

    return typeof value === 'string' ? can(value) : true;
}

export const vCan: Directive = {
    mounted(el: HTMLElement, binding) {
        if (!allowed(binding.value)) {
            el.parentNode?.removeChild(el);
        }
    },
};

import { usePage } from '@inertiajs/vue3';

/**
 * Chequeo de permisos en el front, espejo de spatie en el backend.
 * Los permisos y roles llegan por props compartidas (HandleInertiaRequests).
 * El backend SIEMPRE vuelve a validar: esto es solo para mostrar/ocultar UI.
 */

function auth() {
    return usePage().props.auth;
}

export function isSuperAdmin(): boolean {
    return (auth().roles ?? []).includes('super-admin');
}

export function can(permission: string): boolean {
    if (isSuperAdmin()) {
        return true;
    }

    return (auth().permissions ?? []).includes(permission);
}

export function canAny(permissions: string[]): boolean {
    return isSuperAdmin() || permissions.some((p) => can(p));
}

export function canAll(permissions: string[]): boolean {
    return isSuperAdmin() || permissions.every((p) => can(p));
}

export function hasRole(role: string): boolean {
    return (auth().roles ?? []).includes(role);
}

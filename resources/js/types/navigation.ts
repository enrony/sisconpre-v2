import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
};

/** Nodo del menú dinámico servido por el backend (App\Support\Menu). */
export type MenuNode = {
    key: string;
    label: string;
    icon: string | null;
    url: string | null;
    children: MenuNode[];
};

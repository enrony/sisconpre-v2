<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Banknote,
    Boxes,
    Building2,
    CalendarClock,
    CalendarOff,
    ClipboardList,
    CreditCard,
    Dot,
    HandCoins,
    IdCard,
    Landmark,
    LayoutDashboard,
    type LucideIcon,
    MapPinned,
    ReceiptText,
    ShieldCheck,
    Store,
    Tags,
    Users,
    UsersRound,
    WalletCards,
} from '@lucide/vue';
import { computed } from 'vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { MenuNode } from '@/types/navigation';

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

const menu = computed<MenuNode[]>(() => page.props.menu ?? []);

/**
 * Ícono por `key` de `menu_items`. La columna `icon` del backend está vacía
 * (menú heredado), así que el mapa vive acá; el fallback es un punto neutro.
 */
const ICONS: Record<string, LucideIcon> = {
    dashboard: LayoutDashboard,
    clientes: Users,
    prestamos: HandCoins,
    payment_report: ReceiptText,
    cities: Building2,
    departamentos: MapPinned,
    frecuencias: CalendarClock,
    tipo_prestamo: Tags,
    tipo_documentos: IdCard,
    payment_methods: CreditCard,
    payment_forms: Banknote,
    banks: Landmark,
    bank_account_types: WalletCards,
    franquicias: Store,
    festivos: CalendarOff,
    type_payment_record: ClipboardList,
    modules: Boxes,
    grupos_trabajo: UsersRound,
    profile: ShieldCheck,
};

const iconFor = (key: string): LucideIcon => ICONS[key] ?? Dot;

/** `logout` es una acción y ya vive en el menú de usuario (footer). */
const OCULTOS = new Set(['logout']);

/** Hojas navegables (con url) en cualquier profundidad bajo un nodo. */
function leaves(node: MenuNode): MenuNode[] {
    if (node.url && node.children.length === 0) {
        return [node];
    }

    return node.children.flatMap(leaves);
}

const topLeaves = computed(() =>
    menu.value.filter(
        (n) => n.url && n.children.length === 0 && !OCULTOS.has(n.key),
    ),
);

const groups = computed(() =>
    menu.value
        .filter((n) => n.children.length > 0)
        .map((n) => ({
            label: n.label,
            items: leaves(n).filter((i) => !OCULTOS.has(i.key)),
        }))
        .filter((g) => g.items.length > 0),
);
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarMenu>
            <SidebarMenuItem v-for="item in topLeaves" :key="item.key">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.url!)"
                    :tooltip="item.label"
                >
                    <Link :href="item.url!">
                        <component :is="iconFor(item.key)" />
                        <span>{{ item.label }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>

    <template v-for="(group, gi) in groups" :key="group.label">
        <SidebarSeparator v-if="gi > 0 || topLeaves.length > 0" />
        <SidebarGroup class="px-2 py-0">
            <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in group.items" :key="item.key">
                    <SidebarMenuButton
                        as-child
                        :is-active="isCurrentUrl(item.url!)"
                        :tooltip="item.label"
                    >
                        <Link :href="item.url!">
                            <component :is="iconFor(item.key)" />
                            <span>{{ item.label }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    </template>
</template>

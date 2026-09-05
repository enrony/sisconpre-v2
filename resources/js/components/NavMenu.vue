<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { MenuNode } from '@/types/navigation';

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

const menu = computed<MenuNode[]>(() => page.props.menu ?? []);

/** Hojas navegables (con url) en cualquier profundidad bajo un nodo. */
function leaves(node: MenuNode): MenuNode[] {
    if (node.url && node.children.length === 0) {
        return [node];
    }

    return node.children.flatMap(leaves);
}

const topLeaves = computed(() =>
    menu.value.filter((n) => n.url && n.children.length === 0),
);
const groups = computed(() =>
    menu.value
        .filter((n) => n.children.length > 0)
        .map((n) => ({ label: n.label, items: leaves(n) }))
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
                    <Link :href="item.url!">{{ item.label }}</Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>

    <SidebarGroup v-for="group in groups" :key="group.label" class="px-2 py-0">
        <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in group.items" :key="item.key">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.url!)"
                    :tooltip="item.label"
                >
                    <Link :href="item.url!">{{ item.label }}</Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>

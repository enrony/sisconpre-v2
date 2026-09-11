<script setup lang="ts" generic="TRow extends object">
import { useIsMobile } from '@/composables/useIsMobile';

/** Campo mostrado en la tarjeta móvil. */
export interface ListField<T = object> {
    label: string;
    value: (row: T) => unknown;
    /** clases para el `<dd>` (p. ej. `'text-right tabular-nums'`). */
    class?: string;
    /** ocupa las dos columnas del grid. */
    wide?: boolean;
}

const props = withDefaults(
    defineProps<{
        rows: TRow[];
        /** Campos clave→valor de cada tarjeta (móvil). */
        fields: ListField<TRow>[];
        /** Titular de la tarjeta. */
        title: (row: TRow) => string;
        /** Segunda línea del titular (opcional). */
        subtitle?: (row: TRow) => string;
        rowKey?: string;
        loading?: boolean;
        empty?: string;
        /** La tarjeta entera es accionable (emite `row-click`). */
        clickable?: boolean;
    }>(),
    {
        rowKey: 'id',
        empty: 'Sin registros.',
        clickable: false,
        loading: false,
    },
);

const emit = defineEmits<{ (e: 'rowClick', row: TRow): void }>();

const isMobile = useIsMobile();

const keyOf = (row: TRow, i: number): string | number =>
    ((row as Record<string, unknown>)[props.rowKey] as
        | string
        | number
        | undefined) ?? i;

function activate(row: TRow): void {
    if (props.clickable) emit('rowClick', row);
}
</script>

<template>
    <!-- Escritorio: la tabla tal cual la define la página -->
    <div v-if="!isMobile" v-loading="loading">
        <slot name="table" />
        <slot name="pagination" />
    </div>

    <!-- Móvil: lista de tarjetas -->
    <div v-else v-loading="loading">
        <p
            v-if="!rows.length"
            class="border-border text-muted-foreground rounded-lg border border-dashed px-3 py-8 text-center text-sm"
        >
            {{ empty }}
        </p>

        <ul v-else class="space-y-2">
            <li v-for="(row, i) in rows" :key="keyOf(row, i)">
                <component
                    :is="clickable ? 'button' : 'div'"
                    class="border-border bg-card w-full rounded-lg border px-3 py-3 text-left"
                    :class="
                        clickable
                            ? 'hover:border-foreground/25 active:bg-accent focus-visible:outline-ring transition-colors focus-visible:outline-2 focus-visible:outline-offset-2'
                            : ''
                    "
                    :type="clickable ? 'button' : undefined"
                    @click="activate(row)"
                >
                    <slot name="card" :row="row">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p
                                    class="text-foreground truncate text-sm font-semibold"
                                >
                                    <slot name="title" :row="row">{{
                                        title(row)
                                    }}</slot>
                                </p>
                                <p
                                    v-if="subtitle"
                                    class="text-muted-foreground truncate text-xs"
                                >
                                    {{ subtitle(row) }}
                                </p>
                            </div>
                            <slot name="badge" :row="row" />
                        </div>

                        <dl
                            v-if="fields.length"
                            class="mt-2.5 grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs"
                        >
                            <div
                                v-for="f in fields"
                                :key="f.label"
                                class="flex flex-col gap-0.5"
                                :class="f.wide ? 'col-span-2' : ''"
                            >
                                <dt class="text-muted-foreground">
                                    {{ f.label }}
                                </dt>
                                <dd class="text-foreground" :class="f.class">
                                    {{ f.value(row) ?? '—' }}
                                </dd>
                            </div>
                        </dl>

                        <div
                            v-if="$slots.actions"
                            class="border-border mt-3 flex flex-wrap gap-2 border-t pt-2.5"
                            @click.stop
                        >
                            <slot name="actions" :row="row" />
                        </div>
                    </slot>
                </component>
            </li>
        </ul>

        <slot name="pagination" />
    </div>
</template>

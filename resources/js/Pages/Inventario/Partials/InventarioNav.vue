<script setup>
import { Link } from '@inertiajs/vue3';

const tabs = [
    { label: 'Resumen', route: 'inventario.index' },
    { label: 'Movimientos', route: 'inventario.movimientos' },
    { label: 'Kardex', route: 'inventario.kardex' },
    { label: 'Lotes', route: 'inventario.lotes' },
    { label: 'Salidas', route: 'inventario.salidas.index' },
    { label: 'Conteo', route: 'inventario.conteo' },
    { label: 'Valorizacion', route: 'inventario.valorizacion' },
];
</script>

<template>
    <div class="flex flex-wrap gap-2 border-b border-[var(--border-color)] pb-3 mb-6">
        <Link
            v-for="tab in tabs"
            :key="tab.route"
            :href="route(tab.route)"
            class="px-5 py-2.5 text-sm font-bold rounded-xl transition duration-150 cursor-pointer"
            :class="[
                route().current(tab.route)
                    ? 'bg-[var(--accent)] text-[var(--bg-primary)] shadow-md'
                    : 'text-[var(--text-secondary)] hover:bg-[var(--bg-secondary)]/80 hover:text-[var(--text-primary)]'
            ]"
        >
            {{ tab.label }}
        </Link>
    </div>
</template>

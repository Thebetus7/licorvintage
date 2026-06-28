<script setup>
import { Link } from '@inertiajs/vue3';

const tabs = [
    { label: 'Resumen', route: 'inventario.index' },
    { label: 'Movimientos', route: 'inventario.movimientos' },
    { label: 'Kardex', route: 'inventario.kardex' },
    { label: 'Lotes', route: 'inventario.lotes' },
    { label: 'Salidas', route: 'inventario.salidas.index' },
    { label: 'Valorizacion', route: 'inventario.valorizacion' },
];
</script>

<template>
    <nav class="mb-6 flex flex-wrap gap-2">
        <Link
            v-for="tab in tabs"
            :key="tab.route"
            :href="route(tab.route)"
            class="rounded-md px-3 py-2 text-sm font-medium"
            :class="route().current(tab.route) ? 'bg-[#2b1115] text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200'"
        >
            {{ tab.label }}
        </Link>
    </nav>
</template>

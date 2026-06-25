<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioNav from '@/Pages/Inventario/Partials/InventarioNav.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    movimientos: Array,
    productos: Array,
    filters: Object,
});

const applyFilters = (event) => {
    event.preventDefault();
    const form = event.target;
    router.get(route('inventario.kardex'), {
        producto_id: form.producto_id.value || undefined,
        desde: form.desde.value || undefined,
        hasta: form.hasta.value || undefined,
    }, { preserveState: true });
};

const esIngreso = (tipo) => tipo.startsWith('ingreso_');
</script>

<template>
    <AppLayout title="Kardex">
        <template #header>
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Kardex valorizado</h1>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <InventarioNav />

            <form class="mb-4 grid gap-3 rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 p-4 sm:grid-cols-4 text-[var(--text-primary)] transition-colors duration-300" @submit="applyFilters">
                <div>
                    <InputLabel value="Producto" />
                    <select name="producto_id" class="mt-1 w-full rounded-md bg-[var(--bg-secondary)] border border-[var(--border-color)] text-[var(--text-primary)] focus:ring-[var(--accent)] focus:border-[var(--accent)] py-2 px-3 focus:outline-none transition-colors duration-300" required>
                        <option value="" class="bg-[#1c0e11] text-stone-200">Seleccionar...</option>
                        <option v-for="p in productos" :key="p.id" :value="p.id" :selected="filters.producto_id == p.id" class="bg-[#1c0e11] text-stone-200">{{ p.nombre }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Desde" />
                    <TextInput name="desde" type="date" :value="filters.desde || ''" class="mt-1 w-full" />
                </div>
                <div>
                    <InputLabel value="Hasta" />
                    <TextInput name="hasta" type="date" :value="filters.hasta || ''" class="mt-1 w-full" />
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full justify-center px-4 py-2 bg-[var(--bg-secondary)] border border-[var(--border-color)] text-[var(--accent)] hover:bg-[var(--accent)]/10 hover:text-[var(--text-primary)] rounded-md font-semibold text-sm transition cursor-pointer">
                        Consultar
                    </button>
                </div>
            </form>

            <div v-if="movimientos.length" class="overflow-hidden rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 shadow-sm transition-colors duration-300">
                <table class="min-w-full divide-y divide-[var(--border-color)] text-sm text-[var(--text-secondary)]">
                    <thead class="bg-[var(--bg-secondary)]/50 text-left text-xs uppercase text-[var(--accent)]">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Concepto</th>
                            <th class="px-4 py-3">Ingreso</th>
                            <th class="px-4 py-3">Salida</th>
                            <th class="px-4 py-3">Saldo qty</th>
                            <th class="px-4 py-3">Saldo costo prom.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-color)]">
                        <tr v-for="mov in movimientos" :key="mov.id" class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3 text-xs font-mono">{{ new Date(mov.created_at).toLocaleString() }}</td>
                            <td class="px-4 py-3 font-semibold text-[var(--text-primary)] capitalize">{{ mov.tipo.replace(/_/g, ' ') }}</td>
                            <td class="px-4 py-3">
                                <span v-if="esIngreso(mov.tipo)">{{ mov.cantidad }} / Bs {{ Number(mov.costo_unitario).toFixed(2) }}</span>
                                <span v-else class="text-stone-600">-</span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="!esIngreso(mov.tipo)">{{ mov.cantidad }} / Bs {{ Number(mov.costo_unitario).toFixed(2) }}</span>
                                <span v-else class="text-stone-600">-</span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-[var(--text-primary)]">{{ mov.saldo_cantidad }}</td>
                            <td class="px-4 py-3 text-[var(--text-primary)]">Bs {{ Number(mov.saldo_costo_promedio).toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 p-8 text-center text-[var(--text-secondary)] backdrop-blur-sm transition-colors duration-300">
                Selecciona un producto para ver su kardex.
            </div>
        </div>
    </AppLayout>
</template>

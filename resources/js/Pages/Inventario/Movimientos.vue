<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioNav from '@/Pages/Inventario/Partials/InventarioNav.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import ReportModal from '@/Components/ReportModal.vue';

const props = defineProps({
    movimientos: Object,
    productos: Array,
    filters: Object,
    tipos: Array,
});

const applyFilters = (event) => {
    event.preventDefault();
    const form = event.target;
    router.get(route('inventario.movimientos'), {
        producto_id: form.producto_id.value || undefined,
        tipo: form.tipo.value || undefined,
        desde: form.desde.value || undefined,
        hasta: form.hasta.value || undefined,
    }, { preserveState: true });
};

const tipoLabel = (tipo) => tipo.replace(/_/g, ' ');
</script>

<template>
    <AppLayout title="Movimientos de Inventario">
        <template #header>
            <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Movimientos de Inventario</h1>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <InventarioNav />

            <form class="mb-4 grid gap-3 rounded-xl border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 p-4 sm:grid-cols-5 text-[var(--text-primary)] transition-colors duration-300" @submit="applyFilters">
                <div>
                    <InputLabel value="Producto" />
                    <select name="producto_id" class="mt-1 w-full rounded-xl bg-[var(--bg-secondary)] border border-[var(--border-color)] text-[var(--text-primary)] focus:ring-[var(--accent)] focus:border-[var(--accent)] py-2 px-3 focus:outline-none transition-colors duration-300">
                        <option value="" class="bg-[var(--bg-secondary)] text-[var(--text-primary)]">Todos</option>
                        <option v-for="p in productos" :key="p.id" :value="p.id" :selected="filters.producto_id == p.id" class="bg-[var(--bg-secondary)] text-[var(--text-primary)]">{{ p.nombre }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Tipo" />
                    <select name="tipo" class="mt-1 w-full rounded-xl bg-[var(--bg-secondary)] border border-[var(--border-color)] text-[var(--text-primary)] focus:ring-[var(--accent)] focus:border-[var(--accent)] py-2 px-3 focus:outline-none transition-colors duration-300">
                        <option value="" class="bg-[var(--bg-secondary)] text-[var(--text-primary)]">Todos</option>
                        <option v-for="tipo in tipos" :key="tipo" :value="tipo" :selected="filters.tipo === tipo" class="bg-[var(--bg-secondary)] text-[var(--text-primary)]">{{ tipoLabel(tipo) }}</option>
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
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 justify-center px-4 py-2 bg-[var(--bg-secondary)] border border-[var(--border-color)] text-[var(--accent)] hover:bg-[var(--accent)]/10 hover:text-[var(--text-primary)] rounded-xl font-semibold text-sm transition cursor-pointer">
                        Filtrar
                    </button>
                    <ReportModal module="inventario_movimientos" :filters="filters" />
                </div>
            </form>

            <div class="overflow-hidden rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 shadow-sm transition-colors duration-300">
                <table class="min-w-full divide-y divide-[var(--border-color)] text-sm text-[var(--text-secondary)]">
                    <thead class="bg-[var(--bg-secondary)]/50 text-left text-xs uppercase text-[var(--accent)]">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Producto</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Cantidad</th>
                            <th class="px-4 py-3">Costo u.</th>
                            <th class="px-4 py-3">Saldo</th>
                            <th class="px-4 py-3">Usuario</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-color)]">
                        <tr v-for="mov in movimientos.data" :key="mov.id" class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3 text-xs font-mono">{{ new Date(mov.created_at).toLocaleString() }}</td>
                            <td class="px-4 py-3 font-semibold text-[var(--text-primary)]">{{ mov.producto?.nombre }}</td>
                            <td class="px-4 py-3 capitalize">{{ tipoLabel(mov.tipo) }}</td>
                            <td class="px-4 py-3 font-semibold text-[var(--text-primary)]">{{ mov.cantidad }}</td>
                            <td class="px-4 py-3">Bs {{ Number(mov.costo_unitario).toFixed(2) }}</td>
                            <td class="px-4 py-3 font-semibold text-[var(--text-primary)]">{{ mov.saldo_cantidad }}</td>
                            <td class="px-4 py-3 text-xs">{{ mov.user?.name || 'Sistema' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

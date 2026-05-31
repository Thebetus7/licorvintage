<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioNav from '@/Pages/Inventario/Partials/InventarioNav.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

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
    <AppLayout title="Movimientos de inventario">
        <template #header>
            <h1 class="text-2xl font-semibold text-[#2b1115]">Movimientos de inventario</h1>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <InventarioNav />

            <form class="mb-4 grid gap-3 rounded-lg border bg-white p-4 sm:grid-cols-5" @submit="applyFilters">
                <div>
                    <InputLabel value="Producto" />
                    <select name="producto_id" class="mt-1 w-full rounded-md border-stone-300">
                        <option value="">Todos</option>
                        <option v-for="p in productos" :key="p.id" :value="p.id" :selected="filters.producto_id == p.id">{{ p.nombre }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Tipo" />
                    <select name="tipo" class="mt-1 w-full rounded-md border-stone-300">
                        <option value="">Todos</option>
                        <option v-for="tipo in tipos" :key="tipo" :value="tipo" :selected="filters.tipo === tipo">{{ tipoLabel(tipo) }}</option>
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
                    <SecondaryButton type="submit" class="w-full justify-center">Filtrar</SecondaryButton>
                </div>
            </form>

            <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
                <table class="min-w-full divide-y divide-stone-200 text-sm">
                    <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500">
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
                    <tbody class="divide-y divide-stone-100">
                        <tr v-for="mov in movimientos.data" :key="mov.id">
                            <td class="px-4 py-3">{{ new Date(mov.created_at).toLocaleString() }}</td>
                            <td class="px-4 py-3">{{ mov.producto?.nombre }}</td>
                            <td class="px-4 py-3 capitalize">{{ tipoLabel(mov.tipo) }}</td>
                            <td class="px-4 py-3">{{ mov.cantidad }}</td>
                            <td class="px-4 py-3">Bs {{ Number(mov.costo_unitario).toFixed(2) }}</td>
                            <td class="px-4 py-3">{{ mov.saldo_cantidad }}</td>
                            <td class="px-4 py-3">{{ mov.user?.name }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioNav from '@/Pages/Inventario/Partials/InventarioNav.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    productos: Array,
});

const conteos = reactive(
    props.productos.reduce((acc, producto) => {
        acc[producto.id] = producto.stock_actual?.stock ?? 0;
        return acc;
    }, {}),
);

const form = useForm({
    conteos: [],
});

const submit = () => {
    form.conteos = props.productos.map((producto) => ({
        producto_id: producto.id,
        stock_fisico: Number(conteos[producto.id] ?? 0),
    }));

    form.post(route('inventario.conteo.store'), { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Conteo fisico">
        <template #header>
            <div>
                <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Conteo físico</h1>
                <p class="text-sm text-[var(--text-secondary)]">Reconcilia stock físico vs sistema y genera ajustes automáticos.</p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <InventarioNav />

            <form @submit.prevent="submit">
                <div class="overflow-hidden rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 shadow-sm transition-colors duration-300">
                    <table class="min-w-full divide-y divide-[var(--border-color)] text-sm text-[var(--text-secondary)]">
                        <thead class="bg-[var(--bg-secondary)]/50 text-left text-xs uppercase text-[var(--accent)]">
                            <tr>
                                <th class="px-4 py-3">Producto</th>
                                <th class="px-4 py-3">Stock sistema</th>
                                <th class="px-4 py-3">Stock físico</th>
                                <th class="px-4 py-3">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--border-color)]">
                            <tr v-for="producto in productos" :key="producto.id" class="hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3 font-semibold text-[var(--text-primary)]">{{ producto.nombre }}</td>
                                <td class="px-4 py-3 text-[var(--text-primary)]">{{ producto.stock_actual?.stock ?? 0 }}</td>
                                <td class="px-4 py-3">
                                    <TextInput v-model="conteos[producto.id]" type="number" min="0" class="w-28" />
                                </td>
                                <td class="px-4 py-3" :class="(conteos[producto.id] ?? 0) - (producto.stock_actual?.stock ?? 0) !== 0 ? 'font-semibold text-[var(--accent)]' : ''">
                                    {{ (conteos[producto.id] ?? 0) - (producto.stock_actual?.stock ?? 0) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <PrimaryButton :disabled="form.processing" class="font-semibold">Guardar conteo y aplicar ajustes</PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

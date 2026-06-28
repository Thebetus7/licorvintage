<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioNav from '@/Pages/Inventario/Partials/InventarioNav.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    productos: Array,
});

const search = ref('');
const physicalCounts = ref({}); // Map of product_id -> physical_count (string/number)
const generalMotive = ref('Conteo físico de inventario');

const filteredProducts = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return props.productos;
    return props.productos.filter(p => 
        p.nombre.toLowerCase().includes(term) || 
        (p.codigo_barra && p.codigo_barra.includes(term))
    );
});

// Helper to calculate difference
const getDifference = (product) => {
    const physical = physicalCounts.value[product.id];
    if (physical === undefined || physical === '') return null;
    const system = product.stock_actual?.stock ?? 0;
    return parseInt(physical) - system;
};

// Form submission
const form = useForm({
    motivo: '',
    conteos: [],
});

const submitAdjustment = () => {
    // Build the array of counts only for products that have been counted
    const conteosData = Object.entries(physicalCounts.value)
        .filter(([_, val]) => val !== '' && val !== undefined)
        .map(([prodId, val]) => ({
            producto_id: parseInt(prodId),
            stock_fisico: parseInt(val),
        }));

    if (conteosData.length === 0) {
        alert('Debe ingresar al menos un conteo físico antes de guardar.');
        return;
    }

    form.motivo = generalMotive.value;
    form.conteos = conteosData;

    form.post(route('inventario.conteo.store'), {
        preserveScroll: true,
        onSuccess: () => {
            physicalCounts.value = {};
            generalMotive.value = 'Conteo físico de inventario';
            alert('Ajustes de inventario aplicados correctamente.');
        },
    });
};

const prefillAll = () => {
    if (confirm('¿Está seguro de pre-llenar todos los campos con el stock actual del sistema? Esto sobrescribirá los conteos actuales.')) {
        props.productos.forEach(p => {
            physicalCounts.value[p.id] = p.stock_actual?.stock ?? 0;
        });
    }
};

const clearAll = () => {
    if (confirm('¿Está seguro de limpiar todos los conteos ingresados?')) {
        physicalCounts.value = {};
    }
};
</script>

<template>
    <AppLayout title="Conteo Físico">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Conteo y Ajuste de Inventario</h1>
                    <p class="text-sm text-[var(--text-secondary)]">Auditoría física de almacén. Permite conciliar el stock físico real con el registrado por el sistema.</p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <InventarioNav />

            <!-- Acciones y Filtros -->
            <div class="grid gap-4 md:grid-cols-3 mb-6 items-end bg-[var(--bg-secondary)]/30 border border-[var(--border-color)] p-4 rounded-xl backdrop-blur-md">
                <div>
                    <label for="search_prod" class="block text-xs font-semibold text-stone-300">Buscar Producto</label>
                    <input
                        id="search_prod"
                        v-model="search"
                        type="text"
                        placeholder="Nombre o código de barra..."
                        class="mt-1 block w-full rounded-md border-stone-700 bg-stone-900 text-stone-100 placeholder-stone-500 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm px-3 py-2"
                    />
                </div>
                <div>
                    <label for="general_motive" class="block text-xs font-semibold text-stone-300">Motivo del Ajuste (General)</label>
                    <input
                        id="general_motive"
                        v-model="generalMotive"
                        type="text"
                        placeholder="Ej: Auditoría mensual..."
                        class="mt-1 block w-full rounded-md border-stone-700 bg-stone-900 text-stone-100 placeholder-stone-500 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm px-3 py-2"
                    />
                </div>
                <div class="flex gap-2 justify-end">
                    <button 
                        type="button" 
                        @click="prefillAll" 
                        class="px-3 py-2 text-xs bg-stone-800 border border-stone-700 text-stone-300 hover:bg-stone-700 transition rounded-md font-semibold cursor-pointer"
                    >
                        Pre-llenar con Sistema
                    </button>
                    <button 
                        type="button" 
                        @click="clearAll" 
                        class="px-3 py-2 text-xs bg-stone-800 border border-stone-700 text-rose-400 hover:bg-rose-950/20 transition rounded-md font-semibold cursor-pointer"
                    >
                        Limpiar Conteos
                    </button>
                </div>
            </div>

            <!-- Tabla de Conteo -->
            <div class="overflow-hidden rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 shadow-sm mb-6">
                <table class="min-w-full divide-y divide-[var(--border-color)] text-sm text-[var(--text-secondary)]">
                    <thead class="bg-[var(--bg-secondary)]/50 text-left text-xs uppercase text-[var(--accent)] font-semibold">
                        <tr>
                            <th class="px-6 py-3">Código / Producto</th>
                            <th class="px-6 py-3 text-center">Stock Sistema</th>
                            <th class="px-6 py-3 text-center w-40">Stock Físico Real</th>
                            <th class="px-6 py-3 text-center">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-color)]">
                        <tr v-if="filteredProducts.length === 0">
                            <td colspan="4" class="px-6 py-10 text-center text-[var(--text-secondary)]">
                                No se encontraron productos coincidentes.
                            </td>
                        </tr>
                        <tr v-for="prod in filteredProducts" :key="prod.id" class="hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-[var(--text-primary)]">{{ prod.nombre }}</div>
                                <div class="text-xs text-stone-400 font-mono">{{ prod.codigo_barra || 'Sin código' }}</div>
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-stone-200">
                                {{ prod.stock_actual?.stock ?? 0 }}
                            </td>
                            <td class="px-6 py-4">
                                <input
                                    v-model="physicalCounts[prod.id]"
                                    type="number"
                                    min="0"
                                    placeholder="Sin contar"
                                    class="w-full text-center rounded-md border-stone-700 bg-stone-900 text-stone-100 placeholder-stone-600 focus:border-amber-500 focus:ring-amber-500 text-sm px-2 py-1.5"
                                />
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span v-if="getDifference(prod) === null" class="text-stone-500">—</span>
                                <span v-else-if="getDifference(prod) > 0" class="inline-block text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    +{{ getDifference(prod) }} (Sobrante)
                                </span>
                                <span v-else-if="getDifference(prod) < 0" class="inline-block text-xs font-bold px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                    {{ getDifference(prod) }} (Faltante)
                                </span>
                                <span v-else class="inline-block text-xs font-bold px-2.5 py-1 rounded-full bg-stone-500/10 text-stone-400 border border-stone-500/20">
                                    Sin diferencia
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Botón de Envío -->
            <div class="flex justify-end mb-12">
                <PrimaryButton 
                    @click="submitAdjustment"
                    :disabled="form.processing"
                    class="px-6 py-3 text-sm font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-lg rounded-xl transition cursor-pointer"
                >
                    {{ form.processing ? 'Aplicando Ajustes...' : 'Guardar Conteo y Aplicar Ajustes' }}
                </PrimaryButton>
            </div>
            <InputError :message="form.errors.conteos" class="mt-2 text-right" />
        </div>
    </AppLayout>
</template>

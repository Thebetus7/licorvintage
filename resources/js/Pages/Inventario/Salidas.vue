<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioNav from '@/Pages/Inventario/Partials/InventarioNav.vue';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    salidas: Object,
    tiposSalida: Array,
    productos: Array,
});

const showForm = ref(false);
const search = ref('');
const expandedNotaId = ref(null);

const form = useForm({
    tipo_salida_id: '',
    fecha: new Date().toISOString().split('T')[0],
    detalles: [],
});

const toggleExpand = (id) => {
    expandedNotaId.value = expandedNotaId.value === id ? null : id;
};

const filteredProducts = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return [];
    return props.productos.filter(p => p.nombre.toLowerCase().includes(term));
});

const addProducto = (producto) => {
    const exists = form.detalles.some(d => d.producto_id === producto.id);
    if (exists) return;
    form.detalles.push({
        producto_id: producto.id,
        nombre: producto.nombre,
        cantidad: 1,
    });
    search.value = '';
};

const removeDetalle = (index) => {
    form.detalles.splice(index, 1);
};

const submit = () => {
    form.post(route('inventario.salidas.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
            form.reset();
            form.detalles = [];
        }
    });
};
</script>

<template>
    <AppLayout title="Notas de Salida">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Notas de Salida</h1>
                    <p class="text-sm text-[var(--text-secondary)]">Registro de bajas de inventario por consumo de personal, roturas o vencimientos.</p>
                </div>
                <div>
                    <button @click="showForm = true" class="px-4 py-2 bg-[var(--bg-secondary)] border border-[var(--border-color)] text-[var(--accent)] hover:bg-[var(--accent)]/10 hover:text-[var(--text-primary)] rounded-md font-semibold text-sm transition cursor-pointer">
                        Nueva Nota de Salida
                    </button>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <InventarioNav />

            <!-- Listado de Notas de Salida -->
            <div class="overflow-hidden rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 shadow-sm transition-colors duration-300">
                <table class="min-w-full divide-y divide-[var(--border-color)] text-sm text-[var(--text-secondary)]">
                    <thead class="bg-[var(--bg-secondary)]/50 text-left text-xs uppercase text-[var(--accent)] font-semibold">
                        <tr>
                            <th class="px-6 py-3">Código</th>
                            <th class="px-6 py-3">Tipo de Salida</th>
                            <th class="px-6 py-3">Fecha</th>
                            <th class="px-6 py-3">Registrado Por</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-color)]">
                        <tr v-if="salidas.data.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-[var(--text-secondary)]">
                                No se encontraron notas de salida registradas.
                            </td>
                        </tr>
                        <template v-for="nota in salidas.data" :key="nota.id">
                            <tr class="hover:bg-white/5 transition-colors cursor-pointer" @click="toggleExpand(nota.id)">
                                <td class="px-6 py-4 font-mono text-xs font-bold text-[var(--text-primary)]">
                                    {{ nota.codigo_nota }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border bg-amber-50 dark:bg-amber-500/10 text-amber-800 dark:text-amber-400 border-amber-200 dark:border-amber-500/20">
                                        {{ nota.tipo_salida?.nombre }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    {{ new Date(nota.fecha).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ nota.user?.name }}
                                </td>
                                <td class="px-6 py-4 text-right text-xs text-[var(--accent)] font-semibold">
                                    {{ expandedNotaId === nota.id ? 'Ocultar Detalle' : 'Ver Detalle' }}
                                </td>
                            </tr>
                            <!-- Fila de Detalle Expandido -->
                            <tr v-if="expandedNotaId === nota.id" class="bg-black/20">
                                <td colspan="5" class="px-8 py-4">
                                    <div class="rounded-md border border-stone-800 p-4 bg-stone-900/40">
                                        <h4 class="text-xs font-bold uppercase text-amber-500 mb-3">Productos dados de baja:</h4>
                                        <div class="grid gap-2 text-xs">
                                            <div v-for="det in nota.detalle_salidas" :key="det.id" class="flex items-center justify-between border-b border-stone-800/60 pb-2 last:border-b-0 last:pb-0 text-stone-200">
                                                <span>
                                                    <span class="font-bold text-[var(--text-primary)]">{{ det.producto?.nombre }}</span>
                                                    <span v-if="det.lote" class="ms-2 text-[10px] font-mono text-stone-400 bg-stone-800 px-1.5 py-0.5 rounded">
                                                        Lote: {{ det.lote?.codigo_lote }}
                                                    </span>
                                                </span>
                                                <span class="font-semibold text-amber-400">{{ det.cantidad }} unidades</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="mt-6 flex items-center justify-between mb-8">
                <div class="text-sm text-[var(--text-secondary)]">
                    Mostrando {{ salidas.from || 0 }} al {{ salidas.to || 0 }} de {{ salidas.total }} notas
                </div>
                <div class="flex gap-1">
                    <Link v-for="link in salidas.links" 
                          :key="link.label"
                          :href="link.url || '#'" 
                          class="px-3 py-1.5 text-xs rounded border transition duration-150"
                          :class="[
                              link.active ? 'bg-[var(--accent)] text-white border-[var(--accent)]' : 'bg-[var(--bg-secondary)] text-[var(--text-primary)] border-[var(--border-color)] hover:bg-white/5',
                              !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                          ]"
                          v-html="link.label">
                    </Link>
                </div>
            </div>
        </div>

        <!-- Modal Nueva Nota de Salida -->
        <DialogModal :show="showForm" max-width="lg" scrollable @close="showForm = false">
            <template #title>
                <span class="text-stone-100 font-bold">Nueva Nota de Salida</span>
            </template>

            <template #content>
                <div class="space-y-5">
                    <!-- Tipo de Salida -->
                    <section class="space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-amber-500">Motivo de Salida</h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="tipo_salida" class="block text-xs font-semibold text-stone-300">Tipo de Salida</label>
                                <select
                                    id="tipo_salida"
                                    v-model="form.tipo_salida_id"
                                    class="mt-1 block w-full rounded-md border-stone-700 bg-stone-900 text-stone-100 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm px-3 py-2"
                                >
                                    <option value="" disabled>Seleccione el tipo...</option>
                                    <option v-for="tipo in tiposSalida" :key="tipo.id" :value="tipo.id">
                                        {{ tipo.nombre }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.tipo_salida_id" class="mt-1" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-stone-300">Fecha</label>
                                <input
                                    v-model="form.fecha"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-stone-700 bg-stone-900 text-stone-100 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm px-3 py-2"
                                />
                                <InputError :message="form.errors.fecha" class="mt-1" />
                            </div>
                        </div>
                    </section>

                    <!-- Buscador de Productos -->
                    <section class="space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-amber-500">Buscar Producto</h3>
                        <div>
                            <label for="product_search" class="block text-xs font-semibold text-stone-300">Nombre o código de barra</label>
                            <input
                                id="product_search"
                                v-model="search"
                                type="text"
                                class="mt-1 block w-full rounded-md border-stone-700 bg-stone-900 text-stone-100 placeholder-stone-500 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm px-3 py-2"
                                placeholder="Escribe para buscar..."
                            />
                        </div>
                        <div
                            v-if="filteredProducts.length > 0"
                            class="max-h-44 overflow-y-auto rounded-md border border-stone-700 bg-stone-900"
                        >
                            <button
                                v-for="producto in filteredProducts"
                                :key="producto.id"
                                type="button"
                                class="flex w-full justify-between border-b border-stone-800 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-stone-800 text-stone-200"
                                @click="addProducto(producto)"
                            >
                                <span>{{ producto.nombre }}</span>
                                <span class="text-stone-400">ID: {{ producto.id }}</span>
                            </button>
                        </div>
                    </section>

                    <!-- Lista de Salida -->
                    <section class="space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-amber-500">Productos Seleccionados</h3>

                        <p v-if="!form.detalles.length" class="rounded-md border border-dashed border-stone-700 px-4 py-6 text-center text-sm text-stone-400 bg-stone-900/40">
                            Agrega productos utilizando el buscador de arriba.
                        </p>

                        <div v-else class="space-y-3">
                            <div
                                v-for="(detalle, index) in form.detalles"
                                :key="index"
                                class="rounded-md border border-stone-700 bg-stone-900/40 p-4"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0 font-bold text-stone-100 text-sm">
                                        {{ detalle.nombre }}
                                    </div>
                                    <button
                                        type="button"
                                        class="shrink-0 text-xs text-rose-400 hover:text-rose-300 font-semibold"
                                        @click="removeDetalle(index)"
                                    >
                                        Quitar
                                    </button>
                                </div>
                                <div class="mt-3 w-32">
                                    <label class="block text-xs font-semibold text-stone-300">Cantidad a descargar</label>
                                    <input
                                        v-model="detalle.cantidad"
                                        type="number"
                                        min="1"
                                        class="mt-1 block w-full rounded-md border-stone-700 bg-stone-900 text-stone-100 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm px-3 py-2"
                                    />
                                    <InputError :message="form.errors[`detalles.${index}.cantidad`]" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <InputError :message="form.errors.detalles" class="mt-1" />
                    </section>
                </div>
            </template>

            <template #footer>
                <div class="flex justify-end gap-2 w-full">
                    <SecondaryButton @click="showForm = false">Cancelar</SecondaryButton>
                    <PrimaryButton
                        :disabled="form.processing || !form.detalles.length"
                        @click="submit"
                    >
                        Registrar Salida
                    </PrimaryButton>
                </div>
            </template>
        </DialogModal>

    </AppLayout>
</template>

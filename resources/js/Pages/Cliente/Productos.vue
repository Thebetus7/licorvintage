<script setup>
import { reactive } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    productos: Array,
});

const quantities = reactive({});

const quantity = (producto) => quantities[producto.id] || 1;
const maxStock = (producto) => producto.stock_actual?.stock || 0;

const increase = (producto) => {
    quantities[producto.id] = Math.min(quantity(producto) + 1, maxStock(producto));
};

const decrease = (producto) => {
    quantities[producto.id] = Math.max(quantity(producto) - 1, 1);
};
</script>

<template>
    <AppLayout title="Catalogo">
        <template #header>
            <div>
                <h1 class="text-2xl font-semibold text-[#2b1115]">Catalogo de Bebidas</h1>
                <p class="text-sm text-stone-500">Productos disponibles segun stock actual.</p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <article v-for="producto in productos" :key="producto.id" class="overflow-hidden rounded-lg border bg-white shadow-sm">
                    <div class="aspect-[4/3] bg-[#2b1115]">
                        <img v-if="producto.fotos?.length" :src="producto.fotos[0]" :alt="producto.nombre" class="h-full w-full object-cover">
                        <img v-else-if="producto.imagen" :src="producto.imagen" :alt="producto.nombre" class="h-full w-full object-cover">
                        <div v-else class="flex h-full items-center justify-center text-lg font-semibold text-amber-200">{{ producto.nombre }}</div>
                    </div>
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="font-semibold text-[#2b1115]">{{ producto.nombre }}</h2>
                                <p class="text-sm text-stone-500">{{ producto.mililitros }} ml</p>
                            </div>
                            <div class="text-right font-bold text-amber-700">Bs {{ Number(producto.precio_venta).toFixed(2) }}</div>
                        </div>
                        <p class="mt-3 min-h-10 text-sm text-stone-600">{{ producto.descripcion || 'Bebida disponible en tienda.' }}</p>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-sm text-stone-500">Stock {{ maxStock(producto) }}</span>
                            <div class="flex items-center gap-2">
                                <SecondaryButton @click="decrease(producto)">-</SecondaryButton>
                                <span class="w-8 text-center font-semibold">{{ quantity(producto) }}</span>
                                <SecondaryButton :disabled="quantity(producto) >= maxStock(producto)" @click="increase(producto)">+</SecondaryButton>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductosPublicados from './ProductosPublicados.vue';

const props = defineProps({
    productos: Array,
});
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
            <ProductosPublicados :productos="productos" />
        </div>
    </AppLayout>
</template>

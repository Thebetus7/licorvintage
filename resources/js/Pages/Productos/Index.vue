<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProductoFormModal from '@/Pages/Productos/Partials/ProductoFormModal.vue';
import ProductoList from '@/Pages/Productos/Partials/ProductoList.vue';

defineProps({
    productos: Object,
});

const showModal = ref(false);
const editingProduct = ref(null);

const openCreate = () => {
    editingProduct.value = null;
    showModal.value = true;
};

const openEdit = (producto) => {
    editingProduct.value = producto;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingProduct.value = null;
};
</script>

<template>
    <AppLayout title="Productos">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-[#2b1115]">Productos</h1>
                    <p class="text-sm text-stone-500">Catalogo, codigos de barra, fotos y precios de venta.</p>
                </div>
                <PrimaryButton @click="openCreate">Crear Producto</PrimaryButton>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <ProductoList :productos="productos" @edit="openEdit" />
        </div>

        <ProductoFormModal
            :show="showModal"
            :producto="editingProduct"
            @close="closeModal"
        />
    </AppLayout>
</template>

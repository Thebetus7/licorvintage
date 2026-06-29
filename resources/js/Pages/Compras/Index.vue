<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import CompraFormModal from '@/Pages/Compras/Partials/CompraFormModal.vue';
import CompraList from '@/Pages/Compras/Partials/CompraList.vue';
import ProveedorFormModal from '@/Pages/Compras/Partials/ProveedorFormModal.vue';
import ProveedorPanel from '@/Pages/Compras/Partials/ProveedorPanel.vue';
import ReportModal from '@/Components/ReportModal.vue';

defineProps({
    compras: Object,
    productos: Array,
    proveedores: Array,
});

const showCompraModal = ref(false);
const showProveedorModal = ref(false);
const editingCompra = ref(null);
const editingProveedor = ref(null);

const openCompra = () => {
    editingCompra.value = null;
    showCompraModal.value = true;
};

const editCompra = (compra) => {
    editingCompra.value = compra;
    showCompraModal.value = true;
};

const closeCompraModal = () => {
    showCompraModal.value = false;
    editingCompra.value = null;
};

const openProveedor = () => {
    editingProveedor.value = null;
    showProveedorModal.value = true;
};

const editProveedor = (proveedor) => {
    editingProveedor.value = proveedor;
    showProveedorModal.value = true;
};

const closeProveedorModal = () => {
    showProveedorModal.value = false;
    editingProveedor.value = null;
};

const deleteProveedor = (proveedor) => {
    if (confirm(`Eliminar proveedor ${proveedor.nombre}?`)) {
        router.delete(route('proveedores.destroy', proveedor.id), { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout title="Compras">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Compras</h1>
                    <p class="text-sm text-[var(--text-secondary)]">Compras con multiples productos y proveedor opcional.</p>
                </div>
                <div class="flex gap-2">
                    <ReportModal module="compras" :proveedores="proveedores" />
                    <SecondaryButton @click="openProveedor">Proveedores</SecondaryButton>
                    <PrimaryButton @click="openCompra">Nueva Compra</PrimaryButton>
                </div>
            </div>
        </template>

        <div class="mx-auto grid max-w-7xl gap-6 px-4 lg:grid-cols-[1fr_320px] sm:px-6 lg:px-8">
            <CompraList :compras="compras" @edit="editCompra" />

            <ProveedorPanel
                :proveedores="proveedores"
                @create="openProveedor"
                @edit="editProveedor"
                @delete="deleteProveedor"
            />
        </div>

        <CompraFormModal
            :show="showCompraModal"
            :compra="editingCompra"
            :productos="productos"
            :proveedores="proveedores"
            @close="closeCompraModal"
        />

        <ProveedorFormModal
            :show="showProveedorModal"
            :proveedor="editingProveedor"
            @close="closeProveedorModal"
        />
    </AppLayout>
</template>

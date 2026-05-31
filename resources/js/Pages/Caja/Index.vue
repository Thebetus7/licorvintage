<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    cajaActiva: Object,
    productos: Array,
});

const openForm = useForm({ monto_inicial: 0 });
const closeForm = useForm({ monto_real: 0 });
const saleForm = useForm({
    tipo_pago: 'efectivo',
    monto_pagado: 0,
    detalles: [],
});

const addProduct = (producto) => {
    const existing = saleForm.detalles.find((item) => item.producto_id === producto.id);
    const stock = producto.stock_actual?.stock || 0;

    if (existing) {
        existing.cantidad = Math.min(Number(existing.cantidad) + 1, stock);
        return;
    }

    saleForm.detalles.push({ producto_id: producto.id, cantidad: 1 });
};

const producto = (id) => props.productos.find((item) => item.id === Number(id));

const total = computed(() => saleForm.detalles.reduce((sum, item) => {
    const selected = producto(item.producto_id);
    return sum + (Number(selected?.precio_venta || 0) * Number(item.cantidad || 0));
}, 0));

const submitSale = () => {
    saleForm.monto_pagado = saleForm.monto_pagado || total.value;
    saleForm.post(route('ventas.store'), {
        preserveScroll: true,
        onSuccess: () => {
            saleForm.detalles = [];
            saleForm.monto_pagado = 0;
        },
    });
};
</script>

<template>
    <AppLayout title="Caja">
        <template #header>
            <div>
                <h1 class="text-2xl font-semibold text-[#2b1115]">Caja</h1>
                <p class="text-sm text-stone-500">Apertura, venta y cierre de caja.</p>
            </div>
        </template>

        <div class="mx-auto grid max-w-7xl gap-6 px-4 lg:grid-cols-[320px_1fr] sm:px-6 lg:px-8">
            <section class="rounded-lg border bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-[#2b1115]">Estado de caja</h2>
                <div v-if="cajaActiva" class="mt-4 space-y-2 text-sm">
                    <div class="rounded-md bg-emerald-50 px-3 py-2 text-emerald-800">Caja abierta #{{ cajaActiva.id }}</div>
                    <div>Monto inicial: Bs {{ Number(cajaActiva.monto_inicial).toFixed(2) }}</div>
                    <div>Monto sistema: Bs {{ Number(cajaActiva.monto_sistema).toFixed(2) }}</div>
                    <div class="pt-3">
                        <InputLabel value="Monto real" />
                        <TextInput v-model="closeForm.monto_real" type="number" step="0.01" class="mt-1 w-full" />
                        <SecondaryButton class="mt-3 w-full justify-center" @click="closeForm.put(route('caja.close', cajaActiva.id))">Cerrar caja</SecondaryButton>
                    </div>
                </div>
                <form v-else class="mt-4" @submit.prevent="openForm.post(route('caja.open'))">
                    <InputLabel value="Monto inicial" />
                    <TextInput v-model="openForm.monto_inicial" type="number" step="0.01" class="mt-1 w-full" />
                    <PrimaryButton class="mt-3 w-full justify-center">Abrir caja</PrimaryButton>
                </form>
            </section>

            <section class="rounded-lg border bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-[#2b1115]">Registrar venta</h2>
                    <div class="text-lg font-bold text-amber-700">Bs {{ total.toFixed(2) }}</div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    <button v-for="item in productos" :key="item.id" class="rounded-md border p-3 text-left hover:border-amber-400 hover:bg-amber-50" :disabled="!cajaActiva" @click="addProduct(item)">
                        <div class="font-medium">{{ item.nombre }}</div>
                        <div class="text-xs text-stone-500">Stock {{ item.stock_actual?.stock || 0 }} / Bs {{ Number(item.precio_venta).toFixed(2) }}</div>
                    </button>
                </div>

                <div class="mt-6 space-y-3">
                    <div v-for="(detalle, index) in saleForm.detalles" :key="index" class="grid items-center gap-3 rounded-md border p-3 sm:grid-cols-[1fr_110px_40px]">
                        <div>
                            <div class="font-medium">{{ producto(detalle.producto_id)?.nombre }}</div>
                            <div class="text-xs text-stone-500">Max {{ producto(detalle.producto_id)?.stock_actual?.stock || 0 }}</div>
                        </div>
                        <TextInput v-model="detalle.cantidad" type="number" min="1" :max="producto(detalle.producto_id)?.stock_actual?.stock || 1" />
                        <button class="text-red-700" @click="saleForm.detalles.splice(index, 1)">X</button>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel value="Tipo de pago" />
                        <select v-model="saleForm.tipo_pago" class="mt-1 w-full rounded-md border-stone-300">
                            <option value="efectivo">Efectivo</option>
                            <option value="qr">QR</option>
                            <option value="tarjeta">Tarjeta</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Monto pagado" />
                        <TextInput v-model="saleForm.monto_pagado" type="number" step="0.01" class="mt-1 w-full" />
                    </div>
                    <div class="flex items-end">
                        <PrimaryButton class="w-full justify-center" :disabled="!cajaActiva || saleForm.detalles.length === 0" @click="submitSale">Registrar venta</PrimaryButton>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioNav from '@/Pages/Inventario/Partials/InventarioNav.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    valorTotal: Number,
    productosBajoMinimo: Number,
    ultimosMovimientos: Array,
});

const showIngreso = ref(false);
const showSalida = ref(false);

const ingresoForm = useForm({
    producto_id: '',
    cantidad: 1,
    costo_unitario: 0,
    motivo: '',
});

const salidaForm = useForm({
    producto_id: '',
    cantidad: 1,
    motivo: '',
});

const tipoLabel = (tipo) => tipo.replace(/_/g, ' ');
</script>

<template>
    <AppLayout title="Inventario">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-[#2b1115]">Inventario</h1>
                    <p class="text-sm text-stone-500">Movimientos, costo promedio, conteo y valorizacion.</p>
                </div>
                <div class="flex gap-2">
                    <SecondaryButton @click="showIngreso = true">Registrar ingreso</SecondaryButton>
                    <SecondaryButton @click="showSalida = true">Registrar salida</SecondaryButton>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <InventarioNav />

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg border bg-white p-5 shadow-sm">
                    <div class="text-sm text-stone-500">Valor total inventario</div>
                    <div class="mt-1 text-2xl font-bold text-[#2b1115]">Bs {{ Number(valorTotal).toFixed(2) }}</div>
                </div>
                <div class="rounded-lg border bg-white p-5 shadow-sm">
                    <div class="text-sm text-stone-500">Productos bajo minimo</div>
                    <div class="mt-1 text-2xl font-bold text-amber-700">{{ productosBajoMinimo }}</div>
                </div>
                <div class="rounded-lg border bg-white p-5 shadow-sm">
                    <div class="text-sm text-stone-500">Ultimos movimientos</div>
                    <div class="mt-1 text-2xl font-bold text-[#2b1115]">{{ ultimosMovimientos.length }}</div>
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-lg border bg-white shadow-sm">
                <div class="border-b px-4 py-3 font-semibold text-[#2b1115]">Ultimos movimientos</div>
                <table class="min-w-full divide-y divide-stone-200 text-sm">
                    <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Producto</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Cantidad</th>
                            <th class="px-4 py-3">Costo u.</th>
                            <th class="px-4 py-3">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <tr v-for="mov in ultimosMovimientos" :key="mov.id">
                            <td class="px-4 py-3">{{ new Date(mov.created_at).toLocaleString() }}</td>
                            <td class="px-4 py-3">{{ mov.producto?.nombre }}</td>
                            <td class="px-4 py-3 capitalize">{{ tipoLabel(mov.tipo) }}</td>
                            <td class="px-4 py-3">{{ mov.cantidad }}</td>
                            <td class="px-4 py-3">Bs {{ Number(mov.costo_unitario).toFixed(2) }}</td>
                            <td class="px-4 py-3">{{ mov.saldo_cantidad }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <DialogModal :show="showIngreso" @close="showIngreso = false">
            <template #title>Registrar ingreso</template>
            <template #content>
                <form class="grid gap-4" @submit.prevent="ingresoForm.post(route('inventario.ingreso.store'), { preserveScroll: true, onSuccess: () => showIngreso = false })">
                    <div>
                        <InputLabel value="ID producto" />
                        <TextInput v-model="ingresoForm.producto_id" type="number" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Cantidad" />
                        <TextInput v-model="ingresoForm.cantidad" type="number" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Costo unitario" />
                        <TextInput v-model="ingresoForm.costo_unitario" type="number" step="0.01" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Motivo" />
                        <TextInput v-model="ingresoForm.motivo" class="mt-1 w-full" />
                    </div>
                    <PrimaryButton :disabled="ingresoForm.processing">Guardar ingreso</PrimaryButton>
                </form>
            </template>
        </DialogModal>

        <DialogModal :show="showSalida" @close="showSalida = false">
            <template #title>Registrar salida / merma</template>
            <template #content>
                <form class="grid gap-4" @submit.prevent="salidaForm.post(route('inventario.salida.store'), { preserveScroll: true, onSuccess: () => showSalida = false })">
                    <div>
                        <InputLabel value="ID producto" />
                        <TextInput v-model="salidaForm.producto_id" type="number" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Cantidad" />
                        <TextInput v-model="salidaForm.cantidad" type="number" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Motivo" />
                        <TextInput v-model="salidaForm.motivo" class="mt-1 w-full" />
                    </div>
                    <PrimaryButton :disabled="salidaForm.processing">Guardar salida</PrimaryButton>
                </form>
            </template>
        </DialogModal>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Panel de Control
            </h2>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-5 md:grid-cols-3">
                <div class="rounded-lg border bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-stone-500">Inventario</div>
                    <div class="mt-2 text-2xl font-semibold text-[#2b1115]">Productos y stock</div>
                </div>
                <div class="rounded-lg border bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-stone-500">Compras</div>
                    <div class="mt-2 text-2xl font-semibold text-[#2b1115]">Ingreso de mercaderia</div>
                </div>
                <div class="rounded-lg border bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-stone-500">Caja</div>
                    <div class="mt-2 text-2xl font-semibold text-[#2b1115]">Ventas del dia</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

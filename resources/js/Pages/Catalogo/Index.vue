<script setup>
import { ref, computed } from 'vue';
import { useForm, Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductosPublicados from './ProductosPublicados.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';

const props = defineProps({
    productos: Array,
    promocionesActive: Array,
});

const page = usePage();
const clienteId = computed(() => page.props.auth.user?.id);

// Estado de Carrito
const cart = ref([]);
const showDrawer = ref(false);
const showPaymentModal = ref(false);

// Formularios
const saleForm = useForm({
    tipo_pago: 'compra_directa', // 'compra_directa', 'efectivo', 'qr', 'tarjeta', 'credito'
    monto_pagado: 0,
    detalles: [],
    cliente_id: clienteId.value,
    codigo_promo: '',
    nro_cuotas: 2,
});

// Lógica de Carrito
const addToCart = (productData) => {
    const existing = cart.value.find(item => item.producto_id === productData.id);
    if (existing) {
        existing.cantidad = Math.min(existing.cantidad + productData.cantidad, productData.stock);
    } else {
        cart.value.push({
            producto_id: productData.id,
            nombre: productData.nombre,
            precio: Number(productData.precio_venta),
            cantidad: productData.cantidad,
            stock: productData.stock,
            imagen: productData.imagen
        });
    }
};

const updateQty = (item, amt) => {
    const newQty = item.cantidad + amt;
    if (newQty <= 0) {
        cart.value = cart.value.filter(i => i.producto_id !== item.producto_id);
    } else if (newQty <= item.stock) {
        item.cantidad = newQty;
    }
};

const removeProduct = (productId) => {
    cart.value = cart.value.filter(i => i.producto_id !== productId);
};

// Totales del carrito
const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
});

const totalItems = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.cantidad, 0);
});

// Descuentos y Promociones
const appliedPromo = computed(() => {
    if (!saleForm.codigo_promo.trim()) return null;
    const promo = props.promocionesActive.find(
        p => p.codigo_promo.toUpperCase() === saleForm.codigo_promo.trim().toUpperCase()
    );
    return promo || null;
});

const discountAmount = computed(() => {
    const promo = appliedPromo.value;
    if (!promo) return 0;
    if (promo.tipo_descuento === 'porcentaje') {
        return subtotal.value * (Number(promo.descuento) / 100);
    }
    return Number(promo.descuento);
});

const totalFinal = computed(() => {
    return Math.max(0, subtotal.value - discountAmount.value);
});

// Cuotas a crédito
const quotaAmount = computed(() => {
    if (saleForm.nro_cuotas < 2) return 0;
    return totalFinal.value / saleForm.nro_cuotas;
});

// Checkout
const handleCheckout = () => {
    if (cart.value.length === 0) return;

    // Poblar detalles del form
    saleForm.detalles = cart.value.map(item => ({
        producto_id: item.producto_id,
        cantidad: item.cantidad
    }));
    saleForm.cliente_id = clienteId.value;

    if (saleForm.tipo_pago === 'qr' || saleForm.tipo_pago === 'tarjeta') {
        showPaymentModal.value = true;
    } else {
        submitSale();
    }
};

const submitSale = () => {
    saleForm.monto_pagado = totalFinal.value;
    // La venta para el cliente requiere que exista una caja abierta de un vendedor en el sistema.
    // Usualmente el sistema validará esto en el backend, por lo que usaremos una caja activa.
    saleForm.post(route('ventas.store'), {
        preserveScroll: true,
        onSuccess: () => {
            cart.value = [];
            saleForm.reset();
            showPaymentModal.value = false;
            showDrawer.value = false;
        },
        onError: (err) => {
            console.error('Error al concretar compra:', err);
            if (err.error) {
                alert(err.error);
            }
        }
    });
};
</script>

<template>
    <AppLayout title="Catálogo de Bebidas">
        <template #header>
            <div class="flex justify-between items-center w-full">
                <div>
                    <h1 class="text-2xl font-bold text-[var(--text-primary)]">Catálogo de Bebidas</h1>
                    <p class="text-sm text-[var(--text-secondary)]">Productos exclusivos y finos listos para ordenar.</p>
                </div>
                <!-- Botón de Carrito -->
                <button
                    class="relative rounded-2xl bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white px-5 py-3 font-semibold transition shadow-lg cursor-pointer flex items-center gap-2"
                    @click="showDrawer = true"
                >
                    <span>🛒 Carrito</span>
                    <span v-if="totalItems > 0" class="absolute -top-2 -right-2 bg-indigo-600 text-white text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center border border-white/20 shadow-md">
                        {{ totalItems }}
                    </span>
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-16">
            <!-- Listado de Productos -->
            <ProductosPublicados :productos="productos" @add-to-cart="addToCart" />
        </div>

        <!-- DRAWER DESLIZABLE (CARRITO DE COMPRAS) - DERECHA A IZQUIERDA -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-x-full"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 translate-x-full"
        >
            <div v-if="showDrawer" class="fixed inset-0 z-50 flex justify-end bg-black/60 backdrop-blur-sm" @click.self="showDrawer = false">
                <div class="w-full max-w-md bg-slate-900 border-l border-white/10 h-full flex flex-col justify-between shadow-2xl p-6 relative text-white">
                    
                    <!-- Cabecera de Carrito -->
                    <div>
                        <div class="flex justify-between items-center border-b border-white/10 pb-4 mb-4">
                            <h3 class="text-lg font-bold">Carrito de Bebidas</h3>
                            <button @click="showDrawer = false" class="text-white/60 hover:text-white bg-white/5 hover:bg-white/15 p-1.5 rounded-full transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Lista de productos -->
                        <div class="space-y-3 overflow-y-auto max-h-[30vh] pr-1">
                            <div v-if="cart.length === 0" class="text-center py-12 text-sm text-white/50">
                                Tu carrito está vacío.<br>Agrega productos para ordenar.
                            </div>
                            <div
                                v-for="item in cart"
                                :key="item.producto_id"
                                class="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/5"
                            >
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <img :src="item.imagen" :alt="item.nombre" class="w-10 h-10 rounded-lg object-cover bg-white/10 flex-shrink-0" />
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-xs font-bold truncate">{{ item.nombre }}</h4>
                                        <span class="text-[10px] text-white/60">{{ item.precio.toFixed(2) }} Bs</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button
                                        class="w-6 h-6 rounded-lg bg-white/5 hover:bg-white/15 text-xs flex items-center justify-center font-bold"
                                        @click="updateQty(item, -1)"
                                    >
                                        -
                                    </button>
                                    <span class="text-xs font-semibold px-1 min-w-[20px] text-center">{{ item.cantidad }}</span>
                                    <button
                                        class="w-6 h-6 rounded-lg bg-white/5 hover:bg-white/15 text-xs flex items-center justify-center font-bold"
                                        @click="updateQty(item, 1)"
                                    >
                                        +
                                    </button>
                                    <button
                                        class="w-6 h-6 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-[10px] text-rose-400 flex items-center justify-center font-bold ml-1"
                                        @click="removeProduct(item.producto_id)"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Configuración de Pago (CU6 y CU7) -->
                        <div v-if="cart.length > 0" class="border-t border-white/10 pt-4 mt-4 space-y-4">
                            <!-- Cupón de Promoción -->
                            <div>
                                <InputLabel value="Cupón de Descuento" class="text-xs text-white/70" />
                                <TextInput
                                    v-model="saleForm.codigo_promo"
                                    type="text"
                                    class="w-full text-xs uppercase font-mono py-1.5 mt-1 block"
                                    placeholder="Ej. ESPECIAL20"
                                />
                                <div v-if="saleForm.codigo_promo.trim() && appliedPromo" class="text-xs text-emerald-400 font-semibold mt-1">
                                    ✓ Descuento aplicado: {{ appliedPromo.nombre_promo }} ({{ Number(appliedPromo.descuento) }}{{ appliedPromo.tipo_descuento === 'porcentaje' ? '%' : ' Bs' }})
                                </div>
                                <div v-else-if="saleForm.codigo_promo.trim() && !appliedPromo" class="text-xs text-rose-400 font-semibold mt-1">
                                    ✕ Cupón no válido o expirado
                                </div>
                            </div>

                            <!-- Modalidad y Método de Pago -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <InputLabel value="Método de Pago" class="text-xs text-white/70" />
                                    <select
                                        v-model="saleForm.tipo_pago"
                                        class="mt-1 block w-full rounded-xl border-white/10 bg-slate-900 text-white focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-xs p-2 transition"
                                    >
                                        <option value="compra_directa">Compra Directa</option>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="qr">Código QR</option>
                                        <option value="tarjeta">Tarjeta Bancaria</option>
                                        <option value="credito">A Crédito (Cuotas)</option>
                                    </select>
                                </div>

                                <div v-if="saleForm.tipo_pago === 'credito'">
                                    <InputLabel value="Nro. Cuotas" class="text-xs text-white/70" />
                                    <select
                                        v-model="saleForm.nro_cuotas"
                                        class="mt-1 block w-full rounded-xl border-white/10 bg-slate-900 text-white focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-xs p-2 transition"
                                    >
                                        <option :value="2">2 cuotas</option>
                                        <option :value="3">3 cuotas</option>
                                        <option :value="4">4 cuotas</option>
                                        <option :value="6">6 cuotas</option>
                                        <option :value="12">12 cuotas</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Plan de Cuotas Estimado -->
                            <div v-if="saleForm.tipo_pago === 'credito'" class="p-3 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-xs text-indigo-300">
                                <span class="font-bold block uppercase tracking-wider text-[10px] text-indigo-400 mb-1">Plan de Cuotas:</span>
                                {{ saleForm.nro_cuotas }} cuotas mensuales de <strong class="text-white font-bold text-sm">{{ quotaAmount.toFixed(2) }} Bs</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen contable y botón de compra -->
                    <div class="border-t border-white/10 pt-4 space-y-4">
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between text-white/60">
                                <span>Subtotal:</span>
                                <span>{{ subtotal.toFixed(2) }} Bs</span>
                            </div>
                            <div v-if="discountAmount > 0" class="flex justify-between text-emerald-400 font-semibold">
                                <span>Descuento Promoción:</span>
                                <span>-{{ discountAmount.toFixed(2) }} Bs</span>
                            </div>
                            <div class="flex justify-between text-base font-extrabold text-white border-t border-white/5 pt-2">
                                <span>Total Final:</span>
                                <span class="text-indigo-300 text-lg">{{ totalFinal.toFixed(2) }} Bs</span>
                            </div>
                        </div>

                        <button
                            class="w-full bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white py-3 rounded-2xl font-bold transition shadow-lg text-sm flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="cart.length === 0 || saleForm.processing"
                            @click="handleCheckout"
                        >
                            🛍️ Concretar Compra
                        </button>
                    </div>

                </div>
            </div>
        </transition>

        <!-- MODAL: SIMULADOR DE PAGOS ELECTRÓNICOS (CLIENTE) -->
        <DialogModal :show="showPaymentModal" @close="showPaymentModal = false">
            <template #title>
                <h3 class="text-lg font-bold text-[var(--text-primary)]">
                    {{ saleForm.tipo_pago === 'qr' ? 'Pago con Código QR' : 'Procesador de Tarjeta' }}
                </h3>
            </template>

            <template #content>
                <!-- Simulador de Pago con QR -->
                <div v-if="saleForm.tipo_pago === 'qr'" class="flex flex-col items-center space-y-4 py-4 text-center">
                    <p class="text-sm text-[var(--text-secondary)]">Escanea el código QR desde tu aplicación bancaria para liquidar los <strong class="text-[var(--text-primary)]">{{ totalFinal.toFixed(2) }} Bs</strong>.</p>
                    <div class="bg-white p-3 rounded-2xl shadow-xl flex items-center justify-center">
                        <img
                            :src="`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=CobroLicorVintage:${totalFinal.toFixed(2)}Bs`"
                            alt="QR de Pago"
                            class="w-40 h-40"
                        />
                    </div>
                    <span class="text-[10px] bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 px-3 py-1 rounded-full uppercase font-bold tracking-wider">
                        Pago en Espera de Confirmación
                    </span>
                </div>

                <!-- Simulador de Pago con Tarjeta -->
                <div v-else class="space-y-4 py-2">
                    <p class="text-xs text-[var(--text-secondary)]">Simulador de Transacción Bancaria. Introduce datos ficticios para validar.</p>
                    
                    <div class="bg-gradient-to-tr from-slate-900 to-indigo-950 border border-white/10 rounded-2xl p-5 shadow-2xl space-y-4">
                        <div class="flex justify-between items-center text-xs font-semibold uppercase tracking-wider text-indigo-300">
                            <span>Licor Vintage Premium</span>
                            <span>💳 Card Link</span>
                        </div>

                        <div>
                            <InputLabel value="Número de Tarjeta" class="text-[10px] text-white/50" />
                            <TextInput type="text" class="w-full text-sm font-mono mt-1" placeholder="4000 1234 5678 9010" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Vencimiento" class="text-[10px] text-white/50" />
                                <TextInput type="text" class="w-full text-sm font-mono mt-1" placeholder="MM/AA" />
                            </div>
                            <div>
                                <InputLabel value="CVV" class="text-[10px] text-white/50" />
                                <TextInput type="password" class="w-full text-sm font-mono mt-1" placeholder="***" />
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex items-center gap-3">
                    <SecondaryButton @click="showPaymentModal = false">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': saleForm.processing }" :disabled="saleForm.processing" @click="submitSale">
                        Confirmar Pago Simulado
                    </PrimaryButton>
                </div>
            </template>
        </DialogModal>
    </AppLayout>
</template>

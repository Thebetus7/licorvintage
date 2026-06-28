<script setup>
import { ref, computed } from 'vue';
import { useForm, Head, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductosPublicados from './ProductosPublicados.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import CreditCardForm from '@/Components/CreditCardForm.vue';

const props = defineProps({
    productos: Array,
    promocionesActive: Array,
    creditosPendientes: Array,
});

const page = usePage();
const clienteId = computed(() => page.props.auth.user?.id);

// Estado de Carrito
const cart = ref([]);
const showDrawer = ref(false);
const showPaymentMethodModal = ref(false);
const showPaymentModal = ref(false);
const showSuccessModal = ref(false);

// Estado QR
const qrData = ref(null);
const qrLoading = ref(false);
const qrError = ref('');
const qrPollingActive = ref(false);
const qrPollingStatus = ref('');
let qrPollingInterval = null;

// Formularios
const saleForm = useForm({
    tipo_pago: 'compra_directa',
    monto_pagado: 0,
    detalles: [],
    cliente_id: clienteId.value,
    codigo_promo: '',
    nro_cuotas: 2,
    card_number: '',
    card_expiry: '',
    card_cvc: '',
});

const cardData = ref({ number: '', expiry: '', cvc: '' });

// Estado Creditos
const activeTab = ref('productos');
const showPagoCuotaModal = ref(false);
const selectedCuota = ref(null);
const pagoCuotaMethod = ref('qr');
const pagoCuotaQrImage = ref(null);
const pagoCuotaQrTransactionId = ref(null);
const pagoCuotaQrLoading = ref(false);
const pagoCuotaCardProcessing = ref(false);
const pagoCuotaCardError = ref('');
const pagoCuotaQrError = ref('');
const showPagoCuotaQrError = ref(false);

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

const quotaAmount = computed(() => {
    if (saleForm.nro_cuotas < 2) return 0;
    return totalFinal.value / saleForm.nro_cuotas;
});

// Computed para creditos
const filteredCreditos = computed(() => {
    if (!props.creditosPendientes) return [];
    return props.creditosPendientes.filter(c =>
        c.venta_cuotas?.some(cu => cu.estado === 'pendiente')
    );
});

const cuotasPendientes = computed(() => {
    const list = [];
    filteredCreditos.value.forEach(venta => {
        venta.venta_cuotas?.forEach(cu => {
            if (cu.estado === 'pendiente') {
                list.push({ ...cu, venta });
            }
        });
    });
    return list;
});

// Checkout — muestra modal de selección de método
const handleCheckout = () => {
    if (cart.value.length === 0) return;

    saleForm.detalles = cart.value.map(item => ({
        producto_id: item.producto_id,
        cantidad: item.cantidad
    }));
    saleForm.cliente_id = clienteId.value;

    showPaymentMethodModal.value = true;
};

// Seleccionar método de pago
const selectPaymentMethod = (method) => {
    saleForm.tipo_pago = method;
    showPaymentMethodModal.value = false;

    if (method === 'qr') {
        generateQR();
    } else if (method === 'tarjeta') {
        showPaymentModal.value = true;
    } else {
        submitSale();
    }
};

// Generar QR via backend
const generateQR = async () => {
    qrLoading.value = true;
    qrError.value = '';
    qrData.value = null;

    try {
        const response = await window.axios.post(route('pago.qr.generar'), {
            monto: totalFinal.value,
            orderDetail: cart.value.map((item, index) => ({
                serial: index + 1,
                product: item.nombre,
                quantity: item.cantidad,
                price: item.precio,
                discount: 0,
                total: item.precio * item.cantidad,
            })),
        });

        if (response.data.error) {
            qrError.value = response.data.message || 'Error al generar QR';
        } else {
            qrData.value = response.data.data;
            showPaymentModal.value = true;
        }
    } catch (err) {
        qrError.value = err.response?.data?.message || 'Error de conexión al generar QR';
    } finally {
        qrLoading.value = false;
    }
};

// Cerrar modal de pago y limpiar estado QR
const closePaymentModal = () => {
    showPaymentModal.value = false;
    if (qrPollingInterval) {
        clearInterval(qrPollingInterval);
        qrPollingInterval = null;
    }
    qrPollingActive.value = false;
    if (saleForm.tipo_pago === 'qr') {
        qrData.value = null;
        qrError.value = '';
    }
};
// Confirmar pago QR con polling
const handleConfirmQRPayment = () => {
    if (!qrData.value?.transactionId) return;

    const tid = String(qrData.value.transactionId);

    // QR local (fallback): guardar directo
    if (tid.startsWith('local_')) {
        submitSale();
        return;
    }

    qrPollingActive.value = true;
    qrPollingStatus.value = 'Esperando confirmacion del pago...';
    let elapsed = 0;
    const interval = 3000;
    const maxTime = 90000;
    const terminalErrorStates = ['Cancelado', 'Rechazado', 'Fallido', 'Expirado', 'Anulado'];

    qrPollingInterval = setInterval(async () => {
        elapsed += interval;
        if (elapsed >= maxTime) {
            clearInterval(qrPollingInterval);
            qrPollingInterval = null;
            qrPollingActive.value = false;
            qrError.value = 'Tiempo de espera agotado (1:30 min). El pago no fue confirmado.';
            return;
        }
        qrPollingStatus.value = `Verificando pago... (${Math.floor(elapsed / 1000)}s)`;
        try {
            const resp = await window.axios.post(route('pago.qr.checkStatus'), {
                transactionId: tid,
            });
            if (resp.data?.error === false && resp.data?.data) {
                const statusDesc = resp.data.data.paymentStatusDescription || '';
                const statusCode = resp.data.data.paymentStatus;

                if (statusDesc === 'Pagado' || statusCode === 2) {
                    clearInterval(qrPollingInterval);
                    qrPollingInterval = null;
                    qrPollingStatus.value = 'Pago confirmado. Guardando venta...';
                    submitSale();
                    return;
                }

                if (terminalErrorStates.includes(statusDesc)) {
                    clearInterval(qrPollingInterval);
                    qrPollingInterval = null;
                    qrPollingActive.value = false;
                    qrError.value = `El pago fue ${statusDesc.toLowerCase()} por el cliente.`;
                    return;
                }
            }
        } catch {
            // ignore network errors, keep polling
        }
    }, interval);
};

// Confirmar pago y finalizar compra
const submitSale = () => {
    if (saleForm.tipo_pago === 'tarjeta') {
        saleForm.card_number = cardData.value.number;
        saleForm.card_expiry = cardData.value.expiry;
        saleForm.card_cvc = cardData.value.cvc;
    }
    saleForm.monto_pagado = totalFinal.value;
    saleForm.post(route('ventas.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccessModal.value = true;
            cart.value = [];
            saleForm.reset();
            cardData.value = { number: '', expiry: '', cvc: '' };
            qrData.value = null;
            qrPollingActive.value = false;
            qrPollingStatus.value = '';
            if (qrPollingInterval) {
                clearInterval(qrPollingInterval);
                qrPollingInterval = null;
            }
            showPaymentModal.value = false;
            showPaymentMethodModal.value = false;
            showDrawer.value = false;
        },
        onError: (err) => {
            console.error('Error al concretar compra:', err);
        }
    });
};

// --- Creditos / Cuotas ---
const abrirPagoCuota = (cuota) => {
    selectedCuota.value = cuota;
    pagoCuotaMethod.value = 'qr';
    pagoCuotaQrImage.value = null;
    pagoCuotaQrTransactionId.value = null;
    pagoCuotaQrLoading.value = false;
    pagoCuotaCardProcessing.value = false;
    pagoCuotaCardError.value = '';
    pagoCuotaQrError.value = '';
    showPagoCuotaQrError.value = false;
    cardData.value = { number: '', expiry: '', cvc: '' };
    showPagoCuotaModal.value = true;
};

const generarPagoCuotaQR = async () => {
    if (!selectedCuota.value) return;
    pagoCuotaQrLoading.value = true;
    pagoCuotaQrImage.value = null;
    pagoCuotaQrTransactionId.value = null;
    pagoCuotaQrError.value = '';
    showPagoCuotaQrError.value = false;

    try {
        const monto = Number(selectedCuota.value.sub_monto || 0);
        const detail = [{
            serial: 1,
            product: `Cuota #${selectedCuota.value.nro_cuota} - Venta #${selectedCuota.value.venta_id}`,
            quantity: 1,
            price: monto,
            discount: 0,
            total: monto,
        }];
        const resp = await window.axios.post(route('pago.qr.generar'), {
            monto: monto,
            orderDetail: detail,
        });
        if (resp.data?.error === false && resp.data?.data?.qrBase64) {
            pagoCuotaQrImage.value = resp.data.data.qrBase64;
            pagoCuotaQrTransactionId.value = resp.data.data.transactionId ?? null;
        } else {
            pagoCuotaQrError.value = resp.data?.message || 'Error al generar el codigo QR.';
            showPagoCuotaQrError.value = true;
        }
    } catch {
        pagoCuotaQrError.value = 'Error de conexion al generar el codigo QR.';
        showPagoCuotaQrError.value = true;
    } finally {
        pagoCuotaQrLoading.value = false;
    }
};

const confirmarPagoCuota = async () => {
    if (!selectedCuota.value) return;

    if (pagoCuotaMethod.value === 'qr') {
        if (String(pagoCuotaQrTransactionId.value).startsWith('local_')) {
            submitPagoCuota();
            return;
        }
        submitPagoCuota();
        return;
    }

    if (pagoCuotaMethod.value === 'tarjeta') {
        pagoCuotaCardError.value = '';
        if (!cardData.value.number || cardData.value.number.length < 13) {
            pagoCuotaCardError.value = 'Numero de tarjeta invalido.';
            return;
        }
        if (!cardData.value.expiry || cardData.value.expiry.length < 4) {
            pagoCuotaCardError.value = 'Fecha de vencimiento invalida.';
            return;
        }
        if (!cardData.value.cvc || cardData.value.cvc.length < 3) {
            pagoCuotaCardError.value = 'CVC invalido.';
            return;
        }
        submitPagoCuota();
    }
};

const submitPagoCuota = () => {
    if (!selectedCuota.value) return;
    pagoCuotaCardProcessing.value = true;

    const payload = {
        payment_method: pagoCuotaMethod.value,
    };
    if (pagoCuotaMethod.value === 'tarjeta') {
        payload.card_number = cardData.value.number;
        payload.card_expiry = cardData.value.expiry;
        payload.card_cvc = cardData.value.cvc;
    }

    window.axios.post(route('cliente.cuotas.pagar', selectedCuota.value.id), payload)
        .then(() => {
            showPagoCuotaModal.value = false;
            selectedCuota.value = null;
            pagoCuotaQrImage.value = null;
            pagoCuotaQrTransactionId.value = null;
            pagoCuotaCardProcessing.value = false;
            router.reload({ only: ['creditosPendientes'] });
        })
        .catch((err) => {
            const data = err.response?.data;
            pagoCuotaCardError.value = data?.error || data?.message || 'Error al procesar el pago.';
            pagoCuotaCardProcessing.value = false;
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
            <!-- Tabs -->
            <div class="flex gap-2 border-b border-white/10 pb-3 mb-6">
                <button
                    v-for="tab in [
                        { key: 'productos', label: 'Productos' },
                        { key: 'creditos', label: 'Mis Creditos' },
                    ]"
                    :key="tab.key"
                    class="px-5 py-2.5 text-sm font-bold rounded-xl transition"
                    :class="activeTab === tab.key
                        ? 'bg-[var(--accent)] text-white shadow-lg'
                        : 'text-[var(--text-secondary)] hover:bg-white/5'"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Tab: Productos -->
            <div v-if="activeTab === 'productos'">
                <ProductosPublicados :productos="productos" @add-to-cart="addToCart" />
            </div>

            <!-- Tab: Mis Creditos -->
            <div v-if="activeTab === 'creditos'" class="space-y-4">
                <div v-if="cuotasPendientes.length === 0" class="text-center py-16 text-[var(--text-secondary)]">
                    <div class="text-5xl mb-4">📋</div>
                    <h3 class="text-lg font-bold text-[var(--text-primary)] mb-2">No tienes creditos pendientes</h3>
                    <p class="text-sm">Todas tus cuotas estan al dia.</p>
                </div>

                <div v-for="venta in filteredCreditos" :key="venta.id" class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h4 class="text-sm font-bold text-[var(--text-primary)]">Venta #{{ venta.id }}</h4>
                            <p class="text-xs text-[var(--text-secondary)]">{{ new Date(venta.created_at).toLocaleDateString() }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-[var(--text-secondary)]">Total</p>
                            <p class="text-sm font-bold text-indigo-300">Bs {{ Number(venta.monto_final).toFixed(2) }}</p>
                        </div>
                    </div>

                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-[var(--text-secondary)] border-b border-white/5">
                                <th class="text-left py-2">Cuota</th>
                                <th class="text-right py-2">Monto</th>
                                <th class="text-right py-2">Estado</th>
                                <th class="text-right py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="cu in venta.venta_cuotas" :key="cu.id" class="border-b border-white/5">
                                <td class="py-2 font-medium">#{{ cu.nro_cuota }}</td>
                                <td class="py-2 text-right font-mono">Bs {{ Number(cu.sub_monto).toFixed(2) }}</td>
                                <td class="py-2 text-right">
                                    <span v-if="cu.estado === 'pagado'" class="text-emerald-400 font-semibold">Pagado</span>
                                    <span v-else class="text-amber-400 font-semibold">Pendiente</span>
                                </td>
                                <td class="py-2 text-right">
                                    <button
                                        v-if="cu.estado === 'pendiente'"
                                        class="text-xs bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white px-3 py-1.5 rounded-lg font-semibold transition cursor-pointer"
                                        @click="abrirPagoCuota(cu)"
                                    >
                                        Pagar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
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
                            <div>
                                <InputLabel value="Método de Pago" class="text-xs text-white/70" />
                                <div class="grid grid-cols-2 gap-2 mt-1">
                                    <button
                                        v-for="opt in [
                                            { value: 'qr', label: 'Código QR', icon: '📱' },
                                            { value: 'tarjeta', label: 'Tarjeta', icon: '💳' },
                                            { value: 'efectivo', label: 'Efectivo', icon: '💵' },
                                            { value: 'credito', label: 'Crédito', icon: '📋' },
                                        ]"
                                        :key="opt.value"
                                        class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold transition border cursor-pointer"
                                        :class="saleForm.tipo_pago === opt.value
                                            ? 'bg-indigo-500/20 border-indigo-400/40 text-indigo-300'
                                            : 'bg-white/5 border-white/10 text-white/70 hover:bg-white/10'"
                                        @click="saleForm.tipo_pago = opt.value"
                                    >
                                        <span>{{ opt.icon }}</span>
                                        {{ opt.label }}
                                    </button>
                                </div>
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

        <!-- MODAL: SELECCIÓN DE MÉTODO DE PAGO -->
        <DialogModal :show="showPaymentMethodModal" @close="showPaymentMethodModal = false">
            <template #title>
                <h3 class="text-lg font-bold text-[var(--text-primary)]">Selecciona método de pago</h3>
            </template>

            <template #content>
                <div class="grid grid-cols-2 gap-4 py-4">
                    <button
                        class="flex flex-col items-center gap-3 p-6 rounded-2xl border-2 transition cursor-pointer"
                        :class="saleForm.tipo_pago === 'qr'
                            ? 'border-indigo-400 bg-indigo-500/10'
                            : 'border-white/10 bg-white/5 hover:border-indigo-400/50 hover:bg-indigo-500/5'"
                        @click="selectPaymentMethod('qr')"
                    >
                        <span class="text-4xl">📱</span>
                        <span class="font-bold text-sm text-[var(--text-primary)]">Código QR</span>
                        <span class="text-[10px] text-[var(--text-secondary)]">Paga con tu app bancaria</span>
                    </button>
                    <button
                        class="flex flex-col items-center gap-3 p-6 rounded-2xl border-2 transition cursor-pointer"
                        :class="saleForm.tipo_pago === 'tarjeta'
                            ? 'border-indigo-400 bg-indigo-500/10'
                            : 'border-white/10 bg-white/5 hover:border-indigo-400/50 hover:bg-indigo-500/5'"
                        @click="selectPaymentMethod('tarjeta')"
                    >
                        <span class="text-4xl">💳</span>
                        <span class="font-bold text-sm text-[var(--text-primary)]">Tarjeta</span>
                        <span class="text-[10px] text-[var(--text-secondary)]">Débito o Crédito</span>
                    </button>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="showPaymentMethodModal = false">Cancelar</SecondaryButton>
            </template>
        </DialogModal>

        <!-- MODAL: PAGO QR / TARJETA -->
        <DialogModal :show="showPaymentModal" @close="closePaymentModal">
            <template #title>
                <h3 class="text-lg font-bold text-[var(--text-primary)]">
                    {{ saleForm.tipo_pago === 'qr' ? 'Pago con Código QR' : 'Pago con Tarjeta' }}
                </h3>
            </template>

            <template #content>
                <!-- QR: generado por PagoFacil -->
                <div v-if="saleForm.tipo_pago === 'qr'" class="flex flex-col items-center space-y-4 py-4 text-center">
                    <div v-if="qrLoading" class="flex flex-col items-center gap-3 py-8">
                        <svg class="animate-spin h-10 w-10 text-indigo-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <span class="text-sm text-[var(--text-secondary)]">Generando código QR...</span>
                    </div>

                    <div v-else-if="qrError" class="text-center py-6">
                        <p class="text-rose-400 text-sm font-semibold">{{ qrError }}</p>
                        <button class="mt-3 text-xs text-indigo-400 underline cursor-pointer" @click="generateQR">Intentar de nuevo</button>
                    </div>

                    <template v-else-if="qrData">
                        <p class="text-sm text-[var(--text-secondary)]">Escanea el código QR desde tu aplicación bancaria para pagar <strong class="text-[var(--text-primary)]">{{ totalFinal.toFixed(2) }} Bs</strong>.</p>
                        <div class="bg-white p-3 rounded-2xl shadow-xl flex items-center justify-center">
                            <img
                                :src="`data:image/${qrData.qrFormat || 'png'};base64,${qrData.qrBase64}`"
                                alt="QR de Pago"
                                class="w-48 h-48"
                            />
                        </div>
                        <div v-if="!qrPollingActive" class="flex items-center gap-2 text-[10px] bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 px-4 py-2 rounded-full uppercase font-bold tracking-wider">
                            <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                            Esperando confirmación de pago
                        </div>
                        <div v-else class="flex items-center gap-2 text-[10px] bg-amber-500/10 border border-amber-500/20 text-amber-300 px-4 py-2 rounded-full uppercase font-bold tracking-wider">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ qrPollingStatus }}
                        </div>
                        <p class="text-[10px] text-[var(--text-secondary)]">Transacción: {{ qrData.transactionId }}</p>
                    </template>
                </div>

                <!-- Tarjeta -->
                <div v-else class="space-y-4 py-2">
                    <CreditCardForm v-model="cardData" />
                </div>
            </template>

            <template #footer>
                <div class="flex items-center gap-3">
                    <SecondaryButton @click="closePaymentModal" :disabled="qrPollingActive">
                        Cancelar
                    </SecondaryButton>
                    <template v-if="saleForm.tipo_pago === 'qr' && qrData && !qrLoading">
                        <div v-if="qrPollingActive" class="flex items-center gap-2 text-sm text-amber-300">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ qrPollingStatus }}
                        </div>
                        <PrimaryButton
                            v-else
                            :class="{ 'opacity-25': saleForm.processing }"
                            :disabled="saleForm.processing"
                            @click="handleConfirmQRPayment"
                        >
                            {{ saleForm.processing ? 'Procesando...' : 'Ya pagué — Confirmar' }}
                        </PrimaryButton>
                    </template>
                    <PrimaryButton
                        v-else-if="saleForm.tipo_pago === 'tarjeta'"
                        :class="{ 'opacity-25': saleForm.processing }"
                        :disabled="saleForm.processing"
                        @click="submitSale"
                    >
                        {{ saleForm.processing ? 'Procesando...' : 'Confirmar Pago' }}
                    </PrimaryButton>
                </div>
            </template>
        </DialogModal>

        <!-- MODAL: PAGO EXITOSO -->
        <DialogModal :show="showSuccessModal" @close="showSuccessModal = false">
            <template #title>
                <div class="flex items-center gap-2 text-emerald-400 font-bold text-lg">
                    <span>✨ Compra Exitosa</span>
                </div>
            </template>

            <template #content>
                <div class="flex flex-col items-center text-center py-6 space-y-4">
                    <!-- Icono Animado de Éxito -->
                    <div class="w-16 h-16 bg-emerald-500/10 border border-emerald-500/30 rounded-full flex items-center justify-center text-emerald-400 text-3xl animate-bounce shadow-lg shadow-emerald-500/10">
                        ✓
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-xl font-extrabold text-[var(--text-primary)]">¡Muchas gracias por tu compra!</h3>
                        <p class="text-sm text-[var(--text-secondary)] max-w-sm">
                            Tu pago ha sido procesado de forma segura. El pedido se ha registrado correctamente y el stock ha sido actualizado.
                        </p>
                    </div>
                    <div class="bg-white/5 border border-white/10 rounded-2xl p-4 w-full text-left text-xs font-mono text-[var(--text-secondary)] space-y-1">
                        <div class="flex justify-between">
                            <span>Estado:</span>
                            <span class="text-emerald-400 font-bold uppercase">Aprobado</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Plataforma:</span>
                            <span>Licor Vintage S.R.L.</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Detalle de Pago:</span>
                            <span class="text-indigo-400 font-bold">Tarjeta de Crédito</span>
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <PrimaryButton @click="showSuccessModal = false" class="w-full justify-center">
                    Entendido
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- MODAL: PAGO DE CUOTA -->
        <DialogModal :show="showPagoCuotaModal" @close="showPagoCuotaModal = false">
            <template #title>
                <h3 class="text-lg font-bold text-[var(--text-primary)]">
                    Pagar Cuota #{{ selectedCuota?.nro_cuota }} — Bs {{ Number(selectedCuota?.sub_monto || 0).toFixed(2) }}
                </h3>
            </template>

            <template #content>
                <div class="flex gap-2 mb-4">
                    <button
                        v-for="opt in [
                            { value: 'qr', label: 'QR', icon: '📱' },
                            { value: 'tarjeta', label: 'Tarjeta', icon: '💳' },
                        ]"
                        :key="opt.value"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition border cursor-pointer"
                        :class="pagoCuotaMethod === opt.value
                            ? 'bg-indigo-500/20 border-indigo-400/40 text-indigo-300'
                            : 'bg-white/5 border-white/10 text-white/70 hover:bg-white/10'"
                        @click="pagoCuotaMethod = opt.value; pagoCuotaQrImage = null; pagoCuotaQrTransactionId = null; pagoCuotaQrError = ''; showPagoCuotaQrError = false"
                    >
                        <span>{{ opt.icon }}</span>
                        {{ opt.label }}
                    </button>
                </div>

                <div v-if="pagoCuotaMethod === 'qr'" class="flex flex-col items-center space-y-4 py-2">
                    <div v-if="pagoCuotaQrLoading" class="flex items-center gap-2 text-sm text-[var(--text-secondary)]">
                        <svg class="animate-spin h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Generando QR...
                    </div>
                    <div v-else-if="pagoCuotaQrImage" class="bg-white p-3 rounded-2xl shadow-xl">
                        <img :src="`data:image/png;base64,${pagoCuotaQrImage}`" alt="QR" class="w-48 h-48">
                    </div>
                    <div v-else>
                        <PrimaryButton type="button" :disabled="pagoCuotaQrLoading" @click="generarPagoCuotaQR">
                            Generar QR
                        </PrimaryButton>
                    </div>
                    <div v-if="pagoCuotaQrError" class="text-xs text-rose-400">{{ pagoCuotaQrError }}</div>
                </div>

                <div v-if="pagoCuotaMethod === 'tarjeta'" class="space-y-2">
                    <CreditCardForm v-model="cardData" />
                    <div v-if="pagoCuotaCardError" class="text-xs text-rose-400">{{ pagoCuotaCardError }}</div>
                </div>
            </template>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="showPagoCuotaModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton
                        type="button"
                        :disabled="pagoCuotaCardProcessing || (pagoCuotaMethod === 'qr' && !pagoCuotaQrImage)"
                        @click="confirmarPagoCuota"
                    >
                        {{ pagoCuotaCardProcessing ? 'Procesando...' : 'Confirmar pago' }}
                    </PrimaryButton>
                </div>
            </template>
        </DialogModal>
    </AppLayout>
</template>

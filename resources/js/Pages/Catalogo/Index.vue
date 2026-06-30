<script setup>
import { ref, computed, watch } from 'vue';
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
import ReportModal from '@/Components/ReportModal.vue';

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
const stockError = ref('');
const stockLoading = ref(false);

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

// Estado Creditos + Comprobantes
const activeTab = ref('productos');
const comprobantes = ref([]);
const comprobantesPagination = ref(null);
const comprobantesLoading = ref(false);
const comprobantesFrom = ref(new Date().toISOString().split('T')[0]);
const comprobantesTo = ref(new Date().toISOString().split('T')[0]);
const showDetalleComprobanteModal = ref(false);
const selectedComprobante = ref(null);
const showPagoCuotaModal = ref(false);

// Estado Pedidos
const misPedidos = ref([]);
const misPedidosPagination = ref(null);
const misPedidosLoading = ref(false);
const showDetallePedidoModal = ref(false);
const selectedPedido = ref(null);
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

const setQty = (item, value, event) => {
    const qty = parseInt(value) || 1;
    const clamped = Math.max(1, Math.min(qty, item.stock));
    item.cantidad = clamped;
    if (event) event.target.value = clamped;
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
    stockError.value = '';

    showPaymentMethodModal.value = true;
};

// Seleccionar método de pago (desde modal)
const selectPaymentMethod = (method) => {
    showPaymentMethodModal.value = false;

    if (method === 'qr') {
        generateQR();
    } else if (method === 'tarjeta') {
        showPaymentModal.value = true;
    } else {
        submitSale();
    }
};

const continuarPago = async () => {
    if (!saleForm.tipo_pago) return;
    if (saleForm.tipo_pago === 'credito' && saleForm.nro_cuotas < 2) return;

    stockError.value = '';
    stockLoading.value = true;

    try {
        const resp = await window.axios.post(route('cliente.validar.stock'), {
            productos: cart.value.map(item => ({
                producto_id: item.producto_id,
                cantidad: item.cantidad,
            })),
        });

        if (!resp.data.success) {
            stockError.value = resp.data.message;
            return;
        }
    } catch (err) {
        stockError.value = err.response?.data?.message || 'Error al validar stock. Intente de nuevo.';
        return;
    } finally {
        stockLoading.value = false;
    }

    selectPaymentMethod(saleForm.tipo_pago);
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

// --- Comprobantes ---
const cargarComprobantes = async () => {
    comprobantesLoading.value = true;
    try {
        const resp = await window.axios.get(route('cliente.comprobantes'), {
            params: { from: comprobantesFrom.value, to: comprobantesTo.value },
        });
        comprobantes.value = resp.data.data || [];
        comprobantesPagination.value = {
            currentPage: resp.data.current_page,
            lastPage: resp.data.last_page,
            links: resp.data.links || [],
        };
    } catch {
        comprobantes.value = [];
        comprobantesPagination.value = null;
    } finally {
        comprobantesLoading.value = false;
    }
};

const irPaginaComprobantes = async (url) => {
    if (!url) return;
    comprobantesLoading.value = true;
    try {
        const resp = await window.axios.get(url);
        comprobantes.value = resp.data.data || [];
        comprobantesPagination.value = {
            currentPage: resp.data.current_page,
            lastPage: resp.data.last_page,
            links: resp.data.links || [],
        };
    } catch {
        comprobantes.value = [];
    } finally {
        comprobantesLoading.value = false;
    }
};

const abrirDetalleComprobante = (v) => {
    selectedComprobante.value = v;
    showDetalleComprobanteModal.value = true;
};

function formatTipoPago(tipo) {
    const map = { efectivo: 'Efectivo', qr: 'QR', tarjeta: 'Tarjeta', credito: 'Credito', compra_directa: 'Directo', mixto: 'Mixto' };
    return map[tipo] || tipo;
}

function formatEstadoPedido(estado) {
    const map = { pagado: 'Pagado', enviado: 'Enviado', completado: 'Completado' };
    return map[estado] || estado;
}

// --- Mis Pedidos ---
const cargarMisPedidos = async () => {
    misPedidosLoading.value = true;
    try {
        const resp = await window.axios.get(route('cliente.pedidos'));
        misPedidos.value = resp.data.data || [];
        misPedidosPagination.value = {
            currentPage: resp.data.current_page,
            lastPage: resp.data.last_page,
            links: resp.data.links || [],
        };
    } catch {
        misPedidos.value = [];
        misPedidosPagination.value = null;
    } finally {
        misPedidosLoading.value = false;
    }
};

const irPaginaMisPedidos = async (url) => {
    if (!url) return;
    misPedidosLoading.value = true;
    try {
        const resp = await window.axios.get(url);
        misPedidos.value = resp.data.data || [];
        misPedidosPagination.value = {
            currentPage: resp.data.current_page,
            lastPage: resp.data.last_page,
            links: resp.data.links || [],
        };
    } catch {
        misPedidos.value = [];
    } finally {
        misPedidosLoading.value = false;
    }
};

const abrirDetallePedido = (p) => {
    selectedPedido.value = p;
    showDetallePedidoModal.value = true;
};

const completarPedido = async (ventaId) => {
    try {
        await window.axios.put(route('cliente.pedidos.completar', ventaId));
        cargarMisPedidos();
    } catch {
        // silent
    }
};

watch(activeTab, (val) => {
    if (val === 'pedidos') cargarMisPedidos();
    if (val === 'comprobantes') cargarComprobantes();
});

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
                    <span>Carrito</span>
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
                        { key: 'comprobantes', label: 'Mis Comprobantes' },
                        { key: 'pedidos', label: 'Mis Pedidos' },
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
                    <h3 class="text-lg font-bold text-[var(--text-primary)] mb-2">No tienes créditos pendientes</h3>
                    <p class="text-sm">Todas tus cuotas están al día.</p>
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
            <!-- Tab: Mis Comprobantes -->
            <div v-if="activeTab === 'comprobantes'" class="space-y-4">
                <div class="flex items-end gap-4 flex-wrap rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-white/60">Desde</label>
                        <input v-model="comprobantesFrom" type="date" class="rounded-lg border-white/10 bg-slate-900 text-sm text-white px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-white/60">Hasta</label>
                        <input v-model="comprobantesTo" type="date" class="rounded-lg border-white/10 bg-slate-900 text-sm text-white px-3 py-2">
                    </div>
                    <button
                        class="bg-indigo-500 hover:bg-indigo-600 text-white text-xs font-bold px-4 py-2 rounded-lg transition cursor-pointer"
                        @click="cargarComprobantes"
                        :disabled="comprobantesLoading"
                    >
                        {{ comprobantesLoading ? 'Cargando...' : 'Filtrar' }}
                    </button>
                    <div class="ml-auto">
                        <ReportModal module="cliente_comprobantes" :filters="{ fecha_inicio: comprobantesFrom, fecha_fin: comprobantesTo }" />
                    </div>
                </div>

                <div v-if="comprobantesLoading" class="text-center py-12 text-white/60">
                    <svg class="animate-spin h-8 w-8 mx-auto text-indigo-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <p class="mt-2 text-sm">Cargando comprobantes...</p>
                </div>

                <div v-else-if="comprobantes.length === 0" class="text-center py-16 text-white/50">
                    <h3 class="text-lg font-bold text-white mb-2">No hay comprobantes</h3>
                    <p class="text-sm">No se encontraron compras en este rango de fechas.</p>
                </div>

                <div v-else class="overflow-x-auto rounded-2xl border border-white/10">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-white/5 text-white/60">
                                <th class="text-left py-3 px-4 font-semibold">#</th>
                                <th class="text-left py-3 px-4 font-semibold">Fecha</th>
                                <th class="text-right py-3 px-4 font-semibold">Total</th>
                                <th class="text-center py-3 px-4 font-semibold">Pago</th>
                                <th class="text-center py-3 px-4 font-semibold">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="v in comprobantes" :key="v.id" class="border-t border-white/5 hover:bg-white/5">
                                <td class="py-3 px-4 font-mono text-white">{{ v.id }}</td>
                                <td class="py-3 px-4 text-white/60">{{ new Date(v.created_at).toLocaleString() }}</td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-indigo-300">Bs {{ Number(v.monto_final).toFixed(2) }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                        :class="v.tipo_pago === 'credito' ? 'bg-amber-500/10 text-amber-400' : 'bg-emerald-500/10 text-emerald-400'"
                                    >
                                        {{ formatTipoPago(v.tipo_pago) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button
                                        class="text-xs bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded-lg font-semibold transition cursor-pointer"
                                        @click="abrirDetalleComprobante(v)"
                                    >
                                        Ver
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginacion -->
                <div v-if="comprobantesPagination && comprobantesPagination.lastPage > 1" class="flex justify-center gap-2">
                    <template v-for="(link, i) in comprobantesPagination.links" :key="i">
                        <button
                            v-if="link.url"
                            :disabled="link.active"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition"
                            :class="link.active ? 'bg-indigo-500 text-white' : 'text-white/60 hover:bg-white/5'"
                            @click="irPaginaComprobantes(link.url)"
                            v-html="link.label"
                        />
                        <span v-else class="px-3 py-1.5 text-sm text-white/40" v-html="link.label" />
                    </template>
                </div>
            </div>

            <!-- Tab: Mis Pedidos -->
            <div v-if="activeTab === 'pedidos'" class="space-y-4">
                <div v-if="misPedidosLoading" class="text-center py-12 text-white/60">
                    <svg class="animate-spin h-8 w-8 mx-auto text-indigo-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <p class="mt-2 text-sm">Cargando pedidos...</p>
                </div>

                <div v-else-if="misPedidos.length === 0" class="text-center py-16 text-white/50">
                    <h3 class="text-lg font-bold text-white mb-2">No tienes pedidos activos</h3>
                    <p class="text-sm">Tus pedidos aparecerán aquí después de comprar.</p>
                </div>

                <div v-else class="space-y-3">
                    <div v-for="p in misPedidos" :key="p.id" class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="text-sm font-bold text-white">Pedido #{{ p.id }}</h4>
                                <p class="text-xs text-white/60">{{ new Date(p.created_at).toLocaleString() }}</p>
                            </div>
                            <div class="text-right">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="{
                                        'bg-emerald-500/10 text-emerald-400': p.estado_pedido === 'pagado',
                                        'bg-sky-500/10 text-sky-400': p.estado_pedido === 'enviado',
                                    }"
                                >
                                    {{ formatEstadoPedido(p.estado_pedido) }}
                                </span>
                            </div>
                        </div>

                        <table class="w-full text-xs mb-3">
                            <thead>
                                <tr class="text-white/60 border-b border-white/5">
                                    <th class="text-left py-2">Producto</th>
                                    <th class="text-center py-2">Cant</th>
                                    <th class="text-right py-2">P.U.</th>
                                    <th class="text-right py-2">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="d in p.detalle_ventas" :key="d.id" class="border-b border-white/5">
                                    <td class="py-2 text-white">{{ d.producto?.nombre || '—' }}</td>
                                    <td class="py-2 text-center text-white/60">{{ d.cantidad }}</td>
                                    <td class="py-2 text-right font-mono text-white/60">Bs {{ Number(d.precio_u_final).toFixed(2) }}</td>
                                    <td class="py-2 text-right font-mono text-white">Bs {{ Number(d.subtotal).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="flex justify-between items-center border-t border-white/5 pt-3">
                            <div class="text-sm font-bold text-indigo-300">Bs {{ Number(p.monto_final).toFixed(2) }}</div>
                            <div class="flex gap-2">
                                <button
                                    class="text-xs bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded-lg font-semibold transition cursor-pointer"
                                    @click="abrirDetallePedido(p)"
                                >
                                    Ver
                                </button>
                                <button
                                    v-if="p.estado_pedido === 'enviado'"
                                    class="text-xs bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1.5 rounded-lg font-semibold transition cursor-pointer"
                                    @click="completarPedido(p.id)"
                                >
                                    Recibido
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="misPedidosPagination && misPedidosPagination.lastPage > 1" class="flex justify-center gap-2">
                    <template v-for="(link, i) in misPedidosPagination.links" :key="i">
                        <button
                            v-if="link.url"
                            :disabled="link.active"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition"
                            :class="link.active ? 'bg-indigo-500 text-white' : 'text-white/60 hover:bg-white/5'"
                            @click="irPaginaMisPedidos(link.url)"
                            v-html="link.label"
                        />
                        <span v-else class="px-3 py-1.5 text-sm text-white/40" v-html="link.label" />
                    </template>
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
                                    <input
                                        type="number"
                                        min="1"
                                        :max="item.stock"
                                        :value="item.cantidad"
                                        class="w-12 text-center bg-white/5 border border-white/10 rounded px-1 py-0.5 text-xs font-semibold text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        @input="setQty(item, $event.target.value, $event)"
                                    />
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
                            Concretar Compra
                        </button>
                    </div>

                </div>
            </div>
        </transition>

        <!-- MODAL: SELECCIÓN DE MÉTODO DE PAGO -->
        <DialogModal :show="showPaymentMethodModal" @close="showPaymentMethodModal = false; stockError = ''">
            <template #title>
                <h3 class="text-lg font-bold text-[var(--text-primary)]">Selecciona método de pago</h3>
            </template>

            <template #content>
                <div class="grid grid-cols-3 gap-4 py-4">
                    <button
                        class="flex flex-col items-center gap-3 p-6 rounded-2xl border-2 transition cursor-pointer"
                        :class="saleForm.tipo_pago === 'qr'
                            ? 'border-indigo-400 bg-indigo-500/10'
                            : 'border-white/10 bg-white/5 hover:border-indigo-400/50 hover:bg-indigo-500/5'"
                        @click="saleForm.tipo_pago = 'qr'"
                    >
                        <svg class="w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-6 15h9M7.5 19.5h9" />
                        </svg>
                        <span class="font-bold text-sm text-[var(--text-primary)]">Código QR</span>
                        <span class="text-[10px] text-[var(--text-secondary)]">Paga con tu app bancaria</span>
                    </button>
                    <button
                        class="flex flex-col items-center gap-3 p-6 rounded-2xl border-2 transition cursor-pointer"
                        :class="saleForm.tipo_pago === 'tarjeta'
                            ? 'border-indigo-400 bg-indigo-500/10'
                            : 'border-white/10 bg-white/5 hover:border-indigo-400/50 hover:bg-indigo-500/5'"
                        @click="saleForm.tipo_pago = 'tarjeta'"
                    >
                        <svg class="w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-5.25-6h15a2.25 2.25 0 012.25 2.25v9a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25v-9a2.25 2.25 0 012.25-2.25z" />
                        </svg>
                        <span class="font-bold text-sm text-[var(--text-primary)]">Tarjeta</span>
                        <span class="text-[10px] text-[var(--text-secondary)]">Débito o Crédito</span>
                    </button>
                    <button
                        class="flex flex-col items-center gap-3 p-6 rounded-2xl border-2 transition cursor-pointer"
                        :class="saleForm.tipo_pago === 'credito'
                            ? 'border-indigo-400 bg-indigo-500/10'
                            : 'border-white/10 bg-white/5 hover:border-indigo-400/50 hover:bg-indigo-500/5'"
                        @click="saleForm.tipo_pago = 'credito'"
                    >
                        <svg class="w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                        <span class="font-bold text-sm text-[var(--text-primary)]">Crédito / Cuotas</span>
                        <span class="text-[10px] text-[var(--text-secondary)]">Paga en cuotas</span>
                    </button>
                </div>

                <div v-if="saleForm.tipo_pago === 'credito'" class="mt-2 space-y-3">
                    <div>
                        <InputLabel value="Nro. Cuotas" class="text-xs text-white/70" />
                        <select
                            v-model="saleForm.nro_cuotas"
                            class="mt-1 block w-full rounded-xl border-white/10 bg-slate-900 text-white focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm p-2 transition"
                        >
                            <option value="2">2 cuotas</option>
                            <option value="3">3 cuotas</option>
                            <option value="4">4 cuotas</option>
                            <option value="6">6 cuotas</option>
                            <option value="12">12 cuotas</option>
                        </select>
                    </div>
                    <div class="p-3 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-sm text-indigo-300 text-center">
                        <span class="font-bold">{{ saleForm.nro_cuotas }} cuotas</span> de <strong class="text-white">Bs {{ quotaAmount.toFixed(2) }}</strong> cada una
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="space-y-2">
                    <div v-if="stockError" class="text-xs text-rose-400 text-center bg-rose-500/10 rounded-xl px-3 py-2">
                        {{ stockError }}
                    </div>
                    <div class="flex justify-end gap-2">
                        <SecondaryButton @click="showPaymentMethodModal = false; stockError = ''">Cancelar</SecondaryButton>
                        <PrimaryButton
                            :disabled="!saleForm.tipo_pago || (saleForm.tipo_pago === 'credito' && saleForm.nro_cuotas < 2) || stockLoading"
                            @click="continuarPago"
                        >
                            {{ stockLoading ? 'Validando...' : 'Continuar' }}
                        </PrimaryButton>
                    </div>
                </div>
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
                    <span>Compra Exitosa</span>
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
                            { value: 'qr', label: 'QR' },
                            { value: 'tarjeta', label: 'Tarjeta' },
                        ]"
                        :key="opt.value"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition border cursor-pointer"
                        :class="pagoCuotaMethod === opt.value
                            ? 'bg-indigo-500/20 border-indigo-400/40 text-indigo-300'
                            : 'bg-white/5 border-white/10 text-white/70 hover:bg-white/10'"
                        @click="pagoCuotaMethod = opt.value; pagoCuotaQrImage = null; pagoCuotaQrTransactionId = null; pagoCuotaQrError = ''; showPagoCuotaQrError = false"
                    >
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

        <!-- MODAL: DETALLE COMPROBANTE -->
        <DialogModal :show="showDetalleComprobanteModal" max-width="lg" @close="showDetalleComprobanteModal = false">
            <template #title>
                <div class="flex items-center gap-2 text-white">
                    <span>Factura #{{ selectedComprobante?.id }}</span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        :class="selectedComprobante?.tipo_pago === 'credito' ? 'bg-amber-500/10 text-amber-400' : 'bg-emerald-500/10 text-emerald-400'"
                    >
                        {{ formatTipoPago(selectedComprobante?.tipo_pago) }}
                    </span>
                </div>
            </template>
            <template #content>
                <div v-if="selectedComprobante" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-white/60">Fecha:</span>
                            <p class="font-medium text-white">{{ new Date(selectedComprobante.created_at).toLocaleString() }}</p>
                        </div>
                        <div>
                            <span class="text-white/60">Total:</span>
                            <p class="font-medium text-indigo-300">Bs {{ Number(selectedComprobante.monto_final).toFixed(2) }}</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-semibold uppercase text-white/60 mb-2">Productos</h4>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-white/10 text-white/60">
                                    <th class="text-left py-2 font-semibold">Producto</th>
                                    <th class="text-center py-2 font-semibold">Cant</th>
                                    <th class="text-right py-2 font-semibold">P.U.</th>
                                    <th class="text-right py-2 font-semibold">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="d in selectedComprobante.detalle_ventas" :key="d.id" class="border-b border-white/5">
                                    <td class="py-2 text-white">{{ d.producto?.nombre || '—' }}</td>
                                    <td class="py-2 text-center text-white/60">{{ d.cantidad }}</td>
                                    <td class="py-2 text-right font-mono text-white/60">Bs {{ Number(d.precio_u_final).toFixed(2) }}</td>
                                    <td class="py-2 text-right font-mono text-white">Bs {{ Number(d.subtotal).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="rounded-lg bg-white/5 p-3 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-white/60">Subtotal</span>
                            <span class="font-mono text-white">Bs {{ Number(selectedComprobante.monto_original).toFixed(2) }}</span>
                        </div>
                        <div v-if="selectedComprobante.cod_descuento" class="flex justify-between">
                            <span class="text-white/60">Descuento ({{ selectedComprobante.cod_descuento }})</span>
                            <span class="font-mono text-emerald-400">−Bs {{ (Number(selectedComprobante.monto_original) - Number(selectedComprobante.monto_final)).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-white/10 pt-1 text-base font-bold">
                            <span class="text-white">Total</span>
                            <span class="font-mono text-indigo-300">Bs {{ Number(selectedComprobante.monto_final).toFixed(2) }}</span>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-semibold uppercase text-white/60 mb-2">Metodos de pago</h4>
                        <div class="space-y-1">
                            <div v-for="mp in selectedComprobante.metodo_pagos" :key="mp.id" class="flex justify-between text-sm">
                                <span class="text-white">{{ formatTipoPago(mp.tipo_pago) }}</span>
                                <span class="font-mono text-white">Bs {{ Number(mp.monto || 0).toFixed(2) }}</span>
                            </div>
                            <div v-if="!selectedComprobante.metodo_pagos?.length" class="flex justify-between text-sm">
                                <span class="text-white">{{ formatTipoPago(selectedComprobante.tipo_pago) }}</span>
                                <span class="font-mono text-white">Bs {{ Number(selectedComprobante.monto_final).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedComprobante.tipo_pago === 'credito' && selectedComprobante.venta_cuotas?.length">
                        <h4 class="text-xs font-semibold uppercase text-white/60 mb-2">Cuotas</h4>
                        <div class="space-y-1">
                            <div v-for="cu in selectedComprobante.venta_cuotas" :key="cu.id" class="flex justify-between text-sm">
                                <span class="text-white">Cuota #{{ cu.nro_cuota }}</span>
                                <span class="font-mono text-white">Bs {{ Number(cu.sub_monto).toFixed(2) }}</span>
                                <span :class="cu.estado === 'pagado' ? 'text-emerald-400' : 'text-amber-400'">{{ cu.estado === 'pagado' ? 'Pagado' : 'Pendiente' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-between w-full gap-2">
                    <SecondaryButton @click="showDetalleComprobanteModal = false">Cerrar</SecondaryButton>
                    <a v-if="selectedComprobante?.id"
                        :href="route('descargas.venta.pdf', { venta: selectedComprobante.id })"
                        target="_blank"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest active:opacity-80 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition ease-in-out duration-150 shadow-md cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        Imprimir PDF
                    </a>
                </div>
            </template>
        </DialogModal>

        <!-- MODAL: DETALLE PEDIDO -->
        <DialogModal :show="showDetallePedidoModal" max-width="lg" @close="showDetallePedidoModal = false">
            <template #title>
                <div class="flex items-center gap-2 text-white">
                    <span>Pedido #{{ selectedPedido?.id }}</span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        :class="{
                            'bg-emerald-500/10 text-emerald-400': selectedPedido?.estado_pedido === 'pagado',
                            'bg-sky-500/10 text-sky-400': selectedPedido?.estado_pedido === 'enviado',
                        }"
                    >
                        {{ formatEstadoPedido(selectedPedido?.estado_pedido) }}
                    </span>
                </div>
            </template>
            <template #content>
                <div v-if="selectedPedido" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-white/60">Fecha:</span>
                            <p class="font-medium text-white">{{ new Date(selectedPedido.created_at).toLocaleString() }}</p>
                        </div>
                        <div>
                            <span class="text-white/60">Total:</span>
                            <p class="font-medium text-indigo-300">Bs {{ Number(selectedPedido.monto_final).toFixed(2) }}</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-semibold uppercase text-white/60 mb-2">Productos</h4>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-white/10 text-white/60">
                                    <th class="text-left py-2 font-semibold">Producto</th>
                                    <th class="text-center py-2 font-semibold">Cant</th>
                                    <th class="text-right py-2 font-semibold">P.U.</th>
                                    <th class="text-right py-2 font-semibold">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="d in selectedPedido.detalle_ventas" :key="d.id" class="border-b border-white/5">
                                    <td class="py-2 text-white">{{ d.producto?.nombre || '—' }}</td>
                                    <td class="py-2 text-center text-white/60">{{ d.cantidad }}</td>
                                    <td class="py-2 text-right font-mono text-white/60">Bs {{ Number(d.precio_u_final).toFixed(2) }}</td>
                                    <td class="py-2 text-right font-mono text-white">Bs {{ Number(d.subtotal).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="rounded-lg bg-white/5 p-3 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-white/60">Total</span>
                            <span class="font-mono text-indigo-300 font-bold">Bs {{ Number(selectedPedido.monto_final).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="showDetallePedidoModal = false">Cerrar</SecondaryButton>
                    <PrimaryButton
                        v-if="selectedPedido?.estado_pedido === 'enviado'"
                        @click="completarPedido(selectedPedido.id); showDetallePedidoModal = false"
                    >
                        Marcar como Recibido
                    </PrimaryButton>
                </div>
            </template>
        </DialogModal>
    </AppLayout>
</template>

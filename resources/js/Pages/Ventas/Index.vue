<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, Head, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import BarcodeScannerModal from '@/Pages/Productos/Partials/BarcodeScannerModal.vue';
import CreditCardForm from '@/Components/CreditCardForm.vue';

const props = defineProps({
    cajaActiva: Object,
    productos: Array,
    promocionesActive: Array,
    clientes: Array,
});

const page = usePage();
const userRoles = computed(() => page.props.auth.roles ?? []);

const clienteSearch = ref('');
const showClientDropdown = ref(false);
const selectedClient = ref(null);

const productSearch = ref('');
const showProductDropdown = ref(false);
const showBarcodeScanner = ref(false);

const cart = ref([]);
const montoRecibido = ref('');
const promoCode = ref('');
const promoApplied = ref(null);
const promoError = ref('');

const methodsConfig = [
    { tipo: 'efectivo', label: 'Efectivo', icon: '' },
    { tipo: 'qr', label: 'QR', icon: '' },
    { tipo: 'tarjeta', label: 'Tarjeta', icon: '' },
    { tipo: 'credito', label: 'Credito', icon: '' },
];

const paymentMethods = ref(
    methodsConfig.map(m => ({ ...m, monto: 0, enabled: false, pagado: m.tipo === 'efectivo' }))
);

const activeMethods = computed(() => paymentMethods.value.filter(m => m.enabled));

const totalAsignado = computed(() =>
    paymentMethods.value.reduce((s, m) => s + (parseFloat(m.monto) || 0), 0)
);

const asignadoOk = computed(() =>
    Math.abs(totalAsignado.value - totalFinal.value) < 0.01
);

const nroCuotas = ref(2);

// QR state
const qrImage = ref(null);
const qrLoading = ref(false);
const qrTransactionId = ref(null);
const qrFormat = ref('png');
const qrChecking = ref(false);
const qrCheckStatus = ref('');

// Card state
const cardData = ref({ number: '', expiry: '', cvc: '' });

const showConfirmModal = ref(false);
const showNewClientModal = ref(false);
const showSuccessModal = ref(false);
const showQrErrorModal = ref(false);
const qrErrorMessage = ref('');
const lastSale = ref(null);
const submitting = ref(false);
const processingQr = ref(false);
const processingCard = ref(false);

const activeVentaTab = ref('detalle');

// Comprobantes state
const comprobantes = ref([]);
const comprobantesPagination = ref(null);
const comprobantesLoading = ref(false);
const comprobantesFrom = ref(new Date().toISOString().split('T')[0]);
const comprobantesTo = ref(new Date().toISOString().split('T')[0]);
const showDetalleVentaModal = ref(false);
const selectedVenta = ref(null);

// Pedidos state
const pedidos = ref([]);
const pedidosPagination = ref(null);
const pedidosLoading = ref(false);
const pedidosFrom = ref(new Date().toISOString().split('T')[0]);
const pedidosTo = ref(new Date().toISOString().split('T')[0]);
const showDetallePedidoModal = ref(false);
const selectedPedido = ref(null);

function formatTipoPago(tipo) {
    const map = { efectivo: 'Efectivo', qr: 'QR', tarjeta: 'Tarjeta', credito: 'Credito', compra_directa: 'Directo', mixto: 'Mixto' };
    return map[tipo] || tipo;
}

function formatEstadoPedido(estado) {
    const map = { pagado: 'Pagado', enviado: 'Enviado', completado: 'Completado' };
    return map[estado] || estado;
}

const cargarComprobantes = async () => {
    comprobantesLoading.value = true;
    try {
        const resp = await window.axios.get(route('comprobantes.index'), {
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

const cargarPedidos = async () => {
    pedidosLoading.value = true;
    try {
        const resp = await window.axios.get(route('ventas.pedidos'), {
            params: { from: pedidosFrom.value, to: pedidosTo.value },
        });
        pedidos.value = resp.data.data || [];
        pedidosPagination.value = {
            currentPage: resp.data.current_page,
            lastPage: resp.data.last_page,
            links: resp.data.links || [],
        };
    } catch {
        pedidos.value = [];
        pedidosPagination.value = null;
    } finally {
        pedidosLoading.value = false;
    }
};

const irPaginaPedidos = async (url) => {
    if (!url) return;
    pedidosLoading.value = true;
    try {
        const resp = await window.axios.get(url);
        pedidos.value = resp.data.data || [];
        pedidosPagination.value = {
            currentPage: resp.data.current_page,
            lastPage: resp.data.last_page,
            links: resp.data.links || [],
        };
    } catch {
        pedidos.value = [];
    } finally {
        pedidosLoading.value = false;
    }
};

const marcarEnviado = (ventaId) => {
    router.put(route('ventas.pedidos.estado', ventaId), { estado: 'enviado' }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => cargarPedidos(),
    });
};

onMounted(() => {
    cargarComprobantes();
    cargarPedidos();
});

const quickClientForm = useForm({
    name: '',
    ci: '',
    email: '',
    phone: '',
    password: '',
});

const filteredClientes = computed(() => {
    if (!clienteSearch.value.trim()) return [];
    const term = clienteSearch.value.toLowerCase();
    return props.clientes.filter(c =>
        c.name?.toLowerCase().includes(term) ||
        c.ci?.toLowerCase().includes(term) ||
        c.email?.toLowerCase().includes(term)
    );
});

const filteredProductos = computed(() => {
    const term = productSearch.value.trim();
    if (term.length < 3) return [];
    const lower = term.toLowerCase();
    return props.productos.filter(p =>
        p.nombre.toLowerCase().includes(lower) ||
        p.codigo_barra?.toLowerCase().includes(lower)
    );
});

const cartProductIds = computed(() => new Set(cart.value.map(i => i.producto_id)));

const total = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.subtotal, 0);
});

const descuentoMonto = computed(() => {
    if (!promoApplied.value) return 0;
    const promo = promoApplied.value;
    if (promo.tipo_descuento === 'porcentaje') {
        return total.value * (promo.descuento / 100);
    }
    return Math.min(promo.descuento, total.value);
});

const totalFinal = computed(() => Math.max(0, total.value - descuentoMonto.value));

const cuotaMonto = computed(() => totalFinal.value / nroCuotas.value);

const canFinalizar = computed(() => {
    if (cart.value.length === 0) return false;
    if (!selectedClient.value) return false;
    if (activeMethods.value.length === 0) return false;

    const credito = paymentMethods.value.find(p => p.tipo === 'credito');
    if (credito?.enabled) {
        return nroCuotas.value >= 2;
    }

    if (!asignadoOk.value) return false;
    if (activeMethods.value.some(m => !m.pagado)) return false;
    return true;
});

const metodosActivos = computed(() => {
    const creditoMethod = paymentMethods.value.find(p => p.tipo === 'credito' && p.enabled);
    if (creditoMethod) {
        return [{ tipo: 'credito', label: 'Credito', monto: totalFinal.value, pagado: true }];
    }
    return paymentMethods.value.filter(m => m.enabled && (parseFloat(m.monto) || 0) > 0);
});

const sumaMetodos = computed(() =>
    paymentMethods.value.reduce((s, m) => s + (parseFloat(m.monto) || 0), 0)
);

const totalEfectivoRestante = computed(() => {
    const ef = paymentMethods.value.find(m => m.tipo === 'efectivo');
    return parseFloat(ef?.monto) || 0;
});

function getMethod(tipo) {
    return paymentMethods.value.find(p => p.tipo === tipo);
}

function getMethodEnabled(tipo) {
    return paymentMethods.value.find(p => p.tipo === tipo)?.enabled ?? false;
}

function getMethodPagado(tipo) {
    return paymentMethods.value.find(p => p.tipo === tipo)?.pagado ?? false;
}

function selectClient(client) {
    selectedClient.value = client;
    clienteSearch.value = client.name;
    showClientDropdown.value = false;
}

function clearClient() {
    selectedClient.value = null;
    clienteSearch.value = '';
}

function addProduct(producto) {
    if (cartProductIds.value.has(producto.id)) return;
    cart.value.push({
        producto_id: producto.id,
        nombre: producto.nombre,
        mililitros: producto.mililitros,
        imagen: producto.imagen,
        precio: parseFloat(producto.precio_venta),
        cantidad: 1,
        subtotal: parseFloat(producto.precio_venta),
        stock: producto.stock_actual?.stock ?? 0,
    });
    productSearch.value = '';
    showProductDropdown.value = false;
}

function updateQty(productoId, delta) {
    const item = cart.value.find(i => i.producto_id === productoId);
    if (!item) return;
    const newQty = item.cantidad + delta;
    if (newQty < 1) {
        removeFromCart(productoId);
        return;
    }
    if (newQty > item.stock) return;
    item.cantidad = newQty;
    item.subtotal = newQty * item.precio;
}

function removeFromCart(productoId) {
    cart.value = cart.value.filter(i => i.producto_id !== productoId);
    promoApplied.value = null;
    promoError.value = '';
}

function aplicarPromo() {
    promoError.value = '';
    if (!promoCode.value.trim()) return;
    const code = promoCode.value.trim().toUpperCase();
    const promo = props.promocionesActive.find(p => p.codigo_promo?.toUpperCase() === code);
    if (!promo) {
        promoError.value = 'Codigo de promocion no valido.';
        promoApplied.value = null;
        return;
    }
    promoApplied.value = promo;
}

function handleBarcodeScanned(code) {
    const producto = props.productos.find(p =>
        p.codigo_barra === code || p.codigo_qr === code
    );
    if (producto) {
        addProduct(producto);
    }
}

function toggleMetodo(tipo) {
    const m = paymentMethods.value.find(p => p.tipo === tipo);
    if (!m) return;

    if (tipo === 'credito') {
        m.enabled = !m.enabled;
        if (m.enabled) {
            paymentMethods.value.forEach(p => {
                if (p.tipo !== 'credito') p.enabled = false;
            });
        }
        return;
    }

    m.enabled = !m.enabled;
    if (!m.enabled) {
        m.monto = 0;
        m.pagado = false;
        if (tipo === 'qr') {
            qrImage.value = null;
            qrTransactionId.value = null;
        }
    } else {
        const credito = paymentMethods.value.find(p => p.tipo === 'credito');
        if (credito) credito.enabled = false;
        m.pagado = tipo === 'efectivo';
    }
}

async function handlePagar() {
    if (!canFinalizar.value) return;
    showConfirmModal.value = true;
}

async function handleQRForMethod() {
    const m = paymentMethods.value.find(p => p.tipo === 'qr');
    if (!m || !m.enabled) return;
    qrLoading.value = true;
    processingQr.value = true;
    try {
        const details = cart.value.map((item, i) => ({
            serial: i + 1,
            product: item.nombre,
            quantity: item.cantidad,
            price: item.precio,
            discount: 0,
            total: item.subtotal,
        }));
        const resp = await window.axios.post(route('pago.qr.generar'), {
            monto: parseFloat(m.monto || totalFinal.value),
            orderDetail: details,
        });
        if (resp.data?.error === false && resp.data?.data?.qrBase64) {
            qrImage.value = resp.data.data.qrBase64;
            qrTransactionId.value = resp.data.data.transactionId ?? null;
            qrFormat.value = resp.data.data.qrFormat || 'png';
        } else {
            qrErrorMessage.value = resp.data?.message || 'Error al generar el codigo QR. Intente nuevamente.';
            showQrErrorModal.value = true;
        }
    } catch {
        qrErrorMessage.value = 'Error de conexion al generar el codigo QR.';
        showQrErrorModal.value = true;
    } finally {
        qrLoading.value = false;
        processingQr.value = false;
    }
}

async function handleCheckQRPayment() {
    if (!qrTransactionId.value) return;

    if (String(qrTransactionId.value).startsWith('local_')) {
        const m = paymentMethods.value.find(p => p.tipo === 'qr');
        if (m) m.pagado = true;
        return;
    }

    qrChecking.value = true;
    qrCheckStatus.value = 'Verificando pago...';
    const terminalErrorStates = ['Cancelado', 'Rechazado', 'Fallido', 'Expirado', 'Anulado'];

    try {
        const resp = await window.axios.post(route('pago.qr.checkStatus'), {
            transactionId: qrTransactionId.value,
        });
        if (resp.data?.error === false && resp.data?.data) {
            const statusDesc = resp.data.data.paymentStatusDescription || '';
            const statusCode = resp.data.data.paymentStatus;

            if (statusDesc === 'Pagado' || statusCode === 2) {
                const m = paymentMethods.value.find(p => p.tipo === 'qr');
                if (m) m.pagado = true;
                qrCheckStatus.value = '';
                qrChecking.value = false;
                return;
            }

            if (terminalErrorStates.includes(statusDesc)) {
                qrErrorMessage.value = `El pago fue ${statusDesc.toLowerCase()} por el cliente.`;
                showQrErrorModal.value = true;
                qrChecking.value = false;
                return;
            }

            qrCheckStatus.value = 'Aún no pagado. Escanea el QR e intenta de nuevo.';
        } else {
            qrCheckStatus.value = 'Error al consultar el pago.';
        }
    } catch {
        qrCheckStatus.value = 'Error de conexión.';
    }
}

async function handleCardForMethod() {
    processingCard.value = true;
    const m = paymentMethods.value.find(p => p.tipo === 'tarjeta');
    if (!m) { processingCard.value = false; return; }
    await new Promise(r => setTimeout(r, 500));
    m.pagado = true;
    processingCard.value = false;
}

function submitSale() {
    if (submitting.value) return;
    submitting.value = true;

    const creditoMethod = paymentMethods.value.find(p => p.tipo === 'credito' && p.enabled);

    if (creditoMethod) {
        const saleForm = useForm({
            tipo_pago: 'credito',
            monto_pagado: totalFinal.value,
            payment_methods: [{ tipo_pago: 'credito', monto: totalFinal.value }],
            cliente_id: selectedClient.value?.id,
            detalles: cart.value.map(i => ({
                producto_id: i.producto_id,
                cantidad: i.cantidad,
            })),
            codigo_promo: promoApplied.value ? promoApplied.value.codigo_promo : null,
            nro_cuotas: nroCuotas.value,
            card_number: null,
            card_expiry: null,
            card_cvc: null,
            qr_transaction_id: null,
        });

        saleForm.post(route('ventas.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showConfirmModal.value = false;
                lastSale.value = {
                    cliente: selectedClient.value,
                    detalles: [...cart.value],
                    total: totalFinal.value,
                    tipo_pago: 'credito',
                    payment_methods: [{ tipo: 'credito', label: 'Credito', monto: totalFinal.value, pagado: true }],
                    promo: promoApplied.value,
                    nro_cuotas: nroCuotas.value,
                };
                showSuccessModal.value = true;
                resetForm();
            },
            onError: (errors) => {
                submitting.value = false;
            },
            onFinish: () => {
                submitting.value = false;
            },
        });
        return;
    }

    const active = paymentMethods.value.filter(p => p.enabled && (parseFloat(p.monto) || 0) > 0);
    const singleTipo = active.length === 1 ? active[0].tipo : 'mixto';

    const saleForm = useForm({
        tipo_pago: singleTipo,
        monto_pagado: totalFinal.value,
        payment_methods: active.map(p => ({ tipo_pago: p.tipo, monto: parseFloat(p.monto) })),
        cliente_id: selectedClient.value?.id,
        detalles: cart.value.map(i => ({
            producto_id: i.producto_id,
            cantidad: i.cantidad,
        })),
        codigo_promo: promoApplied.value ? promoApplied.value.codigo_promo : null,
        nro_cuotas: null,
        card_number: active.some(p => p.tipo === 'tarjeta') ? cardData.value.number : null,
        card_expiry: active.some(p => p.tipo === 'tarjeta') ? cardData.value.expiry : null,
        card_cvc: active.some(p => p.tipo === 'tarjeta') ? cardData.value.cvc : null,
        qr_transaction_id: qrTransactionId.value,
    });

    saleForm.post(route('ventas.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmModal.value = false;
            lastSale.value = {
                cliente: selectedClient.value,
                detalles: [...cart.value],
                total: totalFinal.value,
                tipo_pago: singleTipo,
                payment_methods: active.map(p => ({ ...p })),
                promo: promoApplied.value,
                qrImage: qrImage.value,
            };
            showSuccessModal.value = true;
            resetForm();
        },
        onError: (errors) => {
            submitting.value = false;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
}

function resetForm() {
    cart.value = [];
    selectedClient.value = null;
    clienteSearch.value = '';
    montoRecibido.value = '';
    promoCode.value = '';
    promoApplied.value = null;
    promoError.value = '';
    paymentMethods.value = methodsConfig.map(m => ({ ...m, monto: 0, enabled: false, pagado: m.tipo === 'efectivo' }));
    nroCuotas.value = 2;
    qrImage.value = null;
    qrTransactionId.value = null;
    qrChecking.value = false;
    qrCheckStatus.value = '';
    cardData.value = { number: '', expiry: '', cvc: '' };
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

function closeSuccessModal() {
    showSuccessModal.value = false;
    lastSale.value = null;
}

function openNewClientModal() {
    quickClientForm.reset();
    showNewClientModal.value = true;
}

function saveNewClient() {
    quickClientForm.password = quickClientForm.ci || '123456789';
    quickClientForm.post(route('caja.clientes.rapido'), {
        preserveState: true,
        onSuccess: () => {
            showNewClientModal.value = false;
            router.reload({ only: ['clientes'] });
        },
    });
}

function focusClienteInput(el) {
    el?.focus();
}
</script>

<template>
    <AppLayout title="Ventas">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-amber-200">Ventas</h1>
                <div v-if="cajaActiva" class="text-sm text-emerald-400">
                    Caja #{{ cajaActiva.id }} — {{ cajaActiva.user?.name }}
                </div>
                <div v-else class="text-sm text-red-400">
                    No hay caja abierta
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6 pb-12">
            <!-- Tabs -->
            <div class="flex gap-2 border-b border-stone-700/60 pb-3 mb-6">
                <button
                    v-for="t in [
                        { key: 'detalle', label: 'Detalle de Venta' },
                        { key: 'comprobantes', label: 'Comprobantes' },
                        { key: 'pedidos', label: 'Pedidos' },
                    ]"
                    :key="t.key"
                    class="px-5 py-2.5 text-sm font-bold rounded-xl transition"
                    :class="activeVentaTab === t.key
                        ? 'bg-amber-600 text-white shadow-lg'
                        : 'text-stone-400 hover:bg-stone-800/50'"
                    @click="activeVentaTab = t.key"
                >
                    {{ t.label }}
                </button>
            </div>

            <div v-if="activeVentaTab === 'detalle'">
                <div v-if="!cajaActiva" class="flex items-center justify-center py-20">
                    <p class="text-stone-400">Debe abrir una caja antes de realizar ventas.</p>
                </div>

                <div v-else class="space-y-6">

            <!-- Cliente section -->
            <div class="rounded-lg border border-stone-700/60 bg-stone-800/50 p-4">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-stone-400">Cliente</label>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <TextInput
                            ref="clienteInput"
                            v-model="clienteSearch"
                            type="text"
                            class="w-full"
                            placeholder="Buscar por CI, nombre o email..."
                            @focus="showClientDropdown = true"
                            @blur="setTimeout(() => showClientDropdown = false, 200)"
                            @input="selectedClient = null"
                        />
                        <div
                            v-if="showClientDropdown && filteredClientes.length > 0"
                            class="absolute z-50 mt-1 max-h-48 w-full overflow-y-auto rounded-lg border border-stone-600 bg-stone-800 shadow-lg"
                        >
                            <button
                                v-for="c in filteredClientes"
                                :key="c.id"
                                type="button"
                                class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm text-stone-200 hover:bg-stone-700"
                                @mousedown.prevent="selectClient(c)"
                            >
                                <span class="font-medium">{{ c.name }}</span>
                                <span v-if="c.ci" class="text-xs text-stone-400">CI: {{ c.ci }}</span>
                                <span class="ml-auto text-xs text-stone-500">{{ c.email }}</span>
                            </button>
                        </div>
                        <div
                            v-if="showClientDropdown && clienteSearch && filteredClientes.length === 0"
                            class="absolute z-50 mt-1 w-full rounded-lg border border-stone-600 bg-stone-800 px-3 py-2 text-sm text-stone-400 shadow-lg"
                        >
                            Sin resultados. Cree un cliente nuevo.
                        </div>
                    </div>
                    <SecondaryButton type="button" @click="openNewClientModal">
                        + Nuevo
                    </SecondaryButton>
                </div>
                <div v-if="selectedClient" class="mt-2 flex items-center gap-2 text-sm text-emerald-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ selectedClient.name }}
                    <span v-if="selectedClient.ci" class="text-stone-400">CI: {{ selectedClient.ci }}</span>
                    <button type="button" class="ml-2 text-stone-500 hover:text-red-400" @click="clearClient">Quitar</button>
                </div>
            </div>

            <!-- Product search + cart -->
            <div class="rounded-lg border border-stone-700/60 bg-stone-800/50 p-4">
                <div class="mb-4 flex gap-2">
                    <div class="relative flex-1">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-400">Producto</label>
                        <TextInput
                            v-model="productSearch"
                            type="text"
                            class="w-full"
                            placeholder="Buscar producto (min. 3 caracteres)..."
                            @focus="showProductDropdown = true"
                            @blur="setTimeout(() => showProductDropdown = false, 200)"
                        />
                        <div
                            v-if="showProductDropdown && filteredProductos.length > 0"
                            class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-stone-600 bg-stone-800 shadow-lg"
                        >
                            <button
                                v-for="p in filteredProductos"
                                :key="p.id"
                                type="button"
                                :disabled="cartProductIds.has(p.id)"
                                class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm text-stone-200 hover:bg-stone-700 disabled:opacity-40"
                                @mousedown.prevent="addProduct(p)"
                            >
                                <div class="h-8 w-8 flex-shrink-0 overflow-hidden rounded bg-stone-700">
                                    <img v-if="p.imagen" :src="p.imagen" :alt="p.nombre" class="h-full w-full object-cover">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate font-medium">{{ p.nombre }}</div>
                                    <div class="text-xs text-stone-400">
                                        {{ p.mililitros }}ml
                                        <span v-if="p.stock_actual" class="ml-2">Stock: {{ p.stock_actual.stock }}</span>
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-amber-300">Bs {{ parseFloat(p.precio_venta).toFixed(2) }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-end">
                        <SecondaryButton type="button" @click="showBarcodeScanner = true" title="Escanear codigo">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                            </svg>
                        </SecondaryButton>
                    </div>
                </div>

                <!-- Cart table -->
                <div v-if="cart.length > 0" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-stone-700 text-left text-xs uppercase text-stone-500">
                                <th class="py-2 pr-2">#</th>
                                <th class="py-2 px-2">Producto</th>
                                <th class="py-2 px-2 text-center">Cantidad</th>
                                <th class="py-2 px-2 text-right">P. Unit.</th>
                                <th class="py-2 px-2 text-right">Subtotal</th>
                                <th class="py-2 pl-2 text-right">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in cart" :key="item.producto_id" class="border-b border-stone-700/50">
                                <td class="py-2 pr-2 text-stone-400">{{ idx + 1 }}</td>
                                <td class="max-w-[200px] truncate py-2 px-2 font-medium text-stone-200">
                                    {{ item.nombre }}
                                </td>
                                <td class="py-2 px-2 text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="flex h-6 w-6 items-center justify-center rounded bg-stone-700 text-stone-300 hover:bg-stone-600"
                                            @click="updateQty(item.producto_id, -1)"
                                        >−</button>
                                        <span class="mx-1 min-w-[20px] font-mono text-amber-200">{{ item.cantidad }}</span>
                                        <button
                                            type="button"
                                            class="flex h-6 w-6 items-center justify-center rounded bg-stone-700 text-stone-300 hover:bg-stone-600"
                                            :disabled="item.cantidad >= item.stock"
                                            @click="updateQty(item.producto_id, 1)"
                                        >+</button>
                                    </div>
                                </td>
                                <td class="py-2 px-2 text-right font-mono">Bs {{ item.precio.toFixed(2) }}</td>
                                <td class="py-2 px-2 text-right font-mono font-bold text-amber-200">Bs {{ item.subtotal.toFixed(2) }}</td>
                                <td class="py-2 pl-2 text-right">
                                    <button
                                        type="button"
                                        class="text-stone-500 hover:text-red-400"
                                        @click="removeFromCart(item.producto_id)"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <TextInput
                                v-model="promoCode"
                                type="text"
                                class="w-40"
                                placeholder="Codigo promocional"
                                @keyup.enter="aplicarPromo"
                            />
                            <SecondaryButton type="button" @click="aplicarPromo">Aplicar</SecondaryButton>
                        </div>
                        <div class="text-right">
                            <div v-if="promoApplied" class="text-sm text-emerald-400 mb-1">
                                Descuento: −Bs {{ descuentoMonto.toFixed(2) }}
                            </div>
                            <div class="text-lg font-bold text-amber-200">
                                Total: Bs {{ totalFinal.toFixed(2) }}
                            </div>
                        </div>
                    </div>
                    <p v-if="promoError" class="mt-1 text-sm text-red-400">{{ promoError }}</p>
                </div>

                <div v-else class="py-8 text-center text-stone-500">
                    Agregue productos al carrito para iniciar una venta.
                </div>
            </div>

            <!-- Payment methods -->
            <div v-if="cart.length > 0" class="rounded-lg border border-stone-700/60 bg-stone-800/50 p-4">
                <label class="mb-3 block text-xs font-semibold uppercase tracking-wide text-stone-400">Forma de pago</label>

                <div class="flex flex-wrap gap-2 mb-4">
                    <button
                        v-for="m in methodsConfig"
                        :key="m.tipo"
                        type="button"
                        @click="toggleMetodo(m.tipo)"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition"
                        :class="getMethodEnabled(m.tipo)
                            ? getMethodPagado(m.tipo)
                                ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30 ring-2 ring-emerald-400'
                                : 'bg-amber-600 text-white shadow-lg shadow-amber-600/30'
                            : 'bg-stone-700 text-stone-300 hover:bg-stone-600'"
                    >
                        <span v-if="getMethodPagado(m.tipo)" class="mr-1.5">✓</span>
                        {{ m.label }}
                    </button>
                </div>

                <!-- Efectivo -->
                <div v-if="getMethodEnabled('efectivo')" class="space-y-2 border-t border-stone-700/60 pt-3">
                    <div class="flex items-center gap-4">
                        <div>
                            <label class="mb-1 block text-xs text-stone-400">Monto efectivo</label>
                            <TextInput v-model.number="getMethod('efectivo').monto" type="number" min="0" step="0.01" class="w-40" />
                        </div>
                        <div v-if="getMethodPagado('efectivo') && parseFloat(getMethod('efectivo').monto) > 0" class="flex items-center gap-2 text-sm text-emerald-400 font-semibold mt-5">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Pagado
                        </div>
                    </div>
                    <div v-if="parseFloat(getMethod('efectivo').monto) >= totalEfectivoRestante" class="text-sm text-stone-400">
                        Cambio: <span class="font-mono font-bold text-emerald-400">Bs {{ (parseFloat(getMethod('efectivo').monto) - totalEfectivoRestante).toFixed(2) }}</span>
                    </div>
                </div>

                <!-- QR -->
                <div v-if="getMethodEnabled('qr')" class="space-y-3 border-t border-stone-700/60 pt-3">
                    <div class="flex items-center gap-4">
                        <div>
                            <label class="mb-1 block text-xs text-stone-400">Monto QR</label>
                            <TextInput v-model.number="getMethod('qr').monto" type="number" min="0" step="0.01" class="w-40" />
                        </div>
                        <div v-if="!getMethodPagado('qr') && !qrImage" class="mt-5">
                            <PrimaryButton type="button" :disabled="qrLoading" @click="handleQRForMethod">
                                {{ qrLoading ? 'Generando...' : 'Generar QR' }}
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- QR Image -->
                    <div v-if="qrImage && !getMethodPagado('qr')" class="space-y-3">
                        <div class="flex justify-center">
                            <img :src="`data:image/${qrFormat};base64,${qrImage}`" class="h-48 w-48 rounded-lg border border-white/10" alt="QR">
                        </div>
                        <div v-if="qrChecking" class="flex items-center justify-center gap-2 text-sm text-amber-400">
                            <div class="h-4 w-4 animate-spin rounded-full border-2 border-amber-400 border-t-transparent"></div>
                            {{ qrCheckStatus }}
                        </div>
                        <div v-else class="flex justify-center">
                            <PrimaryButton type="button" :disabled="qrChecking" @click="handleCheckQRPayment">
                                {{ qrChecking ? 'Verificando...' : 'Verificar pago' }}
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- QR Paid status -->
                    <div v-if="getMethodPagado('qr')" class="flex items-center gap-2 text-sm text-emerald-400 font-semibold">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Pagado — Bs {{ (parseFloat(getMethod('qr').monto) || totalFinal).toFixed(2) }}
                    </div>
                </div>

                <!-- Tarjeta -->
                <div v-if="getMethodEnabled('tarjeta')" class="space-y-3 border-t border-stone-700/60 pt-3">
                    <div class="flex items-center gap-4">
                        <div>
                            <label class="mb-1 block text-xs text-stone-400">Monto tarjeta</label>
                            <TextInput v-model.number="getMethod('tarjeta').monto" type="number" min="0" step="0.01" class="w-40" />
                        </div>
                    </div>

                    <div v-if="(parseFloat(getMethod('tarjeta').monto) || 0) > 0 && !getMethodPagado('tarjeta')">
                        <CreditCardForm v-model="cardData" />
                        <div class="mt-3 flex justify-end">
                            <PrimaryButton type="button" :disabled="processingCard || !cardData.number" @click="handleCardForMethod">
                                {{ processingCard ? 'Procesando...' : 'Pagar con tarjeta' }}
                            </PrimaryButton>
                        </div>
                    </div>

                    <div v-if="getMethodPagado('tarjeta')" class="flex items-center gap-2 text-sm text-emerald-400 font-semibold">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Pagado — Bs {{ (parseFloat(getMethod('tarjeta').monto) || totalFinal).toFixed(2) }}
                    </div>
                </div>

                <!-- Credito -->
                <div v-if="getMethodEnabled('credito')" class="space-y-2 border-t border-stone-700/60 pt-3">
                    <div class="flex items-center gap-4">
                        <div>
                            <label class="mb-1 block text-xs text-stone-400">Numero de cuotas</label>
                            <select
                                v-model="nroCuotas"
                                class="rounded-lg border-stone-600 bg-stone-700 text-sm text-stone-200"
                            >
                                <option v-for="n in [2,3,4,6,8,12]" :key="n" :value="n">{{ n }} cuotas</option>
                            </select>
                        </div>
                        <div v-if="nroCuotas >= 2" class="mt-5 text-sm text-stone-400">
                            <span class="font-mono font-bold text-amber-200">Bs {{ cuotaMonto.toFixed(2) }}</span> por cuota
                        </div>
                    </div>
                    <p v-if="!selectedClient" class="text-sm text-amber-400">
                        Debe seleccionar un cliente para ventas a credito.
                    </p>
                </div>

                <!-- Total desglose -->
                <div v-if="metodosActivos.length > 1 || getMethodEnabled('credito')" class="border-t border-stone-700/60 pt-3 text-xs text-stone-400">
                    <div class="space-y-1">
                        <div v-for="m in metodosActivos" :key="m.tipo" class="flex justify-between">
                            <span>{{ m.label }}: Bs {{ (parseFloat(m.monto) || 0).toFixed(2) }}</span>
                            <span v-if="m.pagado" class="text-emerald-400">✓ Pagado</span>
                            <span v-else class="text-amber-400">Pendiente</span>
                        </div>
                        <div class="flex justify-between border-t border-stone-700/30 pt-1 font-semibold text-stone-200">
                            <span>Total</span>
                            <span>{{ sumaMetodos.toFixed(2) }} / {{ totalFinal.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action button -->
                <div class="mt-6 flex justify-end">
                    <PrimaryButton
                        type="button"
                        class="px-8 py-3 text-lg"
                        :disabled="!canFinalizar"
                        @click="handlePagar"
                    >
                        {{ submitting ? 'Procesando...' : 'Finalizar Venta' }}
                    </PrimaryButton>
                </div>
            </div>
        </div>
            </div>

            <div v-if="activeVentaTab === 'comprobantes'" class="space-y-4">
                <div class="flex items-end gap-4 flex-wrap rounded-lg border border-stone-700/60 bg-stone-800/50 p-4">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-stone-400">Desde</label>
                        <input v-model="comprobantesFrom" type="date" class="rounded-lg border-stone-700 bg-stone-800 text-sm text-stone-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-stone-400">Hasta</label>
                        <input v-model="comprobantesTo" type="date" class="rounded-lg border-stone-700 bg-stone-800 text-sm text-stone-200 px-3 py-2">
                    </div>
                    <button class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-4 py-2 rounded-lg transition cursor-pointer" :disabled="comprobantesLoading" @click="cargarComprobantes">
                        {{ comprobantesLoading ? 'Cargando...' : 'Filtrar' }}
                    </button>
                </div>
                <div v-if="comprobantesLoading" class="flex items-center justify-center py-16 text-stone-400">
                    <svg class="animate-spin h-8 w-8 mr-2 text-amber-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Cargando comprobantes...
                </div>
                <div v-else-if="comprobantes.length === 0" class="text-center py-16 text-stone-500">No hay ventas en este rango.</div>
                <div v-else class="overflow-x-auto rounded-lg border border-stone-700/60">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-stone-800/60 text-stone-400">
                                <th class="text-left py-3 px-4 font-semibold">#</th>
                                <th class="text-left py-3 px-4 font-semibold">Fecha</th>
                                <th class="text-left py-3 px-4 font-semibold">Cliente</th>
                                <th class="text-left py-3 px-4 font-semibold">Vendedor</th>
                                <th class="text-right py-3 px-4 font-semibold">Total</th>
                                <th class="text-center py-3 px-4 font-semibold">Pago</th>
                                <th class="text-center py-3 px-4 font-semibold">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="v in comprobantes" :key="v.id" class="border-t border-stone-700/60 hover:bg-stone-800/30">
                                <td class="py-3 px-4 font-mono text-stone-200">{{ v.id }}</td>
                                <td class="py-3 px-4 text-stone-400">{{ new Date(v.created_at).toLocaleString() }}</td>
                                <td class="py-3 px-4 text-stone-200">{{ v.cliente?.name || 'Consumidor Final' }}</td>
                                <td class="py-3 px-4 text-stone-200">{{ v.user?.name || '—' }}</td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-amber-200">Bs {{ Number(v.monto_final).toFixed(2) }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="v.tipo_pago === 'credito' ? 'bg-amber-500/10 text-amber-400' : 'bg-emerald-500/10 text-emerald-400'">{{ formatTipoPago(v.tipo_pago) }}</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button class="text-xs bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 rounded-lg font-semibold transition cursor-pointer" @click="selectedVenta = v; showDetalleVentaModal = true">Ver</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="comprobantesPagination && comprobantesPagination.lastPage > 1" class="flex justify-center gap-2">
                    <template v-for="(link, i) in comprobantesPagination.links" :key="i">
                        <button v-if="link.url" :disabled="link.active" class="px-3 py-1.5 rounded-lg text-sm font-medium transition" :class="link.active ? 'bg-amber-600 text-white' : 'text-stone-400 hover:bg-stone-800/50'" @click="irPaginaComprobantes(link.url)" v-html="link.label" />
                        <span v-else class="px-3 py-1.5 text-sm text-stone-600" v-html="link.label" />
                    </template>
                </div>
            </div>

            <div v-if="activeVentaTab === 'pedidos'" class="space-y-4">
                <div class="flex items-end gap-4 flex-wrap rounded-lg border border-stone-700/60 bg-stone-800/50 p-4">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-stone-400">Desde</label>
                        <input v-model="pedidosFrom" type="date" class="rounded-lg border-stone-700 bg-stone-800 text-sm text-stone-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-stone-400">Hasta</label>
                        <input v-model="pedidosTo" type="date" class="rounded-lg border-stone-700 bg-stone-800 text-sm text-stone-200 px-3 py-2">
                    </div>
                    <button class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-4 py-2 rounded-lg transition cursor-pointer" :disabled="pedidosLoading" @click="cargarPedidos">
                        {{ pedidosLoading ? 'Cargando...' : 'Filtrar' }}
                    </button>
                </div>
                <div v-if="pedidosLoading" class="flex items-center justify-center py-16 text-stone-400">
                    <svg class="animate-spin h-8 w-8 mr-2 text-amber-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Cargando pedidos...
                </div>
                <div v-else-if="pedidos.length === 0" class="text-center py-16 text-stone-500">No hay pedidos en este rango.</div>
                <div v-else class="overflow-x-auto rounded-lg border border-stone-700/60">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-stone-800/60 text-stone-400">
                                <th class="text-left py-3 px-4 font-semibold">#</th>
                                <th class="text-left py-3 px-4 font-semibold">Cliente</th>
                                <th class="text-right py-3 px-4 font-semibold">Total</th>
                                <th class="text-center py-3 px-4 font-semibold">Pago</th>
                                <th class="text-center py-3 px-4 font-semibold">Estado</th>
                                <th class="text-center py-3 px-4 font-semibold">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in pedidos" :key="p.id" class="border-t border-stone-700/60 hover:bg-stone-800/30">
                                <td class="py-3 px-4 font-mono text-stone-200">{{ p.id }}</td>
                                <td class="py-3 px-4 text-stone-200">{{ p.cliente?.name || '—' }}</td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-amber-200">Bs {{ Number(p.monto_final).toFixed(2) }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="p.tipo_pago === 'credito' ? 'bg-amber-500/10 text-amber-400' : 'bg-emerald-500/10 text-emerald-400'">{{ formatTipoPago(p.tipo_pago) }}</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="{ 'bg-emerald-500/10 text-emerald-400': p.estado_pedido === 'pagado', 'bg-sky-500/10 text-sky-400': p.estado_pedido === 'enviado' }">{{ formatEstadoPedido(p.estado_pedido) }}</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button class="text-xs bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 rounded-lg font-semibold transition cursor-pointer" @click="selectedPedido = p; showDetallePedidoModal = true">Ver</button>
                                        <button v-if="p.estado_pedido === 'pagado'" class="text-xs bg-sky-500 hover:bg-sky-600 text-white px-3 py-1.5 rounded-lg font-semibold transition cursor-pointer" @click="marcarEnviado(p.id)">Enviar</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="pedidosPagination && pedidosPagination.lastPage > 1" class="flex justify-center gap-2">
                    <template v-for="(link, i) in pedidosPagination.links" :key="i">
                        <button v-if="link.url" :disabled="link.active" class="px-3 py-1.5 rounded-lg text-sm font-medium transition" :class="link.active ? 'bg-amber-600 text-white' : 'text-stone-400 hover:bg-stone-800/50'" @click="irPaginaPedidos(link.url)" v-html="link.label" />
                        <span v-else class="px-3 py-1.5 text-sm text-stone-600" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <DialogModal :show="showConfirmModal" max-width="lg" scrollable @close="showConfirmModal = false; if (pollingInterval) { clearInterval(pollingInterval); pollingInterval = null; } pollingActive = false">
            <template #title>Confirmar venta</template>
            <template #content>
                <div class="space-y-4">
                    <!-- Cliente -->
                    <div v-if="selectedClient" class="rounded-lg bg-stone-700/50 p-3">
                        <div class="text-xs text-stone-400">Cliente</div>
                        <div class="font-medium text-stone-200">{{ selectedClient.name }}</div>
                        <div v-if="selectedClient.ci" class="text-xs text-stone-400">CI: {{ selectedClient.ci }}</div>
                    </div>

                    <!-- Productos -->
                    <div>
                        <div class="mb-2 text-xs font-semibold uppercase text-stone-400">Productos</div>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-stone-700 text-left text-stone-500">
                                    <th class="py-1 pr-2">Prod.</th>
                                    <th class="py-1 px-2 text-center">Cant</th>
                                    <th class="py-1 px-2 text-right">P.U.</th>
                                    <th class="py-1 pl-2 text-right">Subt.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in cart" :key="item.producto_id" class="border-b border-stone-700/30">
                                    <td class="max-w-[180px] truncate py-1 pr-2 text-stone-200">{{ item.nombre }}</td>
                                    <td class="py-1 px-2 text-center text-stone-300">{{ item.cantidad }}</td>
                                    <td class="py-1 px-2 text-right font-mono text-stone-300">Bs {{ item.precio.toFixed(2) }}</td>
                                    <td class="py-1 pl-2 text-right font-mono text-amber-200">Bs {{ item.subtotal.toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Promo -->
                    <div v-if="promoApplied" class="text-sm text-emerald-400">
                        Descuento ({{ promoApplied.codigo_promo }}): −Bs {{ descuentoMonto.toFixed(2) }}
                    </div>

                    <!-- Totales -->
                    <div class="rounded-lg bg-stone-700/50 p-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-stone-400">Subtotal</span>
                            <span class="font-mono text-stone-200">Bs {{ total.toFixed(2) }}</span>
                        </div>
                        <div v-if="promoApplied" class="flex justify-between text-sm">
                            <span class="text-stone-400">Descuento</span>
                            <span class="font-mono text-emerald-400">−Bs {{ descuentoMonto.toFixed(2) }}</span>
                        </div>
                        <div class="mt-1 flex justify-between border-t border-stone-600 pt-1 text-base font-bold">
                            <span class="text-stone-200">Total</span>
                            <span class="font-mono text-amber-200">Bs {{ totalFinal.toFixed(2) }}</span>
                        </div>
                        <div class="mt-1 text-xs text-stone-400">
                            <div v-for="m in metodosActivos" :key="m.tipo" class="flex justify-between">
                                <span>{{ m.label }}: Bs {{ (parseFloat(m.monto) || 0).toFixed(2) }}</span>
                                <span v-if="m.pagado" class="text-emerald-400">✓ Pagado</span>
                                <span v-else class="text-amber-400">Pendiente</span>
                            </div>
                            <div v-if="getMethodEnabled('credito')" class="text-stone-500">
                                {{ nroCuotas }} cuotas de Bs {{ cuotaMonto.toFixed(2) }} c/u
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="showConfirmModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton
                        type="button"
                        :disabled="submitting"
                        @click="submitSale()"
                    >
                        {{ submitting ? 'Procesando...' : (qrImage ? 'Confirmar venta' : 'Confirmar venta') }}
                    </PrimaryButton>
                </div>
            </template>
        </DialogModal>

        <!-- Success Modal -->
        <DialogModal :show="showSuccessModal" max-width="md" @close="closeSuccessModal">
            <template #title>
                <div class="flex items-center gap-2 text-emerald-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Venta registrada
                </div>
            </template>
            <template #content>
                <div v-if="lastSale" class="space-y-3">
                    <div class="rounded-lg bg-stone-700/50 p-3">
                        <div class="text-xs text-stone-400">Cliente</div>
                        <div class="font-medium text-stone-200">{{ lastSale.cliente?.name || 'Consumidor Final' }}</div>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-stone-700 text-left text-stone-500">
                                <th class="py-1 pr-2">Producto</th>
                                <th class="py-1 px-2 text-center">Cant</th>
                                <th class="py-1 pl-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in lastSale.detalles" :key="item.producto_id" class="border-b border-stone-700/30">
                                <td class="py-1 pr-2 text-stone-200">{{ item.nombre }}</td>
                                <td class="py-1 px-2 text-center text-stone-300">{{ item.cantidad }}</td>
                                <td class="py-1 pl-2 text-right font-mono text-amber-200">Bs {{ item.subtotal.toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="rounded-lg bg-stone-700/50 p-3">
                        <div class="flex justify-between text-base font-bold">
                            <span class="text-stone-200">Total pagado</span>
                            <span class="font-mono text-amber-200">Bs {{ parseFloat(lastSale.total).toFixed(2) }}</span>
                        </div>
                        <div class="mt-1 text-xs text-stone-400">
                            <div v-for="pm in lastSale.payment_methods" :key="pm.tipo" class="flex justify-between">
                                <span>{{ pm.label }}: Bs {{ parseFloat(pm.monto || 0).toFixed(2) }}</span>
                                <span v-if="pm.pagado" class="text-emerald-400">✓ Pagado</span>
                            </div>
                            <div v-if="lastSale.nro_cuotas" class="text-stone-500">
                                {{ lastSale.nro_cuotas }} cuotas de Bs {{ (lastSale.total / lastSale.nro_cuotas).toFixed(2) }} c/u
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <PrimaryButton type="button" @click="closeSuccessModal">Nueva venta</PrimaryButton>
            </template>
        </DialogModal>

        <!-- New Client Modal -->
        <DialogModal :show="showNewClientModal" max-width="sm" @close="showNewClientModal = false">
            <template #title>Nuevo cliente</template>
            <template #content>
                <div class="space-y-3">
                    <div>
                        <InputLabel for="nc-name" value="Nombre" />
                        <TextInput id="nc-name" v-model="quickClientForm.name" type="text" class="mt-1 w-full" required />
                        <InputError :message="quickClientForm.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="nc-ci" value="CI / Cedula de Identidad" />
                        <TextInput id="nc-ci" v-model="quickClientForm.ci" type="text" class="mt-1 w-full" />
                        <InputError :message="quickClientForm.errors.ci" />
                    </div>
                    <div>
                        <InputLabel for="nc-email" value="Email" />
                        <TextInput id="nc-email" v-model="quickClientForm.email" type="email" class="mt-1 w-full" />
                        <InputError :message="quickClientForm.errors.email" />
                    </div>
                    <div>
                        <InputLabel for="nc-phone" value="Telefono" />
                        <TextInput id="nc-phone" v-model="quickClientForm.phone" type="text" class="mt-1 w-full" />
                        <InputError :message="quickClientForm.errors.phone" />
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="showNewClientModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton type="button" :disabled="quickClientForm.processing" @click="saveNewClient">
                        Guardar cliente
                    </PrimaryButton>
                </div>
            </template>
        </DialogModal>

        <!-- QR Error Modal -->
        <DialogModal :show="showQrErrorModal" max-width="sm" @close="showQrErrorModal = false">
            <template #title>
                <div class="flex items-center gap-2 text-red-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    Error al generar QR
                </div>
            </template>
            <template #content>
                <p class="text-sm text-stone-300">{{ qrErrorMessage }}</p>
            </template>
            <template #footer>
                <div class="flex justify-end">
                    <SecondaryButton type="button" @click="showQrErrorModal = false">Cerrar</SecondaryButton>
                </div>
            </template>
        </DialogModal>

        <!-- Barcode Scanner -->
        <BarcodeScannerModal
            :show="showBarcodeScanner"
            @close="showBarcodeScanner = false"
            @scanned="handleBarcodeScanned"
        />

        <!-- Comprobantes Modal -->
        <DialogModal :show="showDetalleVentaModal" max-width="lg" @close="showDetalleVentaModal = false">
            <template #title>
                <div class="flex items-center gap-2 text-stone-200">
                    <span>Factura #{{ selectedVenta?.id }}</span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="selectedVenta?.tipo_pago === 'credito' ? 'bg-amber-500/10 text-amber-400' : 'bg-emerald-500/10 text-emerald-400'">{{ formatTipoPago(selectedVenta?.tipo_pago) }}</span>
                </div>
            </template>
            <template #content>
                <div v-if="selectedVenta" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-stone-400">Fecha:</span><p class="font-medium text-stone-200">{{ new Date(selectedVenta.created_at).toLocaleString() }}</p></div>
                        <div><span class="text-stone-400">Vendedor:</span><p class="font-medium text-stone-200">{{ selectedVenta.user?.name || '—' }}</p></div>
                        <div><span class="text-stone-400">Cliente:</span><p class="font-medium text-stone-200">{{ selectedVenta.cliente?.name || 'Consumidor Final' }}</p></div>
                        <div v-if="selectedVenta.cliente?.ci"><span class="text-stone-400">CI:</span><p class="font-medium text-stone-200">{{ selectedVenta.cliente.ci }}</p></div>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold uppercase text-stone-400 mb-2">Productos</h4>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-stone-700 text-stone-400">
                                    <th class="text-left py-2 font-semibold">Producto</th>
                                    <th class="text-center py-2 font-semibold">Cant</th>
                                    <th class="text-right py-2 font-semibold">P.U.</th>
                                    <th class="text-right py-2 font-semibold">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="d in selectedVenta.detalle_ventas" :key="d.id" class="border-b border-stone-700/50">
                                    <td class="py-2 text-stone-200">{{ d.producto?.nombre || '—' }}</td>
                                    <td class="py-2 text-center text-stone-400">{{ d.cantidad }}</td>
                                    <td class="py-2 text-right font-mono text-stone-400">Bs {{ Number(d.precio_u_final).toFixed(2) }}</td>
                                    <td class="py-2 text-right font-mono text-stone-200">Bs {{ Number(d.subtotal).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="rounded-lg bg-stone-800/50 p-3 space-y-1 text-sm">
                        <div class="flex justify-between"><span class="text-stone-400">Subtotal</span><span class="font-mono text-stone-200">Bs {{ Number(selectedVenta.monto_original).toFixed(2) }}</span></div>
                        <div v-if="selectedVenta.cod_descuento" class="flex justify-between"><span class="text-stone-400">Descuento ({{ selectedVenta.cod_descuento }})</span><span class="font-mono text-emerald-400">−Bs {{ (Number(selectedVenta.monto_original) - Number(selectedVenta.monto_final)).toFixed(2) }}</span></div>
                        <div class="flex justify-between border-t border-stone-700 pt-1 text-base font-bold"><span class="text-stone-200">Total</span><span class="font-mono text-amber-200">Bs {{ Number(selectedVenta.monto_final).toFixed(2) }}</span></div>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold uppercase text-stone-400 mb-2">Metodos de pago</h4>
                        <div class="space-y-1">
                            <div v-for="mp in selectedVenta.metodo_pagos" :key="mp.id" class="flex justify-between text-sm"><span class="text-stone-200">{{ formatTipoPago(mp.tipo_pago) }}</span><span class="font-mono text-stone-200">Bs {{ Number(mp.monto || 0).toFixed(2) }}</span></div>
                            <div v-if="!selectedVenta.metodo_pagos?.length" class="flex justify-between text-sm"><span class="text-stone-200">{{ formatTipoPago(selectedVenta.tipo_pago) }}</span><span class="font-mono text-stone-200">Bs {{ Number(selectedVenta.monto_final).toFixed(2) }}</span></div>
                        </div>
                    </div>
                    <div v-if="selectedVenta.tipo_pago === 'credito' && selectedVenta.venta_cuotas?.length">
                        <h4 class="text-xs font-semibold uppercase text-stone-400 mb-2">Cuotas</h4>
                        <div class="space-y-1">
                            <div v-for="cu in selectedVenta.venta_cuotas" :key="cu.id" class="flex justify-between text-sm"><span class="text-stone-200">Cuota #{{ cu.nro_cuota }}</span><span class="font-mono text-stone-200">Bs {{ Number(cu.sub_monto).toFixed(2) }}</span><span :class="cu.estado === 'pagado' ? 'text-emerald-400' : 'text-amber-400'">{{ cu.estado === 'pagado' ? 'Pagado' : 'Pendiente' }}</span></div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showDetalleVentaModal = false">Cerrar</SecondaryButton>
            </template>
        </DialogModal>

        <!-- Pedidos Modal -->
        <DialogModal :show="showDetallePedidoModal" max-width="lg" @close="showDetallePedidoModal = false">
            <template #title>
                <div class="flex items-center gap-2 text-stone-200">
                    <span>Pedido #{{ selectedPedido?.id }}</span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="{ 'bg-emerald-500/10 text-emerald-400': selectedPedido?.estado_pedido === 'pagado', 'bg-sky-500/10 text-sky-400': selectedPedido?.estado_pedido === 'enviado' }">{{ formatEstadoPedido(selectedPedido?.estado_pedido) }}</span>
                </div>
            </template>
            <template #content>
                <div v-if="selectedPedido" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-stone-400">Cliente:</span><p class="font-medium text-stone-200">{{ selectedPedido.cliente?.name || '—' }}</p></div>
                        <div><span class="text-stone-400">Fecha:</span><p class="font-medium text-stone-200">{{ new Date(selectedPedido.created_at).toLocaleString() }}</p></div>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold uppercase text-stone-400 mb-2">Productos</h4>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-stone-700 text-stone-400">
                                    <th class="text-left py-2 font-semibold">Producto</th>
                                    <th class="text-center py-2 font-semibold">Cant</th>
                                    <th class="text-right py-2 font-semibold">P.U.</th>
                                    <th class="text-right py-2 font-semibold">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="d in selectedPedido.detalle_ventas" :key="d.id" class="border-b border-stone-700/50">
                                    <td class="py-2 text-stone-200">{{ d.producto?.nombre || '—' }}</td>
                                    <td class="py-2 text-center text-stone-400">{{ d.cantidad }}</td>
                                    <td class="py-2 text-right font-mono text-stone-400">Bs {{ Number(d.precio_u_final).toFixed(2) }}</td>
                                    <td class="py-2 text-right font-mono text-stone-200">Bs {{ Number(d.subtotal).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="rounded-lg bg-stone-800/50 p-3 space-y-1 text-sm">
                        <div class="flex justify-between"><span class="text-stone-400">Total</span><span class="font-mono text-amber-200 font-bold">Bs {{ Number(selectedPedido.monto_final).toFixed(2) }}</span></div>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="showDetallePedidoModal = false">Cerrar</SecondaryButton>
                    <PrimaryButton v-if="selectedPedido?.estado_pedido === 'pagado'" @click="marcarEnviado(selectedPedido.id); showDetallePedidoModal = false">Marcar como Enviado</PrimaryButton>
                </div>
            </template>
        </DialogModal>
    </AppLayout>
</template>

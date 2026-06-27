<script setup>
import { ref, computed } from 'vue';
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
const paymentMethod = ref('efectivo');
const montoRecibido = ref('');
const promoCode = ref('');
const promoApplied = ref(null);
const promoError = ref('');

const cardNumber = ref('');
const cardExpiry = ref('');
const cardCvc = ref('');
const nroCuotas = ref(2);

const qrImage = ref(null);
const qrLoading = ref(false);
const qrTransactionId = ref(null);
const qrFormat = ref('png');

const showConfirmModal = ref(false);
const showNewClientModal = ref(false);
const showSuccessModal = ref(false);
const showQrErrorModal = ref(false);
const qrErrorMessage = ref('');
const lastSale = ref(null);
const submitting = ref(false);
const processingQr = ref(false);
const processingCard = ref(false);

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

const cambio = computed(() => {
    const recibido = parseFloat(montoRecibido.value) || 0;
    return Math.max(0, recibido - totalFinal.value);
});

const cuotaMonto = computed(() => {
    if (paymentMethod.value !== 'credito' || nroCuotas.value < 1) return 0;
    return totalFinal.value / nroCuotas.value;
});

const canFinalizar = computed(() => {
    if (cart.value.length === 0) return false;
    if (!selectedClient.value) return false;
    if (paymentMethod.value === 'efectivo') {
        const recibido = parseFloat(montoRecibido.value) || 0;
        if (recibido < totalFinal.value) return false;
    }
    if (paymentMethod.value === 'qr') return true;
    if (paymentMethod.value === 'tarjeta') {
        if (!cardNumber.value || !cardExpiry.value || !cardCvc.value) return false;
    }
    if (paymentMethod.value === 'credito' && nroCuotas.value < 2) return false;
    return true;
});

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

async function handlePagar() {
    if (!canFinalizar.value) return;

    if (paymentMethod.value === 'qr') {
        await handleQRPayment();
        return;
    }

    if (paymentMethod.value === 'tarjeta') {
        await handleCardPayment();
        return;
    }

    showConfirmModal.value = true;
}

async function handleQRPayment() {
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
            monto: totalFinal.value,
            orderDetail: details,
        });
        if (resp.data?.error === false && resp.data?.data?.qrBase64) {
            qrImage.value = resp.data.data.qrBase64;
            qrTransactionId.value = resp.data.data.transactionId ?? null;
            qrFormat.value = resp.data.data.qrFormat || 'png';
            showConfirmModal.value = true;
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

async function handleCardPayment() {
    processingCard.value = true;
    showConfirmModal.value = true;
}

function submitSale() {
    if (submitting.value) return;
    submitting.value = true;

    const saleForm = useForm({
        tipo_pago: paymentMethod.value,
        monto_pagado: paymentMethod.value === 'efectivo' ? montoRecibido.value : totalFinal.value,
        cliente_id: selectedClient.value?.id,
        detalles: cart.value.map(i => ({
            producto_id: i.producto_id,
            cantidad: i.cantidad,
        })),
        codigo_promo: promoApplied.value ? promoApplied.value.codigo_promo : null,
        nro_cuotas: paymentMethod.value === 'credito' ? nroCuotas.value : null,
        card_number: paymentMethod.value === 'tarjeta' ? cardNumber.value : null,
        card_expiry: paymentMethod.value === 'tarjeta' ? cardExpiry.value : null,
        card_cvc: paymentMethod.value === 'tarjeta' ? cardCvc.value : null,
        qr_transaction_id: paymentMethod.value === 'qr' ? qrTransactionId.value : null,
    });

    saleForm.post(route('ventas.store'), {
        preserveState: false,
        onSuccess: () => {
            showConfirmModal.value = false;
            lastSale.value = {
                cliente: selectedClient.value,
                detalles: [...cart.value],
                total: totalFinal.value,
                tipo_pago: paymentMethod.value,
                nro_cuotas: paymentMethod.value === 'credito' ? nroCuotas.value : null,
                monto_pagado: paymentMethod.value === 'efectivo' ? montoRecibido.value : totalFinal.value,
                cambio: paymentMethod.value === 'efectivo' ? cambio.value : 0,
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
    paymentMethod.value = 'efectivo';
    montoRecibido.value = '';
    promoCode.value = '';
    promoApplied.value = null;
    promoError.value = '';
    cardNumber.value = '';
    cardExpiry.value = '';
    cardCvc.value = '';
    nroCuotas.value = 2;
    qrImage.value = null;
    qrTransactionId.value = null;
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
                <h1 class="text-xl font-bold text-amber-200">Punto de Venta</h1>
                <div v-if="cajaActiva" class="text-sm text-emerald-400">
                    Caja #{{ cajaActiva.id }} — {{ cajaActiva.user?.name }}
                </div>
                <div v-else class="text-sm text-red-400">
                    No hay caja abierta
                </div>
            </div>
        </template>

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
                        v-for="metodo in ['efectivo', 'qr', 'tarjeta', 'credito']"
                        :key="metodo"
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition"
                        :class="paymentMethod === metodo
                            ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30'
                            : 'bg-stone-700 text-stone-300 hover:bg-stone-600'"
                        @click="paymentMethod = metodo"
                    >
                        <template v-if="metodo === 'efectivo'">Efectivo</template>
                        <template v-else-if="metodo === 'qr'">QR</template>
                        <template v-else-if="metodo === 'tarjeta'">Tarjeta</template>
                        <template v-else>Credito</template>
                    </button>
                </div>

                <!-- Efectivo -->
                <div v-if="paymentMethod === 'efectivo'" class="space-y-2">
                    <div class="flex items-center gap-4">
                        <div>
                            <label class="mb-1 block text-xs text-stone-400">Monto recibido</label>
                            <TextInput v-model="montoRecibido" type="number" min="0" step="0.01" class="w-40" />
                        </div>
                        <div v-if="parseFloat(montoRecibido) >= totalFinal" class="mt-5">
                            <span class="text-sm text-stone-400">Cambio: </span>
                            <span class="font-mono font-bold text-emerald-400">Bs {{ cambio.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- QR -->
                <div v-if="paymentMethod === 'qr'" class="text-sm text-stone-400">
                    Se generara un codigo QR para el pago.
                </div>

                <!-- Tarjeta -->
                <div v-if="paymentMethod === 'tarjeta'" class="space-y-2">
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="mb-1 block text-xs text-stone-400">Numero de tarjeta</label>
                            <TextInput v-model="cardNumber" type="text" class="w-full" placeholder="4242 4242 4242 4242" />
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <div>
                            <label class="mb-1 block text-xs text-stone-400">Vencimiento</label>
                            <TextInput v-model="cardExpiry" type="text" class="w-28" placeholder="MM/AA" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-stone-400">CVC</label>
                            <TextInput v-model="cardCvc" type="text" class="w-24" placeholder="123" />
                        </div>
                    </div>
                </div>

                <!-- Credito -->
                <div v-if="paymentMethod === 'credito'" class="space-y-2">
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

                <!-- Action button -->
                <div class="mt-6 flex justify-end">
                    <PrimaryButton
                        type="button"
                        class="px-8 py-3 text-lg"
                        @click="handlePagar"
                    >
                        <template v-if="paymentMethod === 'qr' && qrLoading">
                            Generando QR...
                        </template>
                        <template v-else>
                            {{ paymentMethod === 'qr' ? 'Generar QR' : 'Finalizar Venta' }}
                        </template>
                    </PrimaryButton>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <DialogModal :show="showConfirmModal" max-width="lg" scrollable @close="showConfirmModal = false">
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
                            Pago: {{ paymentMethod }}
                            <template v-if="paymentMethod === 'credito'"> — {{ nroCuotas }} cuotas de Bs {{ cuotaMonto.toFixed(2) }}</template>
                            <template v-if="paymentMethod === 'efectivo'"> — Recibido: Bs {{ (parseFloat(montoRecibido) || 0).toFixed(2) }}</template>
                        </div>
                    </div>

                    <!-- QR image -->
                    <div v-if="qrImage" class="flex justify-center">
                        <img :src="`data:image/${qrFormat};base64,${qrImage}`" class="h-48 w-48 rounded-lg" alt="QR de pago">
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="showConfirmModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton
                        type="button"
                        :disabled="submitting"
                        @click="submitSale"
                    >
                        {{ submitting ? 'Procesando...' : (qrImage ? 'Confirmar pago QR' : 'Confirmar venta') }}
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
                            Pago: {{ lastSale.tipo_pago }}
                            <template v-if="lastSale.tipo_pago === 'efectivo'"> — Cambio: Bs {{ parseFloat(lastSale.cambio).toFixed(2) }}</template>
                            <template v-if="lastSale.tipo_pago === 'credito'"> — {{ lastSale.nro_cuotas }} cuotas</template>
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
    </AppLayout>
</template>

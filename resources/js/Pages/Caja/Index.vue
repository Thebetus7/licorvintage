<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';

const props = defineProps({
    cajaActiva: Object,
    productos: Array,
    clientes: Array,
    promocionesActive: Array,
    creditosPendientes: Array,
});

// Pestaña activa de la columna izquierda
const activeLeftTab = ref('caja'); // 'caja' o 'creditos'

// Buscador de productos en POS
const productSearch = ref('');

// Control de Modales
const showClientModal = ref(false);
const showPaymentModal = ref(false);
const showCreditDetailModal = ref(false);

const selectedCredit = ref(null);

// Formularios
const openForm = useForm({ monto_inicial: 0 });
const closeForm = useForm({ monto_real: 0 });

const clientForm = useForm({
    name: '',
    email: '',
    password: 'cliente123456789',
});

const saleForm = useForm({
    tipo_pago: 'compra_directa', // 'compra_directa', 'efectivo', 'qr', 'tarjeta', 'credito'
    monto_pagado: 0,
    detalles: [], // { producto_id, cantidad, precio, nombre }
    cliente_id: '',
    codigo_promo: '',
    nro_cuotas: 2,
});

// Filtrado de productos en POS
const filteredProducts = computed(() => {
    if (!productSearch.value.trim()) return props.productos;
    const query = productSearch.value.toLowerCase();
    return props.productos.filter(p => 
        p.nombre.toLowerCase().includes(query) || 
        p.codigo_barra.toLowerCase().includes(query)
    );
});

// Lógica de Carrito
const addProduct = (producto) => {
    const existing = saleForm.detalles.find((item) => item.producto_id === producto.id);
    const stock = producto.stock_actual?.stock || producto.stockActual?.stock || 0;

    if (existing) {
        if (existing.cantidad < stock) {
            existing.cantidad++;
        }
        return;
    }

    saleForm.detalles.push({
        producto_id: producto.id,
        nombre: producto.nombre,
        precio: Number(producto.precio_venta),
        cantidad: 1,
        stock: stock
    });
};

const updateQty = (item, amt) => {
    const newQty = item.cantidad + amt;
    if (newQty <= 0) {
        saleForm.detalles = saleForm.detalles.filter(d => d.producto_id !== item.producto_id);
    } else if (newQty <= item.stock) {
        item.cantidad = newQty;
    }
};

const removeProduct = (productId) => {
    saleForm.detalles = saleForm.detalles.filter(d => d.producto_id !== productId);
};

// Totales y Descuentos
const subtotal = computed(() => {
    return saleForm.detalles.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
});

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

// Procesar Venta
const handleCheckout = () => {
    if (saleForm.detalles.length === 0) return;

    if (saleForm.tipo_pago === 'credito' && !saleForm.cliente_id) {
        alert('Debes seleccionar un cliente para realizar una venta a crédito.');
        return;
    }

    // Si es pago por QR o Tarjeta, abrimos el modal de simulación de pago primero
    if (saleForm.tipo_pago === 'qr' || saleForm.tipo_pago === 'tarjeta') {
        showPaymentModal.value = true;
    } else {
        submitSale();
    }
};

const submitSale = () => {
    saleForm.monto_pagado = totalFinal.value;
    saleForm.post(route('ventas.store'), {
        preserveScroll: true,
        onSuccess: () => {
            saleForm.reset();
            showPaymentModal.value = false;
        },
        onError: (err) => {
            console.error('Error al registrar la venta:', err);
        }
    });
};

// Crear cliente rápido
const submitRapidClient = () => {
    clientForm.post(route('caja.clientes.rapido'), {
        preserveScroll: true,
        onSuccess: () => {
            showClientModal.value = false;
            clientForm.reset();
        }
    });
};

// Cobro de cuotas
const payInstallment = (cuotaId) => {
    if (confirm('¿Confirmas el cobro de esta cuota?')) {
        installmentForm.post(route('caja.cuotas.pagar', cuotaId), {
            preserveScroll: true,
            onSuccess: () => {
                // Actualizar detalle del modal si está abierto
                if (selectedCredit.value) {
                    const creditId = selectedCredit.value.id;
                    const updated = props.creditosPendientes.find(c => c.id === creditId);
                    if (updated) {
                        selectedCredit.value = updated;
                    } else {
                        showCreditDetailModal.value = false;
                    }
                }
            }
        });
    }
};

const installmentForm = useForm({});

const viewCreditDetails = (credit) => {
    selectedCredit.value = credit;
    showCreditDetailModal.value = true;
};
</script>

<template>
    <AppLayout title="Caja">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-[var(--text-primary)]">Terminal de Caja (POS)</h1>
                    <p class="text-sm text-[var(--text-secondary)]">Aperturas, ventas al contado, plan de cuotas y arqueo contable.</p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6 pb-12">
            <div class="grid gap-6 lg:grid-cols-[380px_1fr]">
                
                <!-- COLUMNA IZQUIERDA: CONTROL DE CAJA Y CRÉDITOS -->
                <div class="space-y-6">
                    <!-- Caja Activa y pestañas -->
                    <div class="rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md">
                        <!-- Pestañas de control -->
                        <div class="flex border-b border-[var(--border-color)] pb-3 mb-4 gap-2">
                            <button
                                :class="['px-4 py-2 text-sm font-semibold rounded-xl transition', activeLeftTab === 'caja' ? 'bg-[var(--accent)] text-white' : 'text-[var(--text-secondary)] hover:bg-white/5']"
                                @click="activeLeftTab = 'caja'"
                            >
                                💵 Caja Activa
                            </button>
                            <button
                                :class="['px-4 py-2 text-sm font-semibold rounded-xl transition', activeLeftTab === 'creditos' ? 'bg-[var(--accent)] text-white' : 'text-[var(--text-secondary)] hover:bg-white/5']"
                                @click="activeLeftTab = 'creditos'"
                            >
                                💳 Créditos Pendientes
                            </button>
                        </div>

                        <!-- Panel de Caja -->
                        <div v-if="activeLeftTab === 'caja'">
                            <h3 class="font-bold text-[var(--text-primary)] mb-3">Estado de Caja</h3>
                            
                            <div v-if="cajaActiva" class="space-y-4">
                                <div class="rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-4 py-3 font-semibold text-sm flex items-center gap-2">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    Caja Abierta #{{ cajaActiva.id }}
                                </div>

                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-[var(--text-secondary)]">Monto Inicial:</span>
                                        <span class="font-semibold">{{ Number(cajaActiva.monto_inicial).toFixed(2) }} Bs</span>
                                    </div>
                                    <div class="flex justify-between border-t border-[var(--border-color)] pt-2">
                                        <span class="text-[var(--text-secondary)]">Monto en Sistema:</span>
                                        <span class="font-bold text-indigo-300 text-base">{{ Number(cajaActiva.monto_sistema).toFixed(2) }} Bs</span>
                                    </div>
                                </div>

                                <!-- Cierre de Caja -->
                                <div class="border-t border-[var(--border-color)] pt-4 space-y-3">
                                    <h4 class="text-xs uppercase font-bold text-[var(--text-secondary)] tracking-wider">Cierre de Caja</h4>
                                    <div>
                                        <InputLabel value="Monto Real en Caja (Efectivo)" />
                                        <TextInput v-model="closeForm.monto_real" type="number" step="0.01" class="mt-1 w-full" placeholder="0.00" />
                                        <InputError :message="closeForm.errors.monto_real" class="mt-1" />
                                    </div>
                                    <button
                                        class="w-full bg-rose-600 hover:bg-rose-750 text-white py-2.5 rounded-xl font-bold transition shadow-lg text-sm cursor-pointer"
                                        :disabled="closeForm.processing"
                                        @click="closeForm.put(route('caja.close', cajaActiva.id))"
                                    >
                                        🔒 Cerrar Caja & Arquear
                                    </button>
                                </div>
                            </div>

                            <!-- Apertura de Caja -->
                            <form v-else @submit.prevent="openForm.post(route('caja.open'))" class="space-y-4">
                                <div class="rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20 px-4 py-3 font-semibold text-sm">
                                    ⚠️ Caja Cerrada. Abre la caja para operar.
                                </div>
                                <div>
                                    <InputLabel value="Monto Inicial en Efectivo" />
                                    <TextInput v-model="openForm.monto_inicial" type="number" step="0.01" class="mt-1 w-full" placeholder="0.00" required />
                                    <InputError :message="openForm.errors.monto_inicial" class="mt-1" />
                                </div>
                                <PrimaryButton class="w-full justify-center py-2.5">
                                    🔓 Abrir Caja
                                </PrimaryButton>
                            </form>
                        </div>

                        <!-- Panel de Créditos (CU6) -->
                        <div v-else class="space-y-4">
                            <h3 class="font-bold text-[var(--text-primary)]">Créditos Vigentes</h3>
                            <p class="text-xs text-[var(--text-secondary)]">Ventas a crédito con cuotas por cobrar.</p>

                            <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                                <div v-if="creditosPendientes.length === 0" class="text-center py-6 text-sm text-[var(--text-secondary)]">
                                    No hay créditos pendientes en el sistema.
                                </div>
                                <div
                                    v-for="credit in creditosPendientes"
                                    :key="credit.id"
                                    class="p-4 rounded-2xl bg-white/5 border border-white/5 hover:bg-white/10 hover:border-white/10 transition duration-200 cursor-pointer flex flex-col justify-between"
                                    @click="viewCreditDetails(credit)"
                                >
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="text-xs font-semibold text-indigo-300 font-mono">Venta #{{ credit.id }}</span>
                                            <h4 class="text-sm font-bold text-[var(--text-primary)] mt-0.5">{{ credit.cliente?.name || 'Cliente sin nombre' }}</h4>
                                        </div>
                                        <span class="text-xs font-bold bg-amber-500/10 border border-amber-500/20 text-amber-400 px-2 py-0.5 rounded-full">
                                            Crédito
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center mt-3 pt-2 border-t border-white/5">
                                        <span class="text-xs text-[var(--text-secondary)]">
                                            {{ credit.venta_cuotas.filter(c => c.estado === 'pendiente').length }} cuotas pendientes
                                        </span>
                                        <span class="text-sm font-extrabold text-[var(--text-primary)]">
                                            {{ Number(credit.monto_final).toFixed(2) }} Bs
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: PUNTO DE VENTA (POS) -->
                <div class="grid gap-6 md:grid-cols-[1fr_360px]">
                    <!-- Lista de Productos y Buscador -->
                    <div class="rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md space-y-4">
                        <div class="flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                            <h3 class="font-bold text-[var(--text-primary)]">Selección de Productos</h3>
                            <TextInput
                                v-model="productSearch"
                                type="text"
                                placeholder="🔍 Buscar por nombre o barra..."
                                class="w-full sm:w-64 text-xs py-1.5"
                                :disabled="!cajaActiva"
                            />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 max-h-[500px] overflow-y-auto pr-1">
                            <div v-if="filteredProducts.length === 0" class="col-span-2 text-center py-12 text-[var(--text-secondary)] text-sm">
                                No se encontraron productos.
                            </div>
                            <button
                                v-for="item in filteredProducts"
                                :key="item.id"
                                class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/30 p-4 text-left hover:border-[var(--accent)] hover:bg-[var(--accent)]/5 text-[var(--text-primary)] transition duration-200 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed group flex gap-3 items-center"
                                :disabled="!cajaActiva"
                                @click="addProduct(item)"
                            >
                                <img
                                    :src="item.imagen"
                                    :alt="item.nombre"
                                    class="w-12 h-12 rounded-xl object-cover bg-white/10 flex-shrink-0"
                                />
                                <div class="min-w-0">
                                    <div class="font-bold text-sm text-[var(--text-primary)] truncate group-hover:text-[var(--accent)] transition-colors">{{ item.nombre }}</div>
                                    <div class="text-xs text-[var(--text-secondary)] mt-0.5">
                                        Stock: {{ item.stock_actual?.stock || item.stockActual?.stock || 0 }} uds
                                    </div>
                                    <div class="text-xs font-extrabold text-indigo-300 mt-1">
                                        {{ Number(item.precio_venta).toFixed(2) }} Bs
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Carrito y Procesador de Venta -->
                    <div class="rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md flex flex-col justify-between space-y-4">
                        <div class="space-y-4">
                            <h3 class="font-bold text-[var(--text-primary)] border-b border-[var(--border-color)] pb-3">Detalle de Venta</h3>

                            <!-- Carrito -->
                            <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                                <div v-if="saleForm.detalles.length === 0" class="text-center py-8 text-xs text-[var(--text-secondary)] leading-relaxed">
                                    El carrito está vacío.<br>Agrega productos para iniciar la venta.
                                </div>
                                <div
                                    v-for="item in saleForm.detalles"
                                    :key="item.producto_id"
                                    class="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/5"
                                >
                                    <div class="min-w-0 flex-1 mr-2">
                                        <h4 class="text-xs font-bold text-[var(--text-primary)] truncate">{{ item.nombre }}</h4>
                                        <span class="text-[10px] text-[var(--text-secondary)]">
                                            {{ item.precio.toFixed(2) }} Bs c/u
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button
                                            class="w-6 h-6 rounded-lg bg-white/5 hover:bg-white/15 text-xs text-[var(--text-primary)] flex items-center justify-center font-bold"
                                            @click="updateQty(item, -1)"
                                        >
                                            -
                                        </button>
                                        <span class="text-xs font-semibold px-1 min-w-[20px] text-center">{{ item.cantidad }}</span>
                                        <button
                                            class="w-6 h-6 rounded-lg bg-white/5 hover:bg-white/15 text-xs text-[var(--text-primary)] flex items-center justify-center font-bold"
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

                            <!-- Aplicación de Promociones (CU5) -->
                            <div class="pt-3 border-t border-[var(--border-color)]">
                                <InputLabel value="Cupón de Promoción" class="text-xs" />
                                <div class="flex gap-2 mt-1">
                                    <TextInput
                                        v-model="saleForm.codigo_promo"
                                        type="text"
                                        class="w-full text-xs uppercase font-mono py-1.5"
                                        placeholder="Ej. VINTAGE10"
                                        :disabled="saleForm.detalles.length === 0"
                                    />
                                </div>
                                <div v-if="saleForm.codigo_promo.trim() && appliedPromo" class="text-xs text-emerald-400 font-semibold mt-1">
                                    ✓ Cupón aplicado: {{ appliedPromo.nombre_promo }} ({{ Number(appliedPromo.descuento) }}{{ appliedPromo.tipo_descuento === 'porcentaje' ? '%' : ' Bs' }})
                                </div>
                                <div v-else-if="saleForm.codigo_promo.trim() && !appliedPromo" class="text-xs text-rose-400 font-semibold mt-1">
                                    ✕ Cupón no válido o expirado
                                </div>
                            </div>

                            <!-- Selector de Cliente (CU6) -->
                            <div class="pt-3 border-t border-[var(--border-color)]">
                                <div class="flex justify-between items-center">
                                    <InputLabel value="Cliente" class="text-xs" />
                                    <button
                                        type="button"
                                        class="text-indigo-400 hover:text-indigo-300 text-xs font-semibold"
                                        @click="showClientModal = true"
                                    >
                                        + Registrar Rápido
                                    </button>
                                </div>
                                <select
                                    v-model="saleForm.cliente_id"
                                    class="mt-1 block w-full rounded-xl border-[var(--border-color)] bg-[var(--bg-secondary)] text-[var(--text-primary)] focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-xs p-2 transition"
                                >
                                    <option value="">-- Cliente Genérico (Contado) --</option>
                                    <option v-for="c in clientes" :key="c.id" :value="c.id">
                                        {{ c.name }} ({{ c.email }})
                                    </option>
                                </select>
                            </div>

                            <!-- Tipo de Venta y Cuotas (CU6) -->
                            <div class="pt-3 border-t border-[var(--border-color)] grid grid-cols-2 gap-3">
                                <div>
                                    <InputLabel value="Modalidad" class="text-xs" />
                                    <select
                                        v-model="saleForm.tipo_pago"
                                        class="mt-1 block w-full rounded-xl border-[var(--border-color)] bg-[var(--bg-secondary)] text-[var(--text-primary)] focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-xs p-2 transition"
                                    >
                                        <option value="compra_directa">Compra Directa</option>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="qr">Código QR</option>
                                        <option value="tarjeta">Tarjeta Bancaria</option>
                                        <option value="credito">A Crédito (Cuotas)</option>
                                    </select>
                                </div>

                                <div v-if="saleForm.tipo_pago === 'credito'">
                                    <InputLabel value="Nro. Cuotas" class="text-xs" />
                                    <select
                                        v-model="saleForm.nro_cuotas"
                                        class="mt-1 block w-full rounded-xl border-[var(--border-color)] bg-[var(--bg-secondary)] text-[var(--text-primary)] focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-xs p-2 transition"
                                    >
                                        <option :value="2">2 cuotas</option>
                                        <option :value="3">3 cuotas</option>
                                        <option :value="4">4 cuotas</option>
                                        <option :value="6">6 cuotas</option>
                                        <option :value="12">12 cuotas</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Cálculo de Cuotas en Vivo -->
                            <div v-if="saleForm.tipo_pago === 'credito' && saleForm.detalles.length > 0" class="p-3 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-xs text-indigo-300">
                                <span class="font-bold block uppercase tracking-wider text-[10px] text-indigo-400 mb-1">Plan de Cuotas Estimado:</span>
                                {{ saleForm.nro_cuotas }} cuotas mensuales de <strong class="text-[var(--text-primary)] font-bold text-sm">{{ quotaAmount.toFixed(2) }} Bs</strong>
                            </div>
                        </div>

                        <!-- Resumen Contable final -->
                        <div class="border-t border-[var(--border-color)] pt-4 space-y-4">
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between text-[var(--text-secondary)]">
                                    <span>Subtotal:</span>
                                    <span>{{ subtotal.toFixed(2) }} Bs</span>
                                </div>
                                <div v-if="discountAmount > 0" class="flex justify-between text-emerald-400 font-semibold">
                                    <span>Descuento Promoción:</span>
                                    <span>-{{ discountAmount.toFixed(2) }} Bs</span>
                                </div>
                                <div class="flex justify-between text-base font-extrabold text-[var(--text-primary)] border-t border-white/5 pt-2">
                                    <span>Total a Pagar:</span>
                                    <span class="text-indigo-300 text-lg">{{ totalFinal.toFixed(2) }} Bs</span>
                                </div>
                            </div>

                            <button
                                class="w-full bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white py-3 rounded-2xl font-bold transition shadow-lg text-sm flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="saleForm.detalles.length === 0 || !cajaActiva || saleForm.processing"
                                @click="handleCheckout"
                            >
                                💳 Registrar Venta
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL: CREAR CLIENTE RÁPIDO -->
        <DialogModal :show="showClientModal" @close="showClientModal = false">
            <template #title>
                <h3 class="text-lg font-bold text-[var(--text-primary)]">Registrar Cliente Rápido</h3>
            </template>

            <template #content>
                <form @submit.prevent="submitRapidClient" class="space-y-4">
                    <div>
                        <InputLabel for="client_name" value="Nombre Completo" />
                        <TextInput id="client_name" v-model="clientForm.name" type="text" class="mt-1 block w-full" placeholder="Juan Perez" required />
                        <InputError :message="clientForm.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="client_email" value="Correo Electrónico" />
                        <TextInput id="client_email" v-model="clientForm.email" type="email" class="mt-1 block w-full" placeholder="juan@gmail.com" required />
                        <InputError :message="clientForm.errors.email" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="client_password" value="Contraseña de Cliente (Autogenerada)" />
                        <TextInput id="client_password" v-model="clientForm.password" type="text" class="mt-1 block w-full font-mono bg-white/5" readonly />
                        <span class="text-[10px] text-[var(--text-secondary)] block mt-1">El cliente podrá cambiar su contraseña al iniciar sesión en su catálogo.</span>
                    </div>
                </form>
            </template>

            <template #footer>
                <div class="flex items-center gap-3">
                    <SecondaryButton @click="showClientModal = false">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': clientForm.processing }" :disabled="clientForm.processing" @click="submitRapidClient">
                        Guardar Cliente
                    </PrimaryButton>
                </div>
            </template>
        </DialogModal>

        <!-- MODAL: SIMULADOR DE PAGOS ELECTRÓNICOS (CU7) -->
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

        <!-- MODAL: DETALLES DE CRÉDITO Y COBRO DE CUOTAS (CU6) -->
        <DialogModal :show="showCreditDetailModal" @close="showCreditDetailModal = false">
            <template #title>
                <h3 class="text-lg font-bold text-[var(--text-primary)]">
                    Detalles de Crédito de Venta #{{ selectedCredit?.id }}
                </h3>
            </template>

            <template #content>
                <div v-if="selectedCredit" class="space-y-4 text-sm">
                    <div class="grid grid-cols-2 gap-4 p-4 rounded-2xl bg-white/5 border border-white/5">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[var(--text-secondary)] block">Cliente</span>
                            <span class="font-bold text-[var(--text-primary)]">{{ selectedCredit.cliente?.name }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[var(--text-secondary)] block">Monto Final</span>
                            <span class="font-bold text-indigo-300">{{ Number(selectedCredit.monto_final).toFixed(2) }} Bs</span>
                        </div>
                    </div>

                    <h4 class="font-bold text-xs uppercase tracking-wider text-[var(--text-secondary)] mt-4">Plan de Cuotas</h4>
                    
                    <div class="space-y-2.5">
                        <div
                            v-for="cuota in selectedCredit.venta_cuotas"
                            :key="cuota.id"
                            class="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/5"
                        >
                            <div>
                                <span class="font-bold text-xs">Cuota #{{ cuota.nro_cuota }}</span>
                                <span class="text-xs text-[var(--text-secondary)] block mt-0.5">
                                    Monto: {{ Number(cuota.sub_monto).toFixed(2) }} Bs
                                </span>
                            </div>

                            <div class="flex items-center gap-3">
                                <span
                                    v-if="cuota.estado === 'pagado'"
                                    class="text-xs font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 rounded-full"
                                >
                                    ✓ Pagada ({{ new Date(cuota.fecha_pago).toLocaleDateString() }})
                                </span>
                                <div v-else class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-amber-400 bg-amber-500/10 border border-amber-500/20 px-3 py-1 rounded-full">
                                        Pendiente
                                    </span>
                                    <button
                                        v-if="cajaActiva"
                                        class="text-xs font-bold bg-indigo-600 hover:bg-indigo-750 text-white px-3 py-1 rounded-lg transition"
                                        @click="payInstallment(cuota.id)"
                                    >
                                        Cobrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex justify-end">
                    <SecondaryButton @click="showCreditDetailModal = false">
                        Cerrar Detalles
                    </SecondaryButton>
                </div>
            </template>
        </DialogModal>
    </AppLayout>
</template>

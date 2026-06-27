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

const props = defineProps({
    cajaActiva: Object,
    vendedores: Array,
    clientes: Array,
    creditosPendientes: Array,
    aperturas: Array,
    filters: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const isPropietario = computed(() => page.props.auth.roles?.includes('propietario'));

const activeTab = ref(page.props.ziggy?.query?.tab || 'caja');
const showOpenModal = ref(false);
const showCloseModal = ref(false);
const showClientModal = ref(false);
const showCreditDetailModal = ref(false);
const selectedCredit = ref(null);
const vendedorSearch = ref('');
const historialFrom = ref(props.filters?.from || '');
const historialTo = ref(props.filters?.to || '');
const selectedApertura = ref(null);
const showAperturaModal = ref(false);

const openForm = useForm({ monto_inicial: 0, vendedor_id: '' });
const closeForm = useForm({
    totales_caja: { efectivo: 0, qr: 0, tarjeta: 0, credito: 0 },
});

const filteredVendedores = computed(() => {
    if (!vendedorSearch.value.trim()) return props.vendedores;
    const q = vendedorSearch.value.toLowerCase();
    return props.vendedores.filter(v =>
        v.name.toLowerCase().includes(q) || v.email.toLowerCase().includes(q)
    );
});

const tiposPago = ['efectivo', 'qr', 'tarjeta', 'credito'];

const totalSistemaPorTipo = (tipo) => {
    return Number(props.cajaActiva?.totales_sistema?.[tipo] || 0);
};

const totalCajaPorTipo = (tipo) => {
    return Number(closeForm.totales_caja[tipo] || 0);
};

const diferenciaPorTipo = (tipo) => {
    return totalCajaPorTipo(tipo) - totalSistemaPorTipo(tipo);
};

const totalSistemaGlobal = computed(() => {
    return tiposPago.reduce((s, t) => s + totalSistemaPorTipo(t), 0) + Number(props.cajaActiva?.monto_inicial || 0);
});

const totalCajaGlobal = computed(() => {
    return tiposPago.reduce((s, t) => s + totalCajaPorTipo(t), 0);
});

const diferenciaGlobal = computed(() => {
    return totalCajaGlobal.value - totalSistemaGlobal.value;
});

const submitOpen = () => {
    openForm.post(route('caja.open'), {
        preserveScroll: true,
        onSuccess: () => {
            showOpenModal.value = false;
            openForm.reset();
            vendedorSearch.value = '';
        },
    });
};

const confirmClose = () => {
    showCloseModal.value = true;
};

const submitClose = () => {
    closeForm.put(route('caja.close', props.cajaActiva.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCloseModal.value = false;
            closeForm.reset();
        },
        onError: (err) => {
            console.error('Error al cerrar caja:', err);
        },
    });
};

const payInstallment = (cuotaId) => {
    installmentForm.post(route('caja.cuotas.pagar', cuotaId), {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedCredit.value) {
                const creditId = selectedCredit.value.id;
                const updated = props.creditosPendientes.find(c => c.id === creditId);
                if (updated) selectedCredit.value = updated;
                else showCreditDetailModal.value = false;
            }
        }
    });
};

const installmentForm = useForm({});

const viewCreditDetails = (credit) => {
    selectedCredit.value = credit;
    showCreditDetailModal.value = true;
};

const formatTipoPago = (tipo) => {
    const map = { efectivo: 'Efectivo', qr: 'QR', tarjeta: 'Tarjeta', credito: 'Crédito', compra_directa: 'Directo' };
    return map[tipo] || tipo;
};

const formatDiff = (diff) => {
    const n = Number(diff || 0);
    return (n >= 0 ? '+' : '') + n.toFixed(2);
};

const verApertura = (c) => {
    selectedApertura.value = c;
    showAperturaModal.value = true;
};

const filterHistorial = () => {
    router.get(route('caja.index'), {
        from: historialFrom.value,
        to: historialTo.value,
        tab: 'historial',
    }, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Caja">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-[var(--text-primary)]">Módulo de Caja</h1>
                    <p class="text-sm text-[var(--text-secondary)]">Apertura, arqueo, comprobantes y créditos.</p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6 pb-12">

            <!-- TABS -->
            <div class="flex gap-2 border-b border-[var(--border-color)] pb-3">
                <button
                    v-for="tab in [
                        { key: 'caja', label: '💵 Caja' },
                        { key: 'historial', label: '📋 Historial' },
                        { key: 'creditos', label: '💳 Créditos' },
                    ]"
                    :key="tab.key"
                    :class="['px-5 py-2.5 text-sm font-bold rounded-xl transition', activeTab === tab.key ? 'bg-[var(--accent)] text-white shadow-lg' : 'text-[var(--text-secondary)] hover:bg-white/5']"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- TAB: CAJA -->
            <div v-if="activeTab === 'caja'">

                <!-- Sin caja abierta -->
                <template v-if="!cajaActiva">
                    <div class="rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-8 shadow-xl backdrop-blur-md text-center max-w-lg mx-auto">
                        <div class="text-5xl mb-4">🔒</div>
                        <h3 class="text-lg font-bold text-[var(--text-primary)] mb-2">Caja Cerrada</h3>
                        <p class="text-sm text-[var(--text-secondary)] mb-6">No hay ninguna caja abierta en el sistema.</p>

                        <template v-if="isPropietario">
                            <p class="text-xs text-[var(--text-secondary)] mb-4">Abre una caja para que un vendedor pueda operar.</p>
                            <button
                                class="bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white px-8 py-3 rounded-xl font-bold transition shadow-lg text-sm cursor-pointer"
                                @click="showOpenModal = true"
                            >
                                🔓 Abrir Caja
                            </button>
                        </template>
                        <p v-else class="text-xs text-[var(--text-secondary)]">Espera a que el propietario abra una caja para ti.</p>
                    </div>
                </template>

                <!-- Caja abierta: dashboard -->
                <template v-else>
                    <div class="grid gap-6 lg:grid-cols-3">
                        <!-- Info de caja -->
                        <div class="rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md space-y-4">
                            <div class="rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-4 py-3 font-semibold text-sm flex items-center gap-2">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                Caja Abierta #{{ cajaActiva.id }}
                            </div>

                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-[var(--text-secondary)]">Vendedor:</span>
                                    <span class="font-semibold">{{ cajaActiva.user?.name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[var(--text-secondary)]">Abierta por:</span>
                                    <span class="font-semibold">{{ cajaActiva.opener?.name || '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[var(--text-secondary)]">Apertura:</span>
                                    <span class="font-semibold">{{ new Date(cajaActiva.tiempo_apertura).toLocaleString() }}</span>
                                </div>
                                <div class="flex justify-between border-t border-[var(--border-color)] pt-2 mt-2">
                                    <span class="text-[var(--text-secondary)]">Monto Inicial:</span>
                                    <span class="font-bold">{{ Number(cajaActiva.monto_inicial).toFixed(2) }} Bs</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[var(--text-secondary)]">Total Sistema:</span>
                                    <span class="font-bold text-indigo-300 text-base">{{ Number(cajaActiva.monto_sistema).toFixed(2) }} Bs</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de arqueo -->
                        <div class="lg:col-span-2 rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md">
                            <h4 class="text-sm uppercase font-bold text-[var(--text-secondary)] tracking-wider mb-4">Arqueo por Tipo de Pago</h4>

                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-[var(--text-secondary)] border-b border-[var(--border-color)]">
                                        <th class="text-left py-3 font-semibold">Tipo</th>
                                        <th class="text-right py-3 font-semibold">Total Sistema</th>
                                        <th class="text-right py-3 font-semibold">Total Caja</th>
                                        <th class="text-right py-3 font-semibold">Diferencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="tipo in tiposPago" :key="tipo" class="border-b border-[var(--border-color)]/50">
                                        <td class="py-3 capitalize font-medium">{{ tipo }}</td>
                                        <td class="py-3 text-right font-mono">{{ totalSistemaPorTipo(tipo).toFixed(2) }}</td>
                                        <td class="py-3 text-right">
                                            <input
                                                v-if="!isPropietario && cajaActiva.user_id === user?.id"
                                                v-model.number="closeForm.totales_caja[tipo]"
                                                type="number"
                                                step="0.01"
                                                class="w-28 text-right bg-transparent border border-[var(--border-color)] rounded-lg px-3 py-1.5 font-mono text-sm focus:border-indigo-500 focus:ring-0"
                                            />
                                            <span v-else class="font-mono">{{ totalCajaPorTipo(tipo).toFixed(2) }}</span>
                                        </td>
                                        <td class="py-3 text-right font-mono font-semibold" :class="diferenciaPorTipo(tipo) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                            {{ diferenciaPorTipo(tipo) >= 0 ? '+' : '' }}{{ diferenciaPorTipo(tipo).toFixed(2) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="font-bold text-base border-t-2 border-[var(--border-color)]">
                                        <td class="py-3">Totales</td>
                                        <td class="py-3 text-right font-mono">{{ totalSistemaGlobal.toFixed(2) }}</td>
                                        <td class="py-3 text-right font-mono">{{ totalCajaGlobal.toFixed(2) }}</td>
                                        <td class="py-3 text-right font-mono" :class="diferenciaGlobal >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                            {{ diferenciaGlobal >= 0 ? '+' : '' }}{{ diferenciaGlobal.toFixed(2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>

                            <!-- Botón cerrar (solo vendedor asignado) -->
                            <div v-if="!isPropietario && cajaActiva.user_id === user?.id" class="border-t border-[var(--border-color)] pt-4 mt-4 flex justify-end">
                                <button
                                    class="bg-rose-600 hover:bg-rose-700 text-white px-6 py-2.5 rounded-xl font-bold transition shadow-lg text-sm cursor-pointer disabled:opacity-50"
                                    :disabled="closeForm.processing"
                                    @click="confirmClose"
                                >
                                    🔒 Cerrar Caja & Arquear
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- TAB: HISTORIAL (aperturas y cierres) -->
            <div v-if="activeTab === 'historial'">
                <div class="rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md">
                    <h3 class="font-bold text-[var(--text-primary)] mb-1">Historial de Aperturas y Cierres</h3>

                    <!-- Date filter -->
                    <div class="flex items-center gap-3 mb-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-[var(--text-secondary)] mb-1">Desde</label>
                            <input
                                v-model="historialFrom"
                                type="date"
                                class="rounded-lg border border-[var(--border-color)] bg-transparent px-3 py-1.5 text-sm text-[var(--text-primary)]"
                            />
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-[var(--text-secondary)] mb-1">Hasta</label>
                            <input
                                v-model="historialTo"
                                type="date"
                                class="rounded-lg border border-[var(--border-color)] bg-transparent px-3 py-1.5 text-sm text-[var(--text-primary)]"
                            />
                        </div>
                        <div class="pt-5">
                            <button
                                class="bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white px-4 py-1.5 rounded-lg text-sm font-semibold transition cursor-pointer"
                                @click="filterHistorial"
                            >
                                Filtrar
                            </button>
                        </div>
                    </div>

                    <div v-if="aperturas.length === 0" class="text-center py-12 text-sm text-[var(--text-secondary)]">
                        No se encontraron aperturas en este rango de fechas.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-[var(--text-secondary)] border-b border-[var(--border-color)] text-left">
                                    <th class="py-3 pr-2 font-semibold">#</th>
                                    <th class="py-3 px-2 font-semibold">Vendedor</th>
                                    <th class="py-3 px-2 font-semibold">Abrió</th>
                                    <th class="py-3 px-2 font-semibold text-right">Inicial</th>
                                    <th class="py-3 px-2 font-semibold text-right">Sistema</th>
                                    <th class="py-3 px-2 font-semibold text-right">Dif.</th>
                                    <th class="py-3 px-2 font-semibold">Apertura</th>
                                    <th class="py-3 px-2 font-semibold">Cierre</th>
                                    <th class="py-3 px-2 font-semibold">Estado</th>
                                    <th class="py-3 pl-2 font-semibold text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="c in aperturas" :key="c.id" class="border-b border-[var(--border-color)]/50 hover:bg-white/5 transition">
                                    <td class="py-3 pr-2 font-mono font-bold text-indigo-300">#{{ c.id }}</td>
                                    <td class="py-3 px-2">{{ c.user?.name || '—' }}</td>
                                    <td class="py-3 px-2">{{ c.opener?.name || '—' }}</td>
                                    <td class="py-3 px-2 text-right font-mono">{{ Number(c.monto_inicial).toFixed(2) }}</td>
                                    <td class="py-3 px-2 text-right font-mono font-semibold text-indigo-300">{{ Number(c.monto_sistema).toFixed(2) }}</td>
                                    <td class="py-3 px-2 text-right font-mono font-semibold"
                                        :class="(Number(c.diferencia) || 0) >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                                    >
                                        {{ formatDiff(c.diferencia) }}
                                    </td>
                                    <td class="py-3 px-2 text-xs">{{ c.tiempo_apertura ? new Date(c.tiempo_apertura).toLocaleString() : '—' }}</td>
                                    <td class="py-3 px-2 text-xs">{{ c.tiempo_cierre ? new Date(c.tiempo_cierre).toLocaleString() : '—' }}</td>
                                    <td class="py-3 px-2">
                                        <span
                                            class="text-[10px] font-bold px-2.5 py-1 rounded-full uppercase"
                                            :class="c.estado === 'abierto'
                                                ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                                                : 'bg-stone-500/10 text-stone-400 border border-stone-500/20'"
                                        >
                                            {{ c.estado }}
                                        </span>
                                    </td>
                                    <td class="py-3 pl-2 text-center">
                                        <button
                                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-lg text-xs font-semibold transition cursor-pointer"
                                            @click="verApertura(c)"
                                        >
                                            Ver
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: CRÉDITOS -->
            <div v-if="activeTab === 'creditos'">
                <div class="rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 p-6 shadow-xl backdrop-blur-md">
                    <h3 class="font-bold text-[var(--text-primary)] mb-1">Créditos Vigentes</h3>
                    <p class="text-xs text-[var(--text-secondary)] mb-4">Ventas a crédito con cuotas por cobrar.</p>

                    <div v-if="creditosPendientes.length === 0" class="text-center py-12 text-sm text-[var(--text-secondary)]">
                        No hay créditos pendientes en el sistema.
                    </div>

                    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="credit in creditosPendientes"
                            :key="credit.id"
                            class="p-5 rounded-2xl bg-white/5 border border-white/5 hover:bg-white/10 hover:border-white/10 transition cursor-pointer flex flex-col justify-between"
                            @click="viewCreditDetails(credit)"
                        >
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="text-xs font-semibold text-indigo-300 font-mono">Venta #{{ credit.id }}</span>
                                    <h4 class="text-sm font-bold text-[var(--text-primary)] mt-0.5">{{ credit.cliente?.name || 'Cliente sin nombre' }}</h4>
                                </div>
                                <span class="text-xs font-bold bg-amber-500/10 border border-amber-500/20 text-amber-400 px-2 py-0.5 rounded-full">Crédito</span>
                            </div>
                            <div class="flex justify-between items-center mt-4 pt-3 border-t border-white/5">
                                <span class="text-xs text-[var(--text-secondary)]">{{ credit.venta_cuotas.filter(c => c.estado === 'pendiente').length }} cuotas pend.</span>
                                <span class="text-sm font-extrabold text-[var(--text-primary)]">{{ Number(credit.monto_final).toFixed(2) }} Bs</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL ABRIR CAJA -->
        <DialogModal :show="showOpenModal" @close="showOpenModal = false">
            <template #title>
                <h3 class="text-lg font-bold text-[var(--text-primary)]">Abrir Caja</h3>
            </template>
            <template #content>
                <form @submit.prevent="submitOpen" class="space-y-4">
                    <div>
                        <InputLabel value="Seleccionar Vendedor" />
                        <TextInput v-model="vendedorSearch" type="text" class="mt-1 w-full text-xs py-1.5" placeholder="Buscar vendedor..." />
                        <div class="mt-2 space-y-1 max-h-40 overflow-y-auto">
                            <button
                                v-for="v in filteredVendedores"
                                :key="v.id"
                                type="button"
                                class="w-full text-left px-3 py-2.5 rounded-xl text-sm font-semibold transition border cursor-pointer"
                                :class="openForm.vendedor_id === v.id
                                    ? 'bg-indigo-500/20 border-indigo-400/40 text-indigo-300'
                                    : 'bg-white/5 border-white/10 text-[var(--text-primary)] hover:bg-white/10'"
                                @click="openForm.vendedor_id = v.id; vendedorSearch = v.name"
                            >
                                {{ v.name }}
                                <span class="text-[var(--text-secondary)] font-normal ml-2">{{ v.email }}</span>
                            </button>
                            <div v-if="filteredVendedores.length === 0" class="text-center py-4 text-xs text-[var(--text-secondary)]">No se encontraron vendedores.</div>
                        </div>
                        <InputError :message="openForm.errors.vendedor_id" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Monto Inicial (Caja Chica)" />
                        <TextInput v-model="openForm.monto_inicial" type="number" step="0.01" class="mt-1 w-full" placeholder="0.00" required />
                        <InputError :message="openForm.errors.monto_inicial" class="mt-1" />
                    </div>
                </form>
            </template>
            <template #footer>
                <div class="flex items-center gap-3">
                    <SecondaryButton @click="showOpenModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': openForm.processing }" :disabled="openForm.processing || !openForm.vendedor_id" @click="submitOpen">
                        {{ openForm.processing ? 'Abriendo...' : '🔓 Abrir Caja' }}
                    </PrimaryButton>
                </div>
            </template>
        </DialogModal>

        <!-- MODAL CONFIRMAR CIERRE -->
        <DialogModal :show="showCloseModal" @close="showCloseModal = false">
            <template #title>
                <h3 class="text-lg font-bold text-[var(--text-primary)]">Confirmar Cierre de Caja</h3>
            </template>
            <template #content>
                <div class="space-y-4 py-2">
                    <div class="rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 px-4 py-3 text-sm font-semibold flex items-center gap-2">
                        ⚠️ Estás a punto de cerrar la caja #{{ cajaActiva?.id }}. Verifica los totales antes de confirmar.
                    </div>

                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[var(--text-secondary)] border-b border-[var(--border-color)]">
                                <th class="text-left py-2 font-semibold">Tipo</th>
                                <th class="text-right py-2 font-semibold">Sistema</th>
                                <th class="text-right py-2 font-semibold">Caja</th>
                                <th class="text-right py-2 font-semibold">Dif.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="tipo in tiposPago" :key="tipo" class="border-b border-[var(--border-color)]/50">
                                <td class="py-2 capitalize font-medium">{{ tipo }}</td>
                                <td class="py-2 text-right font-mono">{{ totalSistemaPorTipo(tipo).toFixed(2) }}</td>
                                <td class="py-2 text-right font-mono">{{ totalCajaPorTipo(tipo).toFixed(2) }}</td>
                                <td class="py-2 text-right font-mono font-semibold" :class="diferenciaPorTipo(tipo) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ diferenciaPorTipo(tipo) >= 0 ? '+' : '' }}{{ diferenciaPorTipo(tipo).toFixed(2) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="font-bold border-t-2 border-[var(--border-color)]">
                                <td class="py-2">Totales</td>
                                <td class="py-2 text-right font-mono">{{ totalSistemaGlobal.toFixed(2) }}</td>
                                <td class="py-2 text-right font-mono">{{ totalCajaGlobal.toFixed(2) }}</td>
                                <td class="py-2 text-right font-mono" :class="diferenciaGlobal >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ diferenciaGlobal >= 0 ? '+' : '' }}{{ diferenciaGlobal.toFixed(2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </template>
            <template #footer>
                <div class="flex items-center gap-3">
                    <SecondaryButton @click="showCloseModal = false">Cancelar</SecondaryButton>
                    <DangerButton :class="{ 'opacity-25': closeForm.processing }" :disabled="closeForm.processing" @click="submitClose">
                        {{ closeForm.processing ? 'Cerrando...' : '🔒 Sí, Cerrar Caja' }}
                    </DangerButton>
                </div>
            </template>
        </DialogModal>

        <!-- MODAL DETALLE APERTURA -->
        <DialogModal :show="showAperturaModal" max-width="lg" scrollable @close="showAperturaModal = false">
            <template #title>
                <h3 class="text-lg font-bold text-[var(--text-primary)]">Detalle de Apertura #{{ selectedApertura?.id }}</h3>
            </template>
            <template #content>
                <div v-if="selectedApertura" class="space-y-4 text-sm">
                    <div class="grid grid-cols-2 gap-4 p-4 rounded-2xl bg-white/5 border border-white/5">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[var(--text-secondary)] block">Vendedor</span>
                            <span class="font-bold text-[var(--text-primary)]">{{ selectedApertura.user?.name || '—' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[var(--text-secondary)] block">Abierta por</span>
                            <span class="font-bold text-[var(--text-primary)]">{{ selectedApertura.opener?.name || '—' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[var(--text-secondary)] block">Monto Inicial</span>
                            <span class="font-bold text-[var(--text-primary)]">{{ Number(selectedApertura.monto_inicial).toFixed(2) }} Bs</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[var(--text-secondary)] block">Total Sistema</span>
                            <span class="font-bold text-indigo-300">{{ Number(selectedApertura.monto_sistema).toFixed(2) }} Bs</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[var(--text-secondary)] block">Total Real</span>
                            <span class="font-bold text-[var(--text-primary)]">{{ Number(selectedApertura.monto_real || 0).toFixed(2) }} Bs</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[var(--text-secondary)] block">Diferencia</span>
                            <span class="font-bold font-mono" :class="(Number(selectedApertura.diferencia) || 0) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                {{ formatDiff(selectedApertura.diferencia) }} Bs
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[var(--text-secondary)] block">Apertura</span>
                            <span class="font-bold text-[var(--text-primary)] text-xs">{{ selectedApertura.tiempo_apertura ? new Date(selectedApertura.tiempo_apertura).toLocaleString() : '—' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[var(--text-secondary)] block">Cierre</span>
                            <span class="font-bold text-[var(--text-primary)] text-xs">{{ selectedApertura.tiempo_cierre ? new Date(selectedApertura.tiempo_cierre).toLocaleString() : '—' }}</span>
                        </div>
                    </div>

                    <h4 class="font-bold text-xs uppercase tracking-wider text-[var(--text-secondary)]">Arqueo por Tipo de Pago</h4>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[var(--text-secondary)] border-b border-[var(--border-color)]">
                                <th class="text-left py-2 font-semibold">Tipo</th>
                                <th class="text-right py-2 font-semibold">Sistema</th>
                                <th class="text-right py-2 font-semibold">Caja</th>
                                <th class="text-right py-2 font-semibold">Dif.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="tipo in tiposPago" :key="tipo" class="border-b border-[var(--border-color)]/50">
                                <td class="py-2 capitalize font-medium">{{ tipo }}</td>
                                <td class="py-2 text-right font-mono">{{ (selectedApertura.totales_sistema?.[tipo] || 0).toFixed(2) }}</td>
                                <td class="py-2 text-right font-mono">{{ (selectedApertura.totales_caja?.[tipo] || 0).toFixed(2) }}</td>
                                <td class="py-2 text-right font-mono font-semibold"
                                    :class="((selectedApertura.totales_caja?.[tipo] || 0) - (selectedApertura.totales_sistema?.[tipo] || 0)) >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                                >
                                    {{ ((selectedApertura.totales_caja?.[tipo] || 0) - (selectedApertura.totales_sistema?.[tipo] || 0)) >= 0 ? '+' : '' }}
                                    {{ ((selectedApertura.totales_caja?.[tipo] || 0) - (selectedApertura.totales_sistema?.[tipo] || 0)).toFixed(2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showAperturaModal = false">Cerrar</SecondaryButton>
            </template>
        </DialogModal>

        <!-- MODAL DETALLE CRÉDITO -->
        <DialogModal :show="showCreditDetailModal" @close="showCreditDetailModal = false">
            <template #title>
                <h3 class="text-lg font-bold text-[var(--text-primary)]">Detalles de Crédito - Venta #{{ selectedCredit?.id }}</h3>
            </template>
            <template #content>
                <div v-if="selectedCredit" class="space-y-4 text-sm">
                    <div class="grid grid-cols-2 gap-4 p-4 rounded-2xl bg-white/5 border border-white/5">
                        <div><span class="text-[10px] uppercase font-bold text-[var(--text-secondary)] block">Cliente</span><span class="font-bold text-[var(--text-primary)]">{{ selectedCredit.cliente?.name }}</span></div>
                        <div><span class="text-[10px] uppercase font-bold text-[var(--text-secondary)] block">Monto Total</span><span class="font-bold text-indigo-300">{{ Number(selectedCredit.monto_final).toFixed(2) }} Bs</span></div>
                    </div>
                    <h4 class="font-bold text-xs uppercase tracking-wider text-[var(--text-secondary)]">Plan de Cuotas</h4>
                    <div class="space-y-2.5">
                        <div v-for="cuota in selectedCredit.venta_cuotas" :key="cuota.id" class="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/5">
                            <div>
                                <span class="font-bold text-xs">Cuota #{{ cuota.nro_cuota }}</span>
                                <span class="text-xs text-[var(--text-secondary)] block mt-0.5">Monto: {{ Number(cuota.sub_monto).toFixed(2) }} Bs</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span v-if="cuota.estado === 'pagado'" class="text-xs font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 rounded-full">
                                    ✓ Pagada ({{ new Date(cuota.fecha_pago).toLocaleDateString() }})
                                </span>
                                <div v-else class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-amber-400 bg-amber-500/10 border border-amber-500/20 px-3 py-1 rounded-full">Pendiente</span>
                                    <button v-if="cajaActiva" class="text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition cursor-pointer" @click="payInstallment(cuota.id)">Cobrar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showCreditDetailModal = false">Cerrar</SecondaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';

const props = defineProps({
    promociones: {
        type: Array,
        required: true,
    }
});

const showFormModal = ref(false);
const editingPromocion = ref(null);

const form = useForm({
    nombre_promo: '',
    codigo_promo: '',
    descuento: 0,
    tipo_descuento: 'porcentaje',
    fecha_inicio: '',
    fecha_fin: '',
});

const openCreateModal = () => {
    editingPromocion.value = null;
    form.reset();
    form.clearErrors();
    showFormModal.value = true;
};

const openEditModal = (promocion) => {
    editingPromocion.value = promocion;
    form.clearErrors();
    form.nombre_promo = promocion.nombre_promo;
    form.codigo_promo = promocion.codigo_promo;
    form.descuento = Number(promocion.descuento);
    form.tipo_descuento = promocion.tipo_descuento;
    form.fecha_inicio = promocion.fecha_inicio ? promocion.fecha_inicio.substring(0, 10) : '';
    form.fecha_fin = promocion.fecha_fin ? promocion.fecha_fin.substring(0, 10) : '';
    showFormModal.value = true;
};

const submit = () => {
    if (editingPromocion.value) {
        form.put(route('promociones.update', editingPromocion.value.id), {
            onSuccess: () => {
                showFormModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('promociones.store'), {
            onSuccess: () => {
                showFormModal.value = false;
                form.reset();
            }
        });
    }
};

const deletePromocion = (id) => {
    if (confirm('¿Estás seguro de que deseas eliminar esta promoción?')) {
        form.delete(route('promociones.destroy', id));
    }
};

const getStatus = (promo) => {
    const today = new Date().toISOString().substring(0, 10);
    const start = promo.fecha_inicio ? promo.fecha_inicio.substring(0, 10) : '';
    const end = promo.fecha_fin ? promo.fecha_fin.substring(0, 10) : '';

    if (today < start) {
        return { label: 'Programada', class: 'bg-amber-500/10 text-amber-400 border-amber-500/20' };
    } else if (today > end) {
        return { label: 'Vencida', class: 'bg-rose-500/10 text-rose-400 border-rose-500/20' };
    } else {
        return { label: 'Vigente', class: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' };
    }
};
</script>

<template>
    <AppLayout title="Promociones">
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-[var(--text-primary)]">Gestión de Promociones</h1>
                    <p class="text-sm text-[var(--text-secondary)]">Crea y administra cupones y descuentos globales para ventas.</p>
                </div>
                <button
                    class="rounded-xl bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white px-4 py-2 text-sm font-semibold transition shadow-lg cursor-pointer flex items-center gap-2"
                    @click="openCreateModal"
                >
                    <span>➕ Nueva Promoción</span>
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6 pb-12">
            <!-- Tabla de Promociones -->
            <div class="rounded-3xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/40 overflow-hidden shadow-2xl backdrop-blur-md">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-[var(--border-color)] bg-white/5 text-[var(--text-primary)] font-bold">
                                <th class="px-6 py-4">Nombre</th>
                                <th class="px-6 py-4">Código de Cupón</th>
                                <th class="px-6 py-4 text-right">Descuento</th>
                                <th class="px-6 py-4">Vigencia</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="promociones.length === 0" class="border-b border-[var(--border-color)] text-center text-[var(--text-secondary)]">
                                <td colspan="6" class="px-6 py-12">No hay promociones registradas.</td>
                            </tr>
                            <tr
                                v-for="promo in promociones"
                                :key="promo.id"
                                class="border-b border-[var(--border-color)] last:border-0 hover:bg-white/5 transition-colors duration-200"
                            >
                                <td class="px-6 py-4 font-semibold text-[var(--text-primary)]">
                                    {{ promo.nombre_promo }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs">
                                    <span class="bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 px-2.5 py-1 rounded-lg">
                                        {{ promo.codigo_promo }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-indigo-300">
                                    {{ Number(promo.descuento) }}{{ promo.tipo_descuento === 'porcentaje' ? '%' : ' Bs' }}
                                </td>
                                <td class="px-6 py-4 text-xs text-[var(--text-secondary)]">
                                    {{ new Date(promo.fecha_inicio).toLocaleDateString() }} - {{ new Date(promo.fecha_fin).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <span :class="['px-2.5 py-1 rounded-full border font-semibold', getStatus(promo).class]">
                                        {{ getStatus(promo).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <button
                                            class="text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                                            @click="openEditModal(promo)"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            class="text-rose-400 hover:text-rose-300 bg-rose-500/10 hover:bg-rose-500/20 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                                            @click="deletePromocion(promo.id)"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de Formulario (Crear/Editar) -->
        <DialogModal :show="showFormModal" @close="showFormModal = false">
            <template #title>
                <h3 class="text-lg font-bold text-[var(--text-primary)]">
                    {{ editingPromocion ? 'Editar Promoción' : 'Nueva Promoción' }}
                </h3>
            </template>

            <template #content>
                <div class="space-y-4">
                    <!-- Nombre -->
                    <div>
                        <InputLabel for="nombre_promo" value="Nombre de la Promoción" />
                        <TextInput
                            id="nombre_promo"
                            v-model="form.nombre_promo"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Ej. Descuento Aniversario"
                            required
                        />
                        <InputError :message="form.errors.nombre_promo" class="mt-2" />
                    </div>

                    <!-- Código -->
                    <div>
                        <InputLabel for="codigo_promo" value="Código de Cupón" />
                        <TextInput
                            id="codigo_promo"
                            v-model="form.codigo_promo"
                            type="text"
                            class="mt-1 block w-full font-mono uppercase"
                            placeholder="Ej. ANIVERSARIO15"
                            required
                        />
                        <InputError :message="form.errors.codigo_promo" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Descuento -->
                        <div>
                            <InputLabel for="descuento" value="Descuento" />
                            <TextInput
                                id="descuento"
                                v-model="form.descuento"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.descuento" class="mt-2" />
                        </div>

                        <!-- Tipo de Descuento -->
                        <div>
                            <InputLabel for="tipo_descuento" value="Tipo de Descuento" />
                            <select
                                id="tipo_descuento"
                                v-model="form.tipo_descuento"
                                class="mt-1 block w-full rounded-xl border-[var(--border-color)] bg-[var(--bg-secondary)] text-[var(--text-primary)] focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm p-2.5 transition"
                            >
                                <option value="porcentaje">Porcentaje (%)</option>
                                <option value="monto">Monto Fijo (Bs)</option>
                            </select>
                            <InputError :message="form.errors.tipo_descuento" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Fecha Inicio -->
                        <div>
                            <InputLabel for="fecha_inicio" value="Fecha de Inicio" />
                            <TextInput
                                id="fecha_inicio"
                                v-model="form.fecha_inicio"
                                type="date"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.fecha_inicio" class="mt-2" />
                        </div>

                        <!-- Fecha Fin -->
                        <div>
                            <InputLabel for="fecha_fin" value="Fecha de Fin" />
                            <TextInput
                                id="fecha_fin"
                                v-model="form.fecha_fin"
                                type="date"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.fecha_fin" class="mt-2" />
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex items-center gap-3">
                    <SecondaryButton @click="showFormModal = false">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="submit">
                        {{ editingPromocion ? 'Guardar Cambios' : 'Crear Promoción' }}
                    </PrimaryButton>
                </div>
            </template>
        </DialogModal>
    </AppLayout>
</template>

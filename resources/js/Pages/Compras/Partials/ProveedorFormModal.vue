<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    show: Boolean,
    proveedor: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    nombre: '',
    telefono: '',
    descripcion: '',
});

const modalTitle = computed(() => props.proveedor ? 'Editar proveedor' : 'Nuevo proveedor');

const resetForm = () => {
    form.reset();
};

const fillForm = (proveedor) => {
    form.nombre = proveedor.nombre;
    form.telefono = proveedor.telefono;
    form.descripcion = proveedor.descripcion || '';
};

watch(() => props.show, (visible) => {
    if (! visible) {
        return;
    }

    if (props.proveedor) {
        fillForm(props.proveedor);
        return;
    }

    resetForm();
});

const submit = () => {
    if (props.proveedor) {
        form.put(route('proveedores.update', props.proveedor.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
        return;
    }

    form.post(route('proveedores.store'), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
};

const close = () => emit('close');
</script>

<template>
    <DialogModal :show="show" max-width="lg" scrollable @close="close">
        <template #title>{{ modalTitle }}</template>

        <template #content>
            <div class="space-y-5">
                <section class="space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--accent)]">Datos del proveedor</h3>
                    <div class="grid gap-3">
                        <div>
                            <InputLabel value="Nombre" />
                            <TextInput v-model="form.nombre" class="mt-1 block w-full max-w-md" />
                            <InputError :message="form.errors.nombre" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Telefono" />
                            <TextInput v-model="form.telefono" class="mt-1 block w-full max-w-md" />
                            <InputError :message="form.errors.telefono" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Descripcion" />
                            <textarea
                                v-model="form.descripcion"
                                rows="3"
                                class="mt-1 block w-full max-w-lg rounded-xl border border-[var(--border-color)] bg-[var(--bg-primary)] text-[var(--text-primary)] placeholder-[var(--text-secondary)]/50 focus:border-[var(--accent)] focus:ring-[var(--accent)] text-sm shadow-sm p-2.5 focus:outline-none"
                                placeholder="Notas del proveedor..."
                            />
                            <InputError :message="form.errors.descripcion" class="mt-1" />
                        </div>
                    </div>
                </section>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end gap-2">
                <SecondaryButton @click="close">Cancelar</SecondaryButton>
                <PrimaryButton :disabled="form.processing" @click="submit">Guardar</PrimaryButton>
            </div>
        </template>
    </DialogModal>
</template>

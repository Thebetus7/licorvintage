<script setup>
import Modal from './Modal.vue';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    scrollable: {
        type: Boolean,
        default: false,
    },
});

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <div
            class="flex flex-col overflow-hidden text-[var(--text-primary)]"
            :class="scrollable ? 'max-h-[min(90vh,720px)]' : ''"
        >
            <div
                class="shrink-0 px-6 py-4"
                :class="scrollable ? 'border-b border-[var(--border-color)] bg-transparent' : ''"
            >
                <div class="text-lg font-bold text-[var(--text-primary)]">
                    <slot name="title" />
                </div>

                <div v-if="!scrollable" class="mt-4 text-sm text-[var(--text-secondary)]">
                    <slot name="content" />
                </div>
            </div>

            <div
                v-if="scrollable"
                class="min-h-0 flex-1 overflow-y-auto px-6 py-4 text-sm text-[var(--text-secondary)]"
            >
                <slot name="content" />
            </div>

            <div
                class="shrink-0 text-end"
                :class="scrollable ? 'border-t border-[var(--border-color)] px-6 py-3 bg-[var(--bg-primary)]/40' : 'flex flex-row justify-end px-6 py-4 bg-[var(--bg-primary)]/20 border-t border-[var(--border-color)]'"
            >
                <slot name="footer" />
            </div>
        </div>
    </Modal>
</template>

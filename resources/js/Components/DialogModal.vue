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
            class="flex flex-col overflow-hidden"
            :class="scrollable ? 'max-h-[min(90vh,720px)]' : ''"
        >
            <div
                class="shrink-0 px-6 py-4"
                :class="scrollable ? 'border-b border-stone-800/80 bg-transparent' : ''"
            >
                <div class="text-lg font-bold text-amber-200">
                    <slot name="title" />
                </div>

                <div v-if="!scrollable" class="mt-4 text-sm text-stone-300">
                    <slot name="content" />
                </div>
            </div>

            <div
                v-if="scrollable"
                class="min-h-0 flex-1 overflow-y-auto px-6 py-4 text-sm text-stone-300"
            >
                <slot name="content" />
            </div>

            <div
                class="shrink-0 text-end"
                :class="scrollable ? 'border-t border-stone-800/80 px-6 py-3 bg-[#241518]/20' : 'flex flex-row justify-end px-6 py-4 bg-[#241518]/30 border-t border-stone-800/40'"
            >
                <slot name="footer" />
            </div>
        </div>
    </Modal>
</template>

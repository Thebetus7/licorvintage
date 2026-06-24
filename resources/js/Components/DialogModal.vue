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
                :class="scrollable ? 'border-b border-stone-200 bg-white' : ''"
            >
                <div class="text-lg font-semibold text-[#2b1115]">
                    <slot name="title" />
                </div>

                <div v-if="!scrollable" class="mt-4 text-sm text-stone-600">
                    <slot name="content" />
                </div>
            </div>

            <div
                v-if="scrollable"
                class="min-h-0 flex-1 overflow-y-auto px-6 py-4 text-sm text-stone-600"
            >
                <slot name="content" />
            </div>

            <div
                class="shrink-0 bg-stone-50 text-end"
                :class="scrollable ? 'border-t border-stone-200 px-6 py-3' : 'flex flex-row justify-end px-6 py-4 bg-gray-100'"
            >
                <slot name="footer" />
            </div>
        </div>
    </Modal>
</template>

<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 bg-[#2b1115]/30 border border-stone-800 hover:bg-[#2b1115]/60 hover:border-amber-900/30 rounded-md font-semibold text-xs text-amber-200 uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-stone-900 disabled:opacity-25 transition ease-in-out duration-150">
        <slot />
    </button>
</template>

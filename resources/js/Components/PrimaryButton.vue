<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-amber-600 to-amber-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:from-amber-500 hover:to-amber-600 focus:from-amber-500 focus:to-amber-600 active:from-amber-700 active:to-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-stone-900 disabled:opacity-50 transition ease-in-out duration-150 shadow-md shadow-amber-950/20">
        <slot />
    </button>
</template>

<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center justify-center px-4 py-2 bg-[var(--accent)] hover:bg-[var(--accent-hover)] border border-transparent rounded-xl font-semibold text-xs text-[var(--bg-primary)] uppercase tracking-widest active:opacity-80 focus:outline-none focus:ring-2 focus:ring-[var(--accent)] focus:ring-offset-2 focus:ring-offset-[var(--bg-primary)] disabled:opacity-50 transition ease-in-out duration-150 shadow-md shadow-[var(--accent)]/10">
        <slot />
    </button>
</template>

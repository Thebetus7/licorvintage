<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center justify-center px-4 py-2 bg-[var(--bg-secondary)]/50 border border-[var(--border-color)] hover:bg-[var(--bg-secondary)] hover:border-[var(--accent)] hover:text-[var(--accent)] rounded-xl font-semibold text-xs text-[var(--text-primary)] uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-[var(--accent)] focus:ring-offset-2 focus:ring-offset-[var(--bg-primary)] disabled:opacity-25 transition ease-in-out duration-150">
        <slot />
    </button>
</template>

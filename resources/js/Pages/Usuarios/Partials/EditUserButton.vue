<script setup>
</script>

<template>
    <button class="px-3 py-1 text-xs font-semibold rounded-lg border border-[var(--border-color)] bg-[var(--bg-secondary)] text-[var(--accent)] hover:bg-[var(--accent)]/10 hover:border-[var(--accent)] hover:text-[var(--text-primary)] transition-all duration-200 shadow-sm cursor-pointer inline-flex items-center justify-center">
        <slot>✏️ Editar</slot>
    </button>
</template>

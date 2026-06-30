<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link :href="'/'" class="flex flex-col items-center gap-2">
        <div class="h-16 w-16 rounded-xl bg-gradient-to-tr from-[var(--accent)] to-[var(--bg-secondary)] flex items-center justify-center shadow-lg border border-[var(--border-color)] hover:scale-105 transition duration-200">
            <span class="text-3xl font-extrabold text-white">LV</span>
        </div>
        <span class="text-xl font-bold tracking-wider text-[var(--text-primary)] mt-2">Licor Vintage</span>
    </Link>
</template>

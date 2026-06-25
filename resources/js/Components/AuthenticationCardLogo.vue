<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link :href="'/'" class="flex flex-col items-center gap-2">
        <div class="h-16 w-16 rounded-xl bg-gradient-to-tr from-amber-500 to-[#5a1c24] flex items-center justify-center shadow-lg shadow-amber-950/50 hover:scale-105 transition duration-200">
            <span class="text-3xl font-extrabold text-amber-100">LV</span>
        </div>
        <span class="text-xl font-bold tracking-wider text-amber-200 mt-2">Licor Vintage</span>
    </Link>
</template>

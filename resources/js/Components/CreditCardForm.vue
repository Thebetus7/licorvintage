<script setup>
import { ref, computed } from 'vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({ number: '', expiry: '', cvc: '' }),
    },
});

const emit = defineEmits(['update:modelValue']);

const errors = ref({ number: '', expiry: '', cvc: '' });

const cardData = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

function formatNumber(val) {
    const digits = val.replace(/\D/g, '').slice(0, 16);
    const groups = digits.match(/.{1,4}/g);
    return groups ? groups.join(' ') : '';
}

function onNumberInput(e) {
    const raw = e.target.value;
    const digits = raw.replace(/\D/g, '').slice(0, 16);
    cardData.value.number = digits;
    errors.value.number = '';
    e.target.value = formatNumber(cardData.value.number);
}

function onExpiryInput(e) {
    let raw = e.target.value.replace(/\D/g, '').slice(0, 4);
    if (raw.length >= 2) {
        raw = raw.slice(0, 2) + '/' + raw.slice(2);
    }
    cardData.value.expiry = raw.replace(/\D/g, '');
    errors.value.expiry = '';
    e.target.value = raw;
}

function onCvcInput(e) {
    let raw = e.target.value.replace(/\D/g, '').slice(0, 3);
    cardData.value.cvc = raw;
    errors.value.cvc = '';
    e.target.value = raw;
}

const cardBrand = computed(() => {
    const n = cardData.value.number || '';
    if (n.startsWith('4')) return { name: 'Visa', color: 'from-blue-600 to-blue-800' };
    if (/^5[1-5]/.test(n)) return { name: 'Mastercard', color: 'from-orange-500 to-red-600' };
    if (/^3[47]/.test(n)) return { name: 'Amex', color: 'from-cyan-700 to-blue-900' };
    if (/^6(?:011|4[4-9]|5)/.test(n)) return { name: 'Discover', color: 'from-stone-700 to-stone-900' };
    return { name: '', color: 'from-indigo-800 to-indigo-950' };
});

const displayNumber = computed(() => {
    const n = cardData.value.number || '';
    return n.padEnd(16, '•').replace(/(.{4})/g, '$1 ').trim();
});

const displayExpiry = computed(() => {
    const e = cardData.value.expiry || '';
    if (e.length >= 2) return e.slice(0, 2) + '/' + e.slice(2);
    return e;
});

function validate() {
    errors.value = { number: '', expiry: '', cvc: '' };
    let valid = true;
    if (!cardData.value.number || cardData.value.number.length < 13) {
        errors.value.number = 'Número de tarjeta inválido';
        valid = false;
    }
    if (!cardData.value.expiry || cardData.value.expiry.length < 4) {
        errors.value.expiry = 'Fecha inválida';
        valid = false;
    }
    if (!cardData.value.cvc || cardData.value.cvc.length < 3) {
        errors.value.cvc = 'CVC inválido';
        valid = false;
    }
    return valid;
}

defineExpose({ validate, errors });
</script>

<template>
    <div class="w-full max-w-md mx-auto space-y-4">
        <!-- Card Visual -->
        <div class="relative w-full rounded-2xl p-6 text-white shadow-2xl bg-gradient-to-br shadow-black/40"
             :class="cardBrand.color"
             style="min-height: 200px;">
            <!-- Card Chip -->
            <div class="flex items-center justify-between mb-8">
                <div class="w-10 h-7 rounded-md bg-yellow-300/80 border border-yellow-200/40 shadow-inner"></div>
                <div v-if="cardBrand.name" class="text-xs font-bold tracking-widest opacity-80">{{ cardBrand.name }}</div>
            </div>

            <!-- Card Number -->
            <div class="mb-6">
                <p class="text-xs tracking-widest opacity-60 mb-1">NÚMERO DE TARJETA</p>
                <p class="text-xl font-mono tracking-widest drop-shadow-sm">{{ displayNumber }}</p>
            </div>

            <!-- Expiry + CVC -->
            <div class="flex gap-8">
                <div>
                    <p class="text-xs tracking-widest opacity-60 mb-1">VENCE</p>
                    <p class="text-sm font-mono tracking-wider">{{ displayExpiry || 'MM/AA' }}</p>
                </div>
                <div>
                    <p class="text-xs tracking-widest opacity-60 mb-1">CVC</p>
                    <p class="text-sm font-mono tracking-wider">{{ cardData.cvc ? '•'.repeat(cardData.cvc.length) : '•••' }}</p>
                </div>
            </div>
        </div>

        <!-- Inputs -->
        <div class="space-y-3">
            <div>
                <label class="mb-1 block text-xs font-semibold text-[var(--text-secondary)]">Número de tarjeta</label>
                <TextInput type="text" class="w-full font-mono tracking-wider"
                    placeholder="4242 4242 4242 4242"
                    maxlength="19"
                    inputmode="numeric"
                    @input="onNumberInput"
                    :value="formatNumber(cardData.number)" />
                <p v-if="errors.number" class="mt-1 text-xs text-rose-400">{{ errors.number }}</p>
            </div>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="mb-1 block text-xs font-semibold text-[var(--text-secondary)]">Vencimiento</label>
                    <TextInput type="text" class="w-full font-mono"
                        placeholder="MM/AA"
                        maxlength="5"
                        inputmode="numeric"
                        @input="onExpiryInput"
                        :value="displayExpiry" />
                    <p v-if="errors.expiry" class="mt-1 text-xs text-rose-400">{{ errors.expiry }}</p>
                </div>
                <div class="w-24">
                    <label class="mb-1 block text-xs font-semibold text-[var(--text-secondary)]">CVC</label>
                    <TextInput type="text" class="w-full font-mono"
                        placeholder="123"
                        maxlength="3"
                        inputmode="numeric"
                        @input="onCvcInput"
                        :value="cardData.cvc" />
                    <p v-if="errors.cvc" class="mt-1 text-xs text-rose-400">{{ errors.cvc }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

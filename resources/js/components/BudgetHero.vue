<script setup lang="ts">
import { formatIdr } from '@/lib/format';

defineProps<{
    amount: number;
    editable?: boolean;
}>();

const emit = defineEmits<{ edit: [] }>();
</script>

<template>
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-500 to-violet-500 p-6 text-white shadow-lg shadow-indigo-200 sm:p-8">
        <div
            class="pointer-events-none absolute -top-10 -right-10 h-40 w-40 rounded-full bg-white/10"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute -bottom-14 -left-6 h-36 w-36 rounded-full bg-white/5"
            aria-hidden="true"
        />

        <p class="text-xs font-semibold tracking-widest text-indigo-100 uppercase">Current Budget</p>
        <p
            class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl"
            data-testid="budget-hero-amount"
        >
            {{ formatIdr(amount) }}
        </p>
        <p class="mt-2 text-sm text-indigo-100">Ready to spend on what matters most.</p>

        <button
            v-if="editable"
            type="button"
            class="absolute top-6 right-6 rounded-xl bg-white/15 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm transition-colors duration-150 hover:bg-white/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:top-8 sm:right-8"
            @click="emit('edit')"
        >
            Edit Budget
        </button>
    </div>
</template>

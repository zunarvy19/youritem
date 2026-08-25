<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    modelValue: number | null;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: number | null] }>();

const displayed = computed(() =>
    props.modelValue === null || Number.isNaN(props.modelValue)
        ? ''
        : new Intl.NumberFormat('id-ID').format(props.modelValue),
);

function onInput(event: Event): void {
    const digits = (event.target as HTMLInputElement).value.replace(/\D/g, '');
    const value = digits === '' ? null : Number(digits);

    if (value !== props.modelValue) {
        emit('update:modelValue', value);
    }

    // Keep the caret at the end; sufficient for amount-style inputs.
    const input = event.target as HTMLInputElement;
    const formatted =
        digits === ''
            ? ''
            : new Intl.NumberFormat('id-ID').format(Number(digits));

    if (input.value !== formatted) {
        input.value = formatted;
    }
}
</script>

<template>
    <div class="relative">
        <span
            class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-semibold text-neutral-400"
            aria-hidden="true"
        >
            Rp
        </span>
        <input
            type="text"
            inputmode="numeric"
            :value="displayed"
            class="input pl-10"
            v-bind="$attrs"
            @input="onInput"
        />
    </div>
</template>

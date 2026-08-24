<script setup lang="ts">
import { computed } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import type { IconName } from '@/components/icons';
import type { Priority } from '@/types';

const props = defineProps<{ value: Priority }>();

const styles = computed(() => ({
    HIGH: 'border-rose-200 bg-rose-50 text-rose-700',
    MEDIUM: 'border-indigo-200 bg-indigo-50 text-indigo-700',
    LOW: 'border-neutral-200 bg-neutral-100 text-neutral-600',
}[props.value]));

const labels = computed(() => ({
    HIGH: 'High',
    MEDIUM: 'Medium',
    LOW: 'Low',
}[props.value]));

const icons = computed(() => ({
    HIGH: 'sparkles',
    MEDIUM: 'clock',
    LOW: 'check',
})[props.value]);
</script>

<template>
    <span
        class="badge"
        :class="styles"
    >
        <AppIcon
            :name="icons as IconName"
            class="h-3 w-3"
        />
        {{ labels }}
        <span class="sr-only">priority</span>
    </span>
</template>

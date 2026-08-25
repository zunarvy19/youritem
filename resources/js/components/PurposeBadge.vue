<script setup lang="ts">
import { computed } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import type { Purpose } from '@/types';

const props = defineProps<{ value: Purpose }>();

const styles = computed(
    () =>
        ({
            NEED: 'border-emerald-200 bg-emerald-50 text-emerald-700',
            WANT: 'border-amber-200 bg-amber-50 text-amber-700',
        })[props.value],
);

const icon = computed(() => (props.value === 'NEED' ? 'check' : 'sparkles'));
</script>

<template>
    <span class="badge" :class="styles">
        <AppIcon :name="icon" class="h-3 w-3" />
        {{ value === 'NEED' ? 'Need' : 'Want' }}
    </span>
</template>

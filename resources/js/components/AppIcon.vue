<script setup lang="ts">
import { icons  } from '@/components/icons';
import type {IconName} from '@/components/icons';

defineProps<{ name: IconName }>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.8"
        stroke="currentColor"
        class="h-5 w-5 shrink-0"
        aria-hidden="true"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            :d="icons[name]"
        />
    </svg>
</template>

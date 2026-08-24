<script setup lang="ts">
defineProps<{
    title: string;
    subtitle?: string;
}>();
</script>

<template>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-neutral-900">{{ title }}</h1>
            <p
                v-if="subtitle"
                class="mt-1 text-sm text-neutral-500"
            >
                {{ subtitle }}
            </p>
        </div>
        <div
            v-if="$slots.actions"
            class="flex flex-wrap items-center gap-3"
        >
            <slot name="actions" />
        </div>
    </div>
</template>

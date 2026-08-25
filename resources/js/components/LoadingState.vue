<script setup lang="ts">
defineProps<{
    lines?: number;
}>();
</script>

<template>
    <div class="space-y-3" role="status" aria-label="Loading content">
        <div
            v-for="n in lines ?? 4"
            :key="n"
            class="animate-pulse rounded-xl bg-neutral-200/80"
            :style="{ height: '64px', animationDelay: `${n * 120}ms` }"
        />
        <span class="sr-only">Loading…</span>
    </div>
</template>

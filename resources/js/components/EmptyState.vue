<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
    icon?: 'heart' | 'bag' | 'clock';
}>();
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-neutral-300 bg-white px-6 py-16 text-center">
        <span
            v-if="icon"
            class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-400"
            aria-hidden="true"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-6 w-6"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    :d="
                        icon === 'bag'
                            ? 'M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z'
                            : icon === 'clock'
                                ? 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'
                                : 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z'
                    "
                />
            </svg>
        </span>
        <p class="text-base font-bold text-neutral-900">{{ title }}</p>
        <p
            v-if="description"
            class="mt-1 max-w-sm text-sm text-neutral-500"
        >
            {{ description }}
        </p>
        <div
            v-if="$slots.actions"
            class="mt-6 flex flex-wrap justify-center gap-3"
        >
            <slot name="actions" />
        </div>
    </div>
</template>

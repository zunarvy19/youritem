<script setup lang="ts">
defineProps<{
    message?: string;
}>();

const emit = defineEmits<{ retry: [] }>();
</script>

<template>
    <div
        class="rounded-2xl border border-rose-200 bg-rose-50 p-8 text-center"
        role="alert"
    >
        <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-rose-100 text-lg text-rose-500">
            !
        </span>
        <p class="mt-3 text-sm font-semibold text-rose-800">
            {{ message ?? 'Something went wrong.' }}
        </p>
        <p class="mt-1 text-xs text-rose-600">Please check your connection and try again.</p>
        <button
            type="button"
            class="btn-secondary btn-sm mt-4"
            @click="emit('retry')"
        >
            Try Again
        </button>
    </div>
</template>

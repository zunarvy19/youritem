<script setup lang="ts">
import { useToast } from '@/composables/useToast';

const { toasts, dismiss } = useToast();
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4"
            aria-live="polite"
        >
            <TransitionGroup
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="-translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex w-full max-w-sm items-center justify-between gap-3 rounded-2xl border px-4 py-3 text-sm shadow-lg"
                    :class="
                        toast.kind === 'success'
                            ? 'border-emerald-200 bg-white text-emerald-800'
                            : 'border-rose-200 bg-white text-rose-800'
                    "
                    role="status"
                >
                    <span class="flex items-center gap-2.5">
                        <span
                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white"
                            :class="
                                toast.kind === 'success'
                                    ? 'bg-emerald-500'
                                    : 'bg-rose-500'
                            "
                            aria-hidden="true"
                        >
                            {{ toast.kind === 'success' ? '✓' : '!' }}
                        </span>
                        <span>{{ toast.message }}</span>
                    </span>
                    <button
                        type="button"
                        class="text-xs font-semibold opacity-50 transition-opacity hover:opacity-100"
                        @click="dismiss(toast.id)"
                    >
                        Dismiss
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

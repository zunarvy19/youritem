<script setup lang="ts">
import { onMounted, ref } from 'vue';

defineProps<{
    title: string;
    maxWidth?: string;
}>();

const emit = defineEmits<{ close: [] }>();

const panel = ref<HTMLElement | null>(null);

onMounted(() => {
    panel.value?.focus();
});

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        emit('close');
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-end justify-center bg-neutral-950/50 p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
            :aria-label="title"
            @keydown="onKeydown"
            @click.self="emit('close')"
        >
            <Transition
                appear
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-4 scale-[0.98] opacity-0 sm:scale-95"
                enter-to-class="translate-y-0 scale-100 opacity-100"
            >
                <div
                    ref="panel"
                    tabindex="-1"
                    class="w-full rounded-t-3xl bg-white p-6 shadow-xl outline-none sm:rounded-2xl"
                    :class="maxWidth ?? 'max-w-md'"
                >
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <h2 class="text-lg font-bold text-neutral-900">
                            {{ title }}
                        </h2>
                        <button
                            type="button"
                            class="-mr-2 rounded-lg p-1.5 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-700"
                            aria-label="Close dialog"
                            @click="emit('close')"
                        >
                            ✕
                        </button>
                    </div>
                    <slot />
                </div>
            </Transition>
        </div>
    </Teleport>
</template>

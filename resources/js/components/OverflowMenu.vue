<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AppIcon from '@/components/AppIcon.vue';

export interface MenuItem {
    key: string;
    label: string;
    icon?: 'pencil' | 'archive' | 'restore' | 'trash';
    danger?: boolean;
}

defineProps<{ items: MenuItem[] }>();

const emit = defineEmits<{ select: [key: string] }>();

const open = ref(false);
const root = ref<HTMLElement | null>(null);

function toggle(): void {
    open.value = !open.value;
}

function choose(key: string): void {
    open.value = false;
    emit('select', key);
}

function onDocumentClick(event: MouseEvent): void {
    if (open.value && root.value && !root.value.contains(event.target as Node)) {
        open.value = false;
    }
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        open.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div
        ref="root"
        class="relative inline-block text-left"
    >
        <button
            type="button"
            class="rounded-lg p-2 text-neutral-400 transition-colors duration-150 hover:bg-neutral-100 hover:text-neutral-700"
            aria-haspopup="menu"
            :aria-expanded="open"
            aria-label="Item actions"
            @click.stop="toggle"
        >
            <AppIcon name="dots" />
        </button>

        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-if="open"
                class="absolute right-0 z-20 mt-1 w-40 origin-top-right overflow-hidden rounded-xl border border-neutral-200 bg-white py-1 shadow-lg"
                role="menu"
            >
                <button
                    v-for="item in items"
                    :key="item.key"
                    type="button"
                    role="menuitem"
                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-sm transition-colors duration-100 hover:bg-neutral-50"
                    :class="item.danger ? 'text-rose-600' : 'text-neutral-700'"
                    @click="choose(item.key)"
                >
                    <AppIcon
                        v-if="item.icon"
                        :name="item.icon"
                        class="h-4 w-4"
                    />
                    {{ item.label }}
                </button>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import AppIcon from '@/components/AppIcon.vue';

export interface MenuItem {
    key: string;
    label: string;
    icon?: 'eye' | 'pencil' | 'archive' | 'restore' | 'trash';
    danger?: boolean;
}

defineProps<{ items: MenuItem[] }>();

const emit = defineEmits<{ select: [key: string] }>();

const open = ref(false);
const root = ref<HTMLElement | null>(null);
const trigger = ref<HTMLButtonElement | null>(null);
const menu = ref<HTMLElement | null>(null);
const menuStyle = ref<Record<string, string>>({});

async function toggle(): Promise<void> {
    open.value = !open.value;

    if (!open.value || !trigger.value) {
        return;
    }

    const triggerRect = trigger.value.getBoundingClientRect();
    const menuWidth = 160;
    const viewportPadding = 8;

    menuStyle.value = {
        top: `${triggerRect.bottom + 4}px`,
        left: `${Math.min(
            Math.max(viewportPadding, triggerRect.right - menuWidth),
            window.innerWidth - menuWidth - viewportPadding,
        )}px`,
        width: `${menuWidth}px`,
    };

    await nextTick();

    if (menu.value) {
        const menuHeight = menu.value.offsetHeight;
        const spaceBelow = window.innerHeight - triggerRect.bottom;

        if (
            spaceBelow < menuHeight + viewportPadding &&
            triggerRect.top > menuHeight
        ) {
            menuStyle.value.top = `${triggerRect.top - menuHeight - 4}px`;
        }
    }
}

function choose(key: string): void {
    open.value = false;
    emit('select', key);
}

function onDocumentClick(event: MouseEvent): void {
    const target = event.target as Node;

    if (
        open.value &&
        root.value &&
        !root.value.contains(target) &&
        !menu.value?.contains(target)
    ) {
        open.value = false;
    }
}

function close(): void {
    open.value = false;
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        open.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
    window.addEventListener('resize', close);
    window.addEventListener('scroll', close, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
    window.removeEventListener('resize', close);
    window.removeEventListener('scroll', close, true);
});
</script>

<template>
    <div ref="root" class="relative inline-block text-left">
        <button
            ref="trigger"
            type="button"
            class="rounded-lg p-2 text-neutral-400 transition-colors duration-150 hover:bg-neutral-100 hover:text-neutral-700"
            aria-haspopup="menu"
            :aria-expanded="open"
            aria-label="Item actions"
            @click.stop="toggle"
        >
            <AppIcon name="dots" />
        </button>
    </div>

    <Teleport to="body">
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
                ref="menu"
                class="fixed z-50 origin-top-right overflow-hidden rounded-xl border border-neutral-200 bg-white py-1 shadow-lg"
                :style="menuStyle"
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
    </Teleport>
</template>

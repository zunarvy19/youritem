<script setup lang="ts">
import AppIcon from '@/components/AppIcon.vue';
import type { IconName } from '@/components/icons';

defineProps<{
    label: string;
    value: string;
    icon?: IconName;
    tone?: 'default' | 'indigo' | 'emerald' | 'violet' | 'rose' | 'amber';
    hint?: string;
}>();

const tones: Record<string, { bg: string; iconBg: string; text: string }> = {
    default: {
        bg: '',
        iconBg: 'bg-indigo-50 text-indigo-600',
        text: 'text-neutral-900',
    },
    indigo: {
        bg: 'border-indigo-200',
        iconBg: 'bg-indigo-50 text-indigo-600',
        text: 'text-indigo-700',
    },
    emerald: {
        bg: 'border-emerald-200',
        iconBg: 'bg-emerald-50 text-emerald-600',
        text: 'text-emerald-700',
    },
    violet: {
        bg: 'border-violet-200',
        iconBg: 'bg-violet-50 text-violet-600',
        text: 'text-violet-700',
    },
    rose: {
        bg: 'border-rose-200',
        iconBg: 'bg-rose-50 text-rose-500',
        text: 'text-rose-700',
    },
    amber: {
        bg: 'border-amber-200',
        iconBg: 'bg-amber-50 text-amber-600',
        text: 'text-amber-700',
    },
};
</script>

<template>
    <div class="card card-hover p-5" :class="tone ? tones[tone].bg : ''">
        <div class="flex items-center gap-3">
            <span
                v-if="icon"
                class="rounded-xl p-2"
                :class="tones[tone ?? 'default'].iconBg"
            >
                <AppIcon :name="icon" />
            </span>
            <p
                class="truncate text-xs font-semibold tracking-wide text-neutral-500 uppercase"
            >
                {{ label }}
            </p>
        </div>
        <p
            class="mt-3 text-2xl font-bold"
            :class="tones[tone ?? 'default'].text"
        >
            {{ value }}
        </p>
        <p v-if="hint" class="mt-1 text-xs text-neutral-400">
            {{ hint }}
        </p>
    </div>
</template>

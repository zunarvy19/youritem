<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import type { Locale } from '@/composables/useI18n';

const { locale, setLocale, t } = useI18n();

function change(event: Event): void {
    setLocale((event.target as HTMLSelectElement).value as Locale);
}
</script>

<template>
    <label
        class="inline-flex items-center gap-2 text-sm font-medium text-neutral-600"
    >
        <span class="sr-only">{{ t('language.label') }}</span>
        <select
            :value="locale"
            class="rounded-lg border border-neutral-200 bg-white px-2.5 py-1.5 text-sm text-neutral-700 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
            :aria-label="t('language.label')"
            @change="change"
        >
            <option value="en">EN</option>
            <option value="id">ID</option>
        </select>
    </label>
</template>

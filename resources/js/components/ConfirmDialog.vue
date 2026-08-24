<script setup lang="ts">
import AppModal from '@/components/AppModal.vue';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

defineProps<{
    open: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    danger?: boolean;
    processing?: boolean;
}>();

const emit = defineEmits<{ close: []; confirm: [] }>();
</script>

<template>
    <AppModal
        v-if="open"
        :title="title"
        max-width="max-w-sm"
        @close="emit('close')"
    >
        <p class="text-sm text-neutral-600">{{ message }}</p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" class="btn-secondary" @click="emit('close')">
                {{ cancelLabel ?? t('common.cancel') }}
            </button>
            <button
                type="button"
                :disabled="processing"
                class="btn"
                :class="danger ? 'btn-danger' : 'btn-primary'"
                @click="emit('confirm')"
            >
                {{ confirmLabel ?? t('common.save') }}
            </button>
        </div>
    </AppModal>
</template>

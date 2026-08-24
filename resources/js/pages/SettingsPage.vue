<script setup lang="ts">
import { useRouter } from 'vue-router';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useAuth } from '@/composables/useAuth';
import { useI18n } from '@/composables/useI18n';

const auth = useAuth();
const router = useRouter();
const { t } = useI18n();

async function handleLogout(): Promise<void> {
    await auth.logout();
    await router.push({ name: 'login' });
}
</script>

<template>
    <section>
        <PageHeader
            :title="t('settings.title')"
            :subtitle="t('settings.subtitle')"
        />

        <div class="card p-6">
            <div class="flex items-center gap-4">
                <span
                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 text-xl font-extrabold text-white"
                >
                    {{ auth.user.value?.name?.charAt(0)?.toUpperCase() }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-lg font-bold text-neutral-900">
                        {{ auth.user.value?.name }}
                    </p>
                    <p class="truncate text-sm text-neutral-500">
                        {{ auth.user.value?.email }}
                    </p>
                </div>
            </div>

            <dl
                class="mt-6 divide-y divide-neutral-100 border-t border-neutral-100 text-sm"
            >
                <div class="flex items-center justify-between py-3">
                    <dt class="text-neutral-500">{{ t('nav.account') }}</dt>
                    <dd class="font-medium text-neutral-900">
                        {{ t('settings.account_type') }}
                    </dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-neutral-500">
                        {{ t('settings.currency') }}
                    </dt>
                    <dd class="font-medium text-neutral-900">IDR (Rp)</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-neutral-500">{{ t('settings.data') }}</dt>
                    <dd class="font-medium text-neutral-900">
                        {{ t('settings.data_value') }}
                    </dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-neutral-500">{{ t('language.label') }}</dt>
                    <dd><LanguageSwitcher /></dd>
                </div>
            </dl>

            <button
                type="button"
                class="btn-secondary mt-6 w-full text-rose-600 hover:bg-rose-50"
                @click="handleLogout"
            >
                {{ t('auth.logout') }}
            </button>
        </div>
    </section>
</template>

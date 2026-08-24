<script setup lang="ts">
import { reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { useAuth } from '@/composables/useAuth';
import { useI18n } from '@/composables/useI18n';
import { ApiError } from '@/services/apiClient';

const auth = useAuth();
const router = useRouter();
const route = useRoute();
const { t } = useI18n();

const form = reactive({
    email: '',
    password: '',
});

const processing = ref(false);
const errorMessage = ref('');
const fieldErrors = ref<Record<string, string[]>>({});

async function submit(): Promise<void> {
    if (processing.value) {
        return;
    }

    processing.value = true;
    errorMessage.value = '';
    fieldErrors.value = {};

    try {
        await auth.login(form.email, form.password);
        const redirect = route.query.redirect;
        await router.push(
            typeof redirect === 'string' ? redirect : { name: 'dashboard' },
        );
    } catch (error) {
        if (error instanceof ApiError) {
            errorMessage.value =
                Object.values(error.errors)[0]?.[0] ?? error.message;
            fieldErrors.value = error.errors;
        } else {
            errorMessage.value = t('auth.login_error');
        }
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-neutral-50 px-4"
    >
        <div class="absolute top-4 right-4"><LanguageSwitcher /></div>
        <div class="mb-8 flex items-center gap-2.5">
            <span
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 text-sm font-extrabold text-white"
            >
                Yi
            </span>
            <span class="text-xl font-extrabold tracking-tight text-neutral-900"
                >YourItem</span
            >
        </div>

        <div class="w-full max-w-sm">
            <h1
                class="text-center text-2xl font-bold tracking-tight text-neutral-900"
            >
                {{ t('auth.welcome') }}
            </h1>
            <p class="mt-1 mb-6 text-center text-sm text-neutral-500">
                {{ t('auth.login_subtitle') }}
            </p>

            <div
                v-if="errorMessage"
                class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm font-medium text-rose-700"
                role="alert"
            >
                {{ errorMessage }}
            </div>

            <form
                class="card space-y-4 p-6"
                novalidate
                @submit.prevent="submit"
            >
                <div>
                    <label for="email" class="field-label">{{
                        t('auth.email')
                    }}</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        required
                        class="input"
                        :aria-invalid="fieldErrors.email ? 'true' : undefined"
                    />
                    <p v-if="fieldErrors.email" class="field-error">
                        {{ fieldErrors.email[0] }}
                    </p>
                </div>

                <div>
                    <label for="password" class="field-label">{{
                        t('auth.password')
                    }}</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="input"
                        :aria-invalid="
                            fieldErrors.password ? 'true' : undefined
                        "
                    />
                    <p v-if="fieldErrors.password" class="field-error">
                        {{ fieldErrors.password[0] }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="processing"
                    class="btn-primary w-full"
                >
                    {{ processing ? t('auth.signing_in') : t('auth.sign_in') }}
                </button>
            </form>

            <p class="mt-5 text-center text-sm text-neutral-500">
                {{ t('auth.no_account') }}
                <RouterLink
                    :to="{ name: 'register' }"
                    class="font-semibold text-indigo-600 underline-offset-2 hover:underline"
                >
                    {{ t('auth.create_account') }}
                </RouterLink>
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import BrandLogo from '@/components/BrandLogo.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { useAuth } from '@/composables/useAuth';
import { useI18n } from '@/composables/useI18n';
import { ApiError } from '@/services/apiClient';

const auth = useAuth();
const router = useRouter();
const { t } = useI18n();

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const processing = ref(false);
const errorMessage = ref('');
const fieldErrors = ref<Record<string, string[]>>({});

async function submit(): Promise<void> {
    if (processing.value) {
        return;
    }

    if (form.password !== form.password_confirmation) {
        fieldErrors.value = {
            password_confirmation: [t('auth.password_mismatch')],
        };

        return;
    }

    processing.value = true;
    errorMessage.value = '';
    fieldErrors.value = {};

    try {
        await auth.register(
            form.name,
            form.email,
            form.password,
            form.password_confirmation,
        );
        await router.push({ name: 'dashboard' });
    } catch (error) {
        if (error instanceof ApiError) {
            errorMessage.value =
                Object.values(error.errors)[0]?.[0] ?? error.message;
            fieldErrors.value = error.errors;
        } else {
            errorMessage.value = t('auth.register_error');
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
            <BrandLogo class="h-14 w-17" />
            <!-- <span class="text-xl font-extrabold tracking-tight text-neutral-900"
                >WiseBuy</span
            > -->
        </div>

        <div class="w-full max-w-sm">
            <h1
                class="text-center text-2xl font-bold tracking-tight text-neutral-900"
            >
                {{ t('auth.create_title') }}
            </h1>
            <p class="mt-1 mb-6 text-center text-sm text-neutral-500">
                {{ t('auth.create_subtitle') }}
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
                    <label for="name" class="field-label">{{
                        t('auth.name')
                    }}</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        autocomplete="name"
                        required
                        class="input"
                        :aria-invalid="fieldErrors.name ? 'true' : undefined"
                    />
                    <p v-if="fieldErrors.name" class="field-error">
                        {{ fieldErrors.name[0] }}
                    </p>
                </div>

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
                        autocomplete="new-password"
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

                <div>
                    <label for="password_confirmation" class="field-label">{{
                        t('auth.confirm_password')
                    }}</label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="input"
                        :aria-invalid="
                            fieldErrors.password_confirmation
                                ? 'true'
                                : undefined
                        "
                    />
                    <p
                        v-if="fieldErrors.password_confirmation"
                        class="field-error"
                    >
                        {{ fieldErrors.password_confirmation[0] }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="processing"
                    class="btn-primary w-full"
                >
                    {{
                        processing
                            ? t('auth.creating')
                            : t('auth.create_account')
                    }}
                </button>
            </form>

            <p class="mt-5 text-center text-sm text-neutral-500">
                {{ t('auth.have_account') }}
                <RouterLink
                    :to="{ name: 'login' }"
                    class="font-semibold text-indigo-600 underline-offset-2 hover:underline"
                >
                    {{ t('auth.sign_in') }}
                </RouterLink>
            </p>
        </div>
    </div>
</template>

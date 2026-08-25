import { ref } from 'vue';
import { ApiError } from '@/services/apiClient';
import {
    fetchCurrentUser,
    loginUser,
    logoutUser,
    registerUser,
} from '@/services/authService';
import type { AuthUser } from '@/types';

type AuthStatus = 'idle' | 'loading' | 'authenticated' | 'guest';

const user = ref<AuthUser | null>(null);
const status = ref<AuthStatus>('idle');

let initPromise: Promise<void> | null = null;

async function init(): Promise<void> {
    if (initPromise) {
        return initPromise;
    }

    initPromise = (async () => {
        status.value = 'loading';

        try {
            const response = await fetchCurrentUser();
            user.value = response.data;
            status.value = 'authenticated';
        } catch {
            user.value = null;
            status.value = 'guest';
        }
    })();

    return initPromise;
}

async function login(email: string, password: string): Promise<AuthUser> {
    const response = await loginUser({ email, password });
    await refresh();

    return response.data;
}

async function register(
    name: string,
    email: string,
    password: string,
    password_confirmation: string,
): Promise<AuthUser> {
    const response = await registerUser({
        name,
        email,
        password,
        password_confirmation,
    });
    await refresh();

    return response.data;
}

async function refresh(): Promise<void> {
    try {
        const response = await fetchCurrentUser();
        user.value = response.data;
        status.value = 'authenticated';
    } catch (error) {
        user.value = null;
        status.value = 'guest';

        throw error;
    }
}

async function logout(): Promise<void> {
    try {
        await logoutUser();
    } catch (error) {
        if (!(error instanceof ApiError && error.isUnauthenticated)) {
            throw error;
        }
    } finally {
        user.value = null;
        status.value = 'guest';
    }
}

export function useAuth() {
    return {
        user,
        status,
        isAuthenticated: () => status.value === 'authenticated',
        init,
        login,
        register,
        logout,
        refresh,
    };
}

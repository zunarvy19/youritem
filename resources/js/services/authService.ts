import { apiRequest } from '@/services/apiClient';
import type { ApiDataResponse, AuthUser } from '@/types';

interface CredentialsPayload {
    name?: string;
    email: string;
    password: string;
    password_confirmation?: string;
}

export function registerUser(
    payload: Required<
        Pick<
            CredentialsPayload,
            'name' | 'email' | 'password' | 'password_confirmation'
        >
    >,
): Promise<ApiDataResponse<AuthUser>> {
    return apiRequest('/api/register', {
        method: 'POST',
        body: payload,
    });
}

export function loginUser(
    payload: Pick<CredentialsPayload, 'email' | 'password'>,
): Promise<ApiDataResponse<AuthUser>> {
    return apiRequest('/api/login', {
        method: 'POST',
        body: payload,
    });
}

export function logoutUser(): Promise<{ message: string }> {
    return apiRequest('/api/logout', {
        method: 'POST',
        body: {},
    });
}

export function fetchCurrentUser(): Promise<ApiDataResponse<AuthUser>> {
    return apiRequest('/api/user');
}

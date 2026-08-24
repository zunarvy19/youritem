import { ref } from 'vue';

export type Toast = {
    id: number;
    kind: 'success' | 'error';
    message: string;
};

const toasts = ref<Toast[]>([]);
let nextId = 1;

function push(kind: Toast['kind'], message: string, durationMs = 3500): void {
    const id = nextId++;
    toasts.value.push({ id, kind, message });
    setTimeout(() => dismiss(id), durationMs);
}

function dismiss(id: number): void {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

export function useToast() {
    return {
        toasts,
        dismiss,
        success: (message: string) => push('success', message),
        error: (message: string) => push('error', message),
    };
}

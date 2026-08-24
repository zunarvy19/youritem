export * from './models';

export type ApiDataResponse<T> = {
    data: T;
    message?: string;
};

export type ApiListResponse<T> = {
    data: T[];
    meta?: Record<string, unknown>;
};

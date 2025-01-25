export interface ApiError {
    code: string;
    message: string;
    status: number;
}

export interface ApiResponse<T> {
    data: T | null;
    error: ApiError | null;
}

export interface UserData {
    id: number;
    name: string;
    email: string;
    roles: string[];
} 
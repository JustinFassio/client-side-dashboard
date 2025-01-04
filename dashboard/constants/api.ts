export const API_ROUTES = {
    PROFILE: '/custom/v1/profile',
} as const;

export type ApiRoute = typeof API_ROUTES[keyof typeof API_ROUTES]; 
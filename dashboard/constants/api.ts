export const API_ROUTES = {
    PROFILE: '/athlete-dashboard/v1/profile/user',
} as const;

export type ApiRoute = typeof API_ROUTES[keyof typeof API_ROUTES]; 
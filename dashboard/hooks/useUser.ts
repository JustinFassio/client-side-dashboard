import { useState, useEffect } from 'react';
import { ApiClient } from '../services/api';
import { API_ROUTES } from '../constants/api';
import { UserData } from '../types/api';
import { FeatureContext } from '../contracts/Feature';

export const useUser = (context: FeatureContext) => {
    const [user, setUser] = useState<UserData | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchUser = async () => {
            const api = ApiClient.getInstance(context);
            const { data, error } = await api.fetchWithCache<UserData>(API_ROUTES.PROFILE);

            if (error) {
                setError(error.message);
                setUser(null);
            } else {
                setUser(data);
                setError(null);
            }
            setIsLoading(false);
        };

        fetchUser();
    }, [context]);

    return { user, isLoading, error };
}; 
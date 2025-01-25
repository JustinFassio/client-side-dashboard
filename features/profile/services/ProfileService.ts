import { ProfileData, ProfileErrorCode } from '../types/profile';
import { ProfileConfig, getFullEndpointUrl } from '../config';
import { ApiClient } from '../../../dashboard/services/api';
import { FeatureContext } from '../../../dashboard/contracts/Feature';
import { AxiosInstance } from 'axios';

export class ProfileError extends Error {
    constructor(
        public readonly details: {
            code: ProfileErrorCode;
            message: string;
            status?: number;
        }
    ) {
        super(details.message);
        this.name = 'ProfileError';
    }
}

interface ApiError {
    code: string;
    message: string;
    status: number;
}

interface ApiResponse<T> {
    success: boolean;
    data: T;
    error?: ApiError;
}

interface ProfileApiResponse {
    id: number;
    profile: ProfileData;
}

type ProfileResponse = ApiResponse<ProfileApiResponse>;

export interface UserProfile {
    user_id: number;
    data: Record<string, any>;
}

export class ProfileService {
    private currentUserData: ProfileData | null = null;
    private readonly apiClient: ApiClient;
    private readonly nonce: string | null = null;

    constructor(apiClient: ApiClient, nonce?: string) {
        this.apiClient = apiClient;
        this.nonce = nonce || null;
    }

    public async fetchProfile(userId: number): Promise<ProfileData> {
        try {
            console.group('ProfileService: fetchProfile');
            console.log('Fetching profile for user:', userId);
            
            if (!userId) {
                throw new ProfileError({
                    code: 'VALIDATION_ERROR',
                    message: 'User ID is required',
                    status: 400
                });
            }
            
            const endpoint = `profile/user/${userId}`;
                
            console.log('API URL:', endpoint);
            console.log('Headers:', {
                'X-WP-Nonce': this.nonce ? '[PRESENT]' : '[MISSING]',
                'Content-Type': 'application/json'
            });
            
            const response = await this.apiClient.fetch<ProfileApiResponse>(endpoint);

            if (!response) {
                throw new ProfileError({
                    code: 'NETWORK_ERROR',
                    message: 'No response received from server',
                    status: 500
                });
            }

            if (response.error) {
                console.error('API Error:', response.error);
                throw new ProfileError({
                    code: response.error.code === 'validation_error' ? 'VALIDATION_ERROR' : 'NETWORK_ERROR',
                    message: response.error.message || 'Failed to fetch profile data',
                    status: response.error.status || (response.error.code === 'validation_error' ? 400 : 500)
                });
            }

            if (!response.data?.profile) {
                console.error('Invalid response:', response);
                throw new ProfileError({
                    code: 'INVALID_RESPONSE',
                    message: 'No profile data received from server',
                    status: 500
                });
            }

            const normalizedData = this.normalizeProfileData(response.data.profile);
            console.log('Normalized profile data:', normalizedData);
            console.groupEnd();
            return normalizedData;
        } catch (error) {
            console.error('Profile fetch error:', error);
            console.groupEnd();
            throw error instanceof ProfileError ? error : new ProfileError({
                code: error instanceof Error && error.message.includes('validation') ? 'VALIDATION_ERROR' : 'NETWORK_ERROR',
                message: error instanceof Error ? error.message : 'Failed to fetch profile data',
                status: error instanceof Error && error.message.includes('validation') ? 400 : 500
            });
        }
    }

    public async updateProfile(userId: number, data: Partial<ProfileData>): Promise<ProfileData> {
        try {
            console.group('ProfileService: updateProfile');
            console.log('Updating profile for user:', userId);
            
            const endpoint = `profile/user/${userId}`;
            console.log('API URL:', endpoint);
            console.log('Headers:', {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.nonce ? '[PRESENT]' : '[MISSING]'
            });

            const denormalizedData = this.denormalizeProfileData(data);
            console.log('Denormalized data for backend:', denormalizedData);
            
            const response = await this.apiClient.post<ProfileApiResponse>(endpoint, denormalizedData);

            if (response.error) {
                console.error('API Error:', response.error);
                throw new ProfileError({
                    code: response.error.code === 'validation_error' ? 'VALIDATION_ERROR' : 'NETWORK_ERROR',
                    message: response.error.message || 'Failed to update profile data',
                    status: response.error.status || (response.error.code === 'validation_error' ? 400 : 500)
                });
            }

            if (!response.data?.profile) {
                console.error('Invalid response:', response);
                throw new ProfileError({
                    code: 'INVALID_RESPONSE',
                    message: 'No profile data received from server',
                    status: 500
                });
            }

            const normalizedData = this.normalizeProfileData(response.data.profile);
            console.log('Normalized updated data:', normalizedData);
            console.groupEnd();
            return normalizedData;
        } catch (error) {
            console.error('Profile update error:', error);
            console.groupEnd();
            throw error instanceof ProfileError ? error : new ProfileError({
                code: error instanceof Error && error.message.includes('validation') ? 'VALIDATION_ERROR' : 'NETWORK_ERROR',
                message: error instanceof Error ? error.message : 'Failed to update profile data',
                status: error instanceof Error && error.message.includes('validation') ? 400 : 500
            });
        }
    }

    private normalizeProfileData(data: any): ProfileData {
        console.group('ProfileService: normalizeProfileData');
        console.log('Raw data received:', data);

        // Extract profile data from the response structure
        const profileData = data;
        console.log('Extracted profile data:', profileData);

        // Validate required fields
        const hasRequiredFields = 'user_login' in profileData || 'username' in profileData;
            
        if (!hasRequiredFields) {
            console.error('[ProfileService] Missing required fields in response');
            console.groupEnd();
            throw new ProfileError({
                code: 'INVALID_RESPONSE',
                message: 'Profile data is missing required fields',
                status: 500
            });
        }

        // Log raw field values for debugging
        console.log('Raw field values:', {
            username: {
                user_login: profileData.user_login,
                username: profileData.username
            },
            displayName: {
                display_name: profileData.display_name,
                name: profileData.name,
                displayName: profileData.displayName
            },
            email: {
                user_email: profileData.user_email,
                email: profileData.email
            }
        });

        // Convert string values to appropriate types
        const normalizedData: ProfileData = {
            // Core WordPress fields
            id: Number(profileData.id) || 0,
            username: profileData.user_login || profileData.username || '',
            email: profileData.user_email || profileData.email || '',
            displayName: profileData.display_name || profileData.displayName || '',
            firstName: profileData.first_name || profileData.firstName || '',
            lastName: profileData.last_name || profileData.lastName || '',
            nickname: profileData.nickname || '',
            roles: Array.isArray(profileData.roles) ? profileData.roles : [],

            // Physical measurements
            heightCm: Number(profileData.height_cm || profileData.heightCm) || 0,
            weightKg: Number(profileData.weight_kg || profileData.weightKg) || 0,
            experienceLevel: profileData.experience_level || profileData.experienceLevel || 'beginner',

            // Medical information
            medicalConditions: Array.isArray(profileData.medical_conditions || profileData.medicalConditions) ? profileData.medical_conditions || profileData.medicalConditions : [],
            exerciseLimitations: Array.isArray(profileData.exercise_limitations || profileData.exerciseLimitations) ? profileData.exercise_limitations || profileData.exerciseLimitations : [],
            medications: profileData.medications || '',
            medicalClearance: Boolean(profileData.medical_clearance || profileData.medicalClearance),
            medicalNotes: profileData.medical_notes || profileData.medicalNotes || '',

            // Custom profile fields
            phone: profileData.phone || '',
            age: Number(profileData.age) || 0,
            dateOfBirth: profileData.date_of_birth || profileData.dateOfBirth || '',
            gender: profileData.gender || '',
            dominantSide: profileData.dominant_side || profileData.dominantSide || '',
            emergencyContactName: profileData.emergency_contact_name || profileData.emergencyContactName || '',
            emergencyContactPhone: profileData.emergency_contact_phone || profileData.emergencyContactPhone || '',
            injuries: Array.isArray(profileData.injuries)
                ? profileData.injuries.map((injury: any) => ({
                    id: injury.id || String(Date.now()),
                    name: injury.name || '',
                    details: injury.details || '',
                    type: injury.type || 'general',
                    description: injury.description || injury.details || '',
                    date: injury.date || new Date().toISOString(),
                    severity: injury.severity || 'medium',
                    isCustom: true,
                    status: injury.status || 'active'
                }))
                : [],
            equipment: Array.isArray(profileData.equipment) ? profileData.equipment : [],
            fitnessGoals: Array.isArray(profileData.fitness_goals || profileData.fitnessGoals) 
                ? profileData.fitness_goals || profileData.fitnessGoals 
                : []
        };

        // Log normalization results for verification
        console.log('Field normalization results:', {
            username: {
                raw: profileData.user_login,
                normalized: normalizedData.username
            },
            displayName: {
                raw: profileData.display_name,
                normalized: normalizedData.displayName
            },
            email: {
                raw: profileData.user_email,
                normalized: normalizedData.email
            }
        });

        // Store the current user data for future reference
        this.currentUserData = normalizedData;
        
        console.log('Final normalized data:', normalizedData);
        console.groupEnd();
        return normalizedData;
    }

    private denormalizeProfileData(data: Partial<ProfileData>): Record<string, any> {
        console.group('ProfileService: denormalizeProfileData');
        
        // Get current email for preservation logic
        const currentEmail = this.currentUserData?.email || '';
        
        // Handle email preservation
        const emailExists = 'email' in data;
        const email = emailExists 
            ? (data.email?.trim() || null)  // Convert empty/whitespace to null
            : currentEmail;
        
        console.log('Email preservation:', {
            inputEmail: data.email,
            inputEmailType: typeof data.email,
            currentEmail,
            emailExists,
            finalEmail: email,
            finalEmailType: typeof email,
            wasPreserved: !emailExists,
            trimmedLength: data.email?.trim().length
        });

        // Convert camelCase to snake_case for backend
        const denormalized: Record<string, any> = {
            id: data.id,
            username: data.username || '',
            email,  // Use preserved or null email
            display_name: data.displayName || '',
            first_name: data.firstName || '',
            last_name: data.lastName || '',

            // Medical information
            medical_conditions: data.medicalConditions,
            exercise_limitations: data.exerciseLimitations,
            medications: data.medications,
            medical_clearance: data.medicalClearance,
            medical_notes: data.medicalNotes,

            // Custom profile fields
            phone: data.phone,
            age: data.age,
            date_of_birth: data.dateOfBirth,
            gender: data.gender,
            dominant_side: data.dominantSide,
            emergency_contact_name: data.emergencyContactName,
            emergency_contact_phone: data.emergencyContactPhone,
            injuries: data.injuries?.map(injury => ({
                id: injury.id,
                name: injury.name,
                details: injury.details,
                type: injury.type,
                description: injury.description,
                date: injury.date,
                severity: injury.severity,
                status: injury.status
            }))
        };

        // Remove undefined and null values
        Object.keys(denormalized).forEach(key => {
            if (denormalized[key] === undefined || denormalized[key] === null) {
                delete denormalized[key];
            }
        });

        console.log('Denormalized data for backend:', denormalized);
        console.groupEnd();
        return denormalized;
    }

    async fetchUserProfile(userId: number): Promise<UserProfile> {
        try {
            const response = await this.apiClient.fetch<UserProfile>(`profile/user/${userId}`);
            if (response.error || !response.data) {
                throw new Error(response.error?.message || 'No data received');
            }
            return response.data;
        } catch (error) {
            console.error('[ProfileService] Error fetching user profile:', error);
            throw error;
        }
    }

    async updateUserProfile(userId: number, data: Partial<UserProfile['data']>): Promise<UserProfile> {
        try {
            const response = await this.apiClient.post<UserProfile>(`profile/user/${userId}`, data);
            if (response.error || !response.data) {
                throw new Error(response.error?.message || 'No data received');
            }
            return response.data;
        } catch (error) {
            console.error('[ProfileService] Error updating user profile:', error);
            throw error;
        }
    }
} 
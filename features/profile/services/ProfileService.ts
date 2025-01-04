import { ProfileData, PhysicalMetric, ProfileErrorCode } from '../types/profile';

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

export class ProfileService {
    private readonly apiUrl: string;
    private readonly nonce: string;

    constructor(apiUrl: string, nonce: string) {
        this.apiUrl = apiUrl;
        this.nonce = nonce;
    }

    public async fetchProfile(userId: number): Promise<ProfileData> {
        try {
            const response = await fetch(`${this.apiUrl}/athlete-dashboard/v1/profile/${userId}`, {
                headers: {
                    'X-WP-Nonce': this.nonce
                }
            });

            if (!response.ok) {
                throw new ProfileError({
                    code: 'NETWORK_ERROR',
                    message: 'Failed to fetch profile data',
                    status: response.status
                });
            }

            const data = await response.json();
            return this.normalizeProfileData(data);
        } catch (error) {
            throw new ProfileError({
                code: 'NETWORK_ERROR',
                message: error instanceof Error ? error.message : 'Failed to fetch profile data'
            });
        }
    }

    public async updateProfile(userId: number, data: Partial<ProfileData>): Promise<ProfileData> {
        try {
            const response = await fetch(`${this.apiUrl}/athlete-dashboard/v1/profile/${userId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.nonce
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new ProfileError({
                    code: 'NETWORK_ERROR',
                    message: 'Failed to update profile data',
                    status: response.status
                });
            }

            const updatedData = await response.json();
            return this.normalizeProfileData(updatedData);
        } catch (error) {
            throw new ProfileError({
                code: 'NETWORK_ERROR',
                message: error instanceof Error ? error.message : 'Failed to update profile data'
            });
        }
    }

    private normalizeProfileData(data: any): ProfileData {
        const defaultMetrics: PhysicalMetric[] = [{
            type: 'height',
            value: data.height || 0,
            unit: 'cm',
            date: new Date().toISOString()
        }, {
            type: 'weight',
            value: data.weight || 0,
            unit: 'kg',
            date: new Date().toISOString()
        }];

        return {
            id: data.id,
            username: data.username,
            email: data.email,
            displayName: data.display_name || '',
            firstName: data.first_name || '',
            lastName: data.last_name || '',
            age: data.age || 0,
            gender: data.gender || '',
            height: data.height || 0,
            weight: data.weight || 0,
            fitnessLevel: data.fitness_level || 'beginner',
            activityLevel: data.activity_level || 'sedentary',
            medicalConditions: data.medical_conditions || [],
            exerciseLimitations: data.exercise_limitations || [],
            medications: data.medications || '',
            physicalMetrics: data.physical_metrics || defaultMetrics
        };
    }
} 
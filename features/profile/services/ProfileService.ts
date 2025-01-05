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
            console.group('Profile Fetch');
            console.log('Fetching profile for user:', userId);
            console.log('API URL:', `${this.apiUrl}/athlete-dashboard/v1/profile/${userId}`);
            
            const response = await fetch(`${this.apiUrl}/athlete-dashboard/v1/profile/${userId}`, {
                headers: {
                    'X-WP-Nonce': this.nonce
                }
            });

            console.log('Profile fetch response status:', response.status);
            const responseText = await response.text();
            console.log('Raw response:', responseText);

            if (!response.ok) {
                throw new ProfileError({
                    code: 'NETWORK_ERROR',
                    message: `Failed to fetch profile data: ${response.status} ${response.statusText}`,
                    status: response.status
                });
            }

            const data = JSON.parse(responseText);
            const normalizedData = this.normalizeProfileData(data);
            console.log('Normalized profile data:', normalizedData);
            console.groupEnd();
            return normalizedData;
        } catch (error) {
            console.error('Profile fetch error:', error);
            console.groupEnd();
            throw new ProfileError({
                code: 'NETWORK_ERROR',
                message: error instanceof Error ? error.message : 'Failed to fetch profile data'
            });
        }
    }

    public async updateProfile(userId: number, data: Partial<ProfileData>): Promise<ProfileData> {
        try {
            console.group('Profile Update');
            console.log('Updating profile for user:', userId);
            console.log('Update data:', data);
            
            const response = await fetch(`${this.apiUrl}/athlete-dashboard/v1/profile/${userId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.nonce
                },
                body: JSON.stringify(this.denormalizeProfileData(data))
            });

            console.log('Profile update response status:', response.status);
            const responseText = await response.text();
            console.log('Raw update response:', responseText);

            if (!response.ok) {
                throw new ProfileError({
                    code: 'NETWORK_ERROR',
                    message: `Failed to update profile data: ${response.status} ${response.statusText}`,
                    status: response.status
                });
            }

            const updatedData = JSON.parse(responseText);
            const normalizedData = this.normalizeProfileData(updatedData);
            console.log('Normalized updated data:', normalizedData);
            console.groupEnd();
            return normalizedData;
        } catch (error) {
            console.error('Profile update error:', error);
            console.groupEnd();
            throw new ProfileError({
                code: 'NETWORK_ERROR',
                message: error instanceof Error ? error.message : 'Failed to update profile data'
            });
        }
    }

    private normalizeProfileData(data: any): ProfileData {
        console.group('Profile Data Normalization');
        console.log('Raw data received:', data);

        // Extract profile data from the response structure
        const profileData = data.data?.profile || data;
        console.log('Extracted profile data:', profileData);

        // Convert string values to appropriate types
        const normalizedData: ProfileData = {
            // Core WordPress fields
            id: Number(profileData.id) || 0,
            username: profileData.username || '',
            email: profileData.email || '',
            displayName: profileData.displayName || profileData.display_name || '',
            firstName: profileData.firstName || profileData.first_name || '',
            lastName: profileData.lastName || profileData.last_name || '',

            // Custom profile fields
            phone: profileData.phone || '',
            age: Number(profileData.age) || 0,
            dateOfBirth: profileData.date_of_birth || '',
            height: Number(profileData.height) || 0,
            weight: Number(profileData.weight) || 0,
            gender: profileData.gender || '',
            dominantSide: profileData.dominant_side || '',
            medicalClearance: Boolean(profileData.medical_clearance),
            medicalNotes: profileData.medical_notes || '',
            emergencyContactName: profileData.emergency_contact_name || '',
            emergencyContactPhone: profileData.emergency_contact_phone || '',
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
                : []
        };

        console.log('Normalized data:', normalizedData);
        console.groupEnd();
        return normalizedData;
    }

    private denormalizeProfileData(data: Partial<ProfileData>): Record<string, any> {
        // Convert camelCase to snake_case for backend
        const denormalized: Record<string, any> = {
            // Core WordPress fields remain as is
            id: data.id,
            username: data.username,
            email: data.email,
            display_name: data.displayName,
            first_name: data.firstName,
            last_name: data.lastName,

            // Custom profile fields
            phone: data.phone,
            age: data.age,
            date_of_birth: data.dateOfBirth,
            height: data.height,
            weight: data.weight,
            gender: data.gender,
            dominant_side: data.dominantSide,
            medical_clearance: data.medicalClearance,
            medical_notes: data.medicalNotes,
            emergency_contact_name: data.emergencyContactName,
            emergency_contact_phone: data.emergencyContactPhone,
            injuries: data.injuries?.map(injury => ({
                id: injury.id,
                name: injury.name,
                details: injury.details
            }))
        };

        // Remove undefined values
        Object.keys(denormalized).forEach(key => {
            if (denormalized[key] === undefined) {
                delete denormalized[key];
            }
        });

        console.log('Denormalized data for backend:', denormalized);
        return denormalized;
    }
} 
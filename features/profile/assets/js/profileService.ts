import axios from 'axios';
import { Events } from '@dashboard/events';
import { ProfileData, PROFILE_EVENTS } from '../../events';

class ProfileService {
    private readonly endpoint = '/wp-json/athlete-dashboard/v1/profile';

    public async loadProfile(): Promise<void> {
        try {
            const response = await axios.get<ProfileData>(this.endpoint);
            Events.emit(PROFILE_EVENTS.LOADED, { data: response.data });
        } catch (error) {
            Events.emit(PROFILE_EVENTS.ERROR, { 
                error: 'Failed to load profile data'
            });
        }
    }

    public async updateProfile(data: Partial<ProfileData>): Promise<void> {
        try {
            const response = await axios.post<ProfileData>(this.endpoint, data);
            Events.emit(PROFILE_EVENTS.UPDATED, { 
                data: response.data 
            });
        } catch (error) {
            Events.emit(PROFILE_EVENTS.ERROR, { 
                error: 'Failed to update profile data'
            });
        }
    }
}

export const profileService = new ProfileService(); 
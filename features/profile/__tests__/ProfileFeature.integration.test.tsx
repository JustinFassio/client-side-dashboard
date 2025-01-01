import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { ProfileFeature } from '../ProfileFeature';
import { ProfileService } from '../services/ProfileService';
import { Events } from '../../../dashboard/core/events';
import { PROFILE_EVENTS } from '../events/events';

// Mock the ProfileService
jest.mock('../services/ProfileService');

describe('ProfileFeature Integration', () => {
    const mockContext = {
        userId: 1,
        navigate: jest.fn(),
        isEnabled: jest.fn(() => true)
    };

    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('should load and display profile data', async () => {
        const mockProfile = ProfileService.getDefaultProfile();
        mockProfile.displayName = 'Test User';
        (ProfileService.fetchProfile as jest.Mock).mockResolvedValue(mockProfile);

        const feature = new ProfileFeature();
        await feature.register(mockContext);
        await feature.init();
        
        render(feature.render());

        await waitFor(() => {
            expect(screen.getByDisplayValue('Test User')).toBeInTheDocument();
        });
    });

    it('should handle profile updates', async () => {
        const mockProfile = ProfileService.getDefaultProfile();
        const updatedProfile = { ...mockProfile, displayName: 'Updated User' };
        
        (ProfileService.fetchProfile as jest.Mock).mockResolvedValue(mockProfile);
        (ProfileService.updateProfile as jest.Mock).mockResolvedValue(updatedProfile);

        const feature = new ProfileFeature();
        await feature.register(mockContext);
        await feature.init();
        
        render(feature.render());

        // Wait for initial load
        await waitFor(() => {
            expect(ProfileService.fetchProfile).toHaveBeenCalled();
        });

        // Update display name
        const input = screen.getByLabelText(/display name/i);
        fireEvent.change(input, { target: { value: 'Updated User' } });
        
        // Submit form
        const submitButton = screen.getByRole('button', { name: /save/i });
        fireEvent.click(submitButton);

        await waitFor(() => {
            expect(ProfileService.updateProfile).toHaveBeenCalledWith(
                expect.objectContaining({ displayName: 'Updated User' })
            );
        });
    });

    it('should handle section navigation', async () => {
        (ProfileService.fetchProfile as jest.Mock).mockResolvedValue(ProfileService.getDefaultProfile());

        const feature = new ProfileFeature();
        await feature.register(mockContext);
        await feature.init();
        
        render(feature.render());

        // Navigate to Physical section
        const physicalTab = screen.getByRole('tab', { name: /physical information/i });
        fireEvent.click(physicalTab);

        expect(screen.getByRole('tabpanel', { name: /physical information/i })).toBeInTheDocument();

        // Verify event emission
        await waitFor(() => {
            const eventSpy = jest.spyOn(Events, 'emit');
            expect(eventSpy).toHaveBeenCalledWith(
                PROFILE_EVENTS.SECTION_CHANGE,
                expect.any(String)
            );
        });
    });

    it('should handle error states', async () => {
        const error = { code: 'SERVER_ERROR', message: 'Failed to load profile' };
        (ProfileService.fetchProfile as jest.Mock).mockRejectedValue(error);

        const feature = new ProfileFeature();
        await feature.register(mockContext);
        await feature.init();
        
        render(feature.render());

        await waitFor(() => {
            expect(screen.getByText(/failed to load profile/i)).toBeInTheDocument();
        });
    });

    it('should cleanup on unmount', async () => {
        const feature = new ProfileFeature();
        await feature.register(mockContext);
        await feature.init();
        
        const { unmount } = render(feature.render());
        unmount();

        expect(feature.isEnabled()).toBe(true);
        expect(Events.off).toHaveBeenCalled();
    });
}); 
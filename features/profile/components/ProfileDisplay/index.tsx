import { createElement, useState, useEffect } from '@wordpress/element';
import { ProfileDisplayState, ProfileSection } from '../../types/profile-display';
import { Events } from '../../../../dashboard/core/events';
import { PROFILE_EVENTS } from '../../events';
import { profileService } from '../../services/ProfileService';

interface ProfileDisplayProps {
    sections: ProfileSection[];
}

export const ProfileDisplay = ({ sections }: ProfileDisplayProps) => {
    const [state, setState] = useState<ProfileDisplayState>({
        activeSection: sections[0]?.id || '',
        editMode: 'none',
        editingField: null,
        isLoading: true,
        error: null,
        success: null
    });

    useEffect(() => {
        loadProfileData();
        
        // Subscribe to profile events
        Events.on(PROFILE_EVENTS.PROFILE_UPDATED, handleProfileUpdated);
        Events.on(PROFILE_EVENTS.PROFILE_UPDATE_FAILED, handleProfileUpdateFailed);

        return () => {
            Events.off(PROFILE_EVENTS.PROFILE_UPDATED, handleProfileUpdated);
            Events.off(PROFILE_EVENTS.PROFILE_UPDATE_FAILED, handleProfileUpdateFailed);
        };
    }, []);

    const loadProfileData = async () => {
        try {
            setState(prev => ({ ...prev, isLoading: true, error: null }));
            await profileService.getCurrentProfile();
            setState(prev => ({ ...prev, isLoading: false }));
        } catch (error) {
            setState(prev => ({
                ...prev,
                isLoading: false,
                error: 'Failed to load profile data'
            }));
        }
    };

    const handleProfileUpdated = () => {
        setState(prev => ({
            ...prev,
            editMode: 'none',
            editingField: null,
            success: 'Profile updated successfully'
        }));

        // Clear success message after delay
        setTimeout(() => {
            setState(prev => ({ ...prev, success: null }));
        }, 3000);
    };

    const handleProfileUpdateFailed = () => {
        setState(prev => ({
            ...prev,
            error: 'Failed to update profile'
        }));
    };

    const handleSectionChange = (sectionId: string) => {
        setState(prev => ({ ...prev, activeSection: sectionId }));
    };

    return createElement('div', { className: 'profile-display' }, [
        // Navigation
        createElement('nav', { className: 'profile-sections', key: 'nav' },
            sections.map(section =>
                createElement('button', {
                    key: section.id,
                    className: `section-button ${state.activeSection === section.id ? 'active' : ''}`,
                    onClick: () => handleSectionChange(section.id)
                }, [
                    createElement(section.icon, {
                        size: 20,
                        key: 'icon',
                        className: 'section-icon'
                    }),
                    createElement('span', { key: 'text' }, section.title)
                ])
            )
        ),

        // Status messages
        state.error && createElement('div', {
            className: 'profile-error',
            key: 'error'
        }, state.error),

        state.success && createElement('div', {
            className: 'profile-success',
            key: 'success'
        }, state.success),

        // Active section content
        createElement('div', {
            className: 'profile-content',
            key: 'content'
        }, state.isLoading
            ? createElement('div', { className: 'loading' }, 'Loading...')
            : sections
                .filter(section => section.id === state.activeSection)
                .map(section => createElement('div', {
                    key: section.id,
                    className: 'section-content'
                }, [
                    createElement('h2', { key: 'title' }, section.title),
                    // Section content will be rendered here
                ]))
        )
    ]);
}; 
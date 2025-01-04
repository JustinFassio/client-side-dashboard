import { createElement } from '@wordpress/element';
import { useProfile } from '../../context/ProfileContext';
import { useUser } from '../../../../dashboard/hooks/useUser';

export const ProfileLayout = () => {
    const { context } = useProfile();
    const { user, isLoading, error } = useUser(context);
    const userId = user?.id;

    if (isLoading) {
        return createElement('div', { className: 'profile-layout' },
            createElement('p', null, 'Loading profile...')
        );
    }

    if (error || !user) {
        return createElement('div', { className: 'profile-layout' },
            createElement('p', null, `Error: ${error || 'Failed to load profile'}`)
        );
    }

    return createElement('div', { className: 'profile-layout' },
        createElement('h1', null, 'Profile'),
        createElement('p', null, `Welcome, ${user.name}`),
        createElement('div', { className: 'profile-details' },
            createElement('p', null, `Email: ${user.email}`),
            createElement('p', null, `Roles: ${user.roles.join(', ')}`)
        )
    );
}; 
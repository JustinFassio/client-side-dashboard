import { DashboardEvents } from '../../../dashboard/contracts/Events';
import { LEGACY_PROFILE_EVENTS } from '../types/events';

const EVENT_MAP = {
    'profile:update-success': LEGACY_PROFILE_EVENTS.PROFILE_UPDATED,
    'profile:fetch-success': LEGACY_PROFILE_EVENTS.PROFILE_LOADED,
    'profile:fetch-error': LEGACY_PROFILE_EVENTS.PROFILE_ERROR,
    'profile:update-error': LEGACY_PROFILE_EVENTS.PROFILE_ERROR
} as const;

export const setupProfileEventCompatibility = (events: DashboardEvents): void => {
    Object.entries(EVENT_MAP).forEach(([modernEvent, legacyEvent]) => {
        events.on(modernEvent, (data) => {
            events.emit(legacyEvent, data);
        });
    });
};

export { LEGACY_PROFILE_EVENTS }; 
/**
 * Profile Events System
 * Exports a unified API for the profile feature's event system
 */

export { PROFILE_EVENTS } from './constants';
export { ProfileEventUtils } from './utils';
export {
    emitCompatibleEvent as emitProfileEvent,
    onCompatibleEvent as onProfileEvent,
    offCompatibleEvent as offProfileEvent,
    LEGACY_PROFILE_EVENTS
} from './compatibility';
export type {
    ProfileEvent,
    ProfileEventType,
    ProfileEventHandler,
    ProfileEventPayloads
} from './types'; 
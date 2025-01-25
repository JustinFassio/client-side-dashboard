import { Events } from '../../../dashboard/core/events';
import { ProfileEvent, ProfileEventPayloads } from './types';

type EventKey = keyof typeof ProfileEvent;
type EventType = typeof ProfileEvent[EventKey];
type PayloadType<T extends EventType> = T extends keyof ProfileEventPayloads ? ProfileEventPayloads[T] : never;

export function emitProfileEvent<T extends EventType>(
    events: typeof Events,
    event: T,
    payload: PayloadType<T>
): void {
    events.emit(event, payload);
}

export function onProfileEvent<T extends EventType>(
    events: typeof Events,
    event: T,
    handler: (payload: PayloadType<T>) => void
): () => void {
    events.on(event, handler);
    return () => events.off(event, handler);
}

export function offProfileEvent<T extends EventType>(
    events: typeof Events,
    event: T,
    handler: (payload: PayloadType<T>) => void
): void {
    events.off(event, handler);
} 
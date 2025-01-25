import { addAction, doAction } from '@wordpress/hooks';
import { EventEmitter as NodeEventEmitter } from 'events';

export interface DashboardEvents extends NodeEventEmitter {
    emit(event: string, payload?: any): boolean;
    on(event: string, handler: (payload: any) => void): this;
    off(event: string, handler: (payload: any) => void): this;
}

// Create a singleton instance of EventEmitter that implements DashboardEvents
export const dashboardEvents = new NodeEventEmitter() as DashboardEvents;

// Event types
export enum DashboardEventType {
    FEATURE_LOADED = 'feature_loaded',
    FEATURE_UNLOADED = 'feature_unloaded',
    NAVIGATION_CHANGED = 'navigation_changed',
}

// Event data interfaces
export interface FeatureLoadedEvent {
    featureId: string;
    timestamp: number;
}

export interface FeatureUnloadedEvent {
    featureId: string;
    timestamp: number;
}

export interface NavigationChangedEvent {
    from: string;
    to: string;
    timestamp: number;
}

// Event emitter functions
export const emitFeatureLoaded = (featureId: string) => {
    doAction(DashboardEventType.FEATURE_LOADED, {
        featureId,
        timestamp: Date.now(),
    } as FeatureLoadedEvent);
};

export const emitFeatureUnloaded = (featureId: string) => {
    doAction(DashboardEventType.FEATURE_UNLOADED, {
        featureId,
        timestamp: Date.now(),
    } as FeatureUnloadedEvent);
};

export const emitNavigationChanged = (from: string, to: string) => {
    doAction(DashboardEventType.NAVIGATION_CHANGED, {
        from,
        to,
        timestamp: Date.now(),
    } as NavigationChangedEvent);
};

// Event listener functions
export const onFeatureLoaded = (callback: (event: FeatureLoadedEvent) => void) => {
    addAction(DashboardEventType.FEATURE_LOADED, 'athlete-dashboard', callback);
};

export const onFeatureUnloaded = (callback: (event: FeatureUnloadedEvent) => void) => {
    addAction(DashboardEventType.FEATURE_UNLOADED, 'athlete-dashboard', callback);
};

export const onNavigationChanged = (callback: (event: NavigationChangedEvent) => void) => {
    addAction(DashboardEventType.NAVIGATION_CHANGED, 'athlete-dashboard', callback);
}; 
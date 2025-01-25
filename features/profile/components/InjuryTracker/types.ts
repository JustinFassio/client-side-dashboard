export interface TrackerField<T> {
    label: string;
    key: keyof T;
    type: 'text' | 'select' | 'textarea';
    options?: Array<{ value: string; label: string }>;
    placeholder?: string;
}

export interface TrackerProps<T> {
    items: T[];
    onAdd: (item: Partial<T>) => void;
    onUpdate: (id: string, updates: Partial<T>) => void;
    onRemove: (id: string) => void;
    title: string;
    description: string;
    fields: TrackerField<T>[];
    predefinedItems?: Array<{ value: string; label: string }>;
    className?: string;
}

// Import the Injury type from profile types to ensure consistency
import { Injury as ProfileInjury } from '../../types/profile';
export type { ProfileInjury as Injury };

// Specific props for the InjuryTracker implementation
export interface InjuryTrackerProps {
    injuries: ProfileInjury[];
    onChange: (injuries: ProfileInjury[]) => void;
    className?: string;
}

export const PREDEFINED_INJURIES = [
    { value: 'knee_pain', label: 'Knee Pain' },
    { value: 'back_pain', label: 'Back Pain' },
    { value: 'shoulder_pain', label: 'Shoulder Pain' },
    { value: 'ankle_sprain', label: 'Ankle Sprain' },
    { value: 'muscle_strain', label: 'Muscle Strain' },
    { value: 'tendonitis', label: 'Tendonitis' }
]; 
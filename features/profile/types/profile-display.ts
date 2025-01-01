import { LucideIcon } from 'lucide-react';

export interface ProfileField<T> {
    value: T;
    lastUpdated: string;
    isEditing: boolean;
    validation?: RegExp | ((value: T) => boolean);
    displayFormat?: (value: T) => string;
}

export interface ProfileSection {
    id: string;
    title: string;
    icon: LucideIcon;
    fields: Record<string, ProfileField<any>>;
    order: number;
}

export interface ProfileUpdate {
    id: string;
    timestamp: string;
    userId: number;
    changes: {
        section: string;
        field: string;
        oldValue: any;
        newValue: any;
    }[];
    metadata?: {
        source: 'web' | 'admin' | 'api';
        context?: string;
    };
}

export type EditMode = 'none' | 'field' | 'section';

export interface ProfileDisplayState {
    activeSection: string;
    editMode: EditMode;
    editingField: string | null;
    isLoading: boolean;
    error: string | null;
    success: string | null;
} 
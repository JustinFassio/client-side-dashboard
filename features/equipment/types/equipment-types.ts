export interface Equipment {
    id: string;
    name: string;
    type: 'machine' | 'free weights' | 'bands' | 'other';
    weightRange?: string; // e.g., "10-50 lbs"
    quantity?: number;
    description?: string;
}

export interface EquipmentSet {
    id: string;
    name: string;
    equipmentIds: string[];
    notes?: string;
}

export interface WorkoutZone {
    id: string;
    name: string;
    equipmentIds: string[];
    environment: 'home' | 'gym' | 'outdoor';
} 
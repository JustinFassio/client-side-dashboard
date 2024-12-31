export interface Injury {
    id: string;
    name: string;
    details: string;
    severity?: 'low' | 'medium' | 'high';
    dateAdded?: string;
    isCustom: boolean;
    status: 'active' | 'recovered';
}

export interface InjuryTrackerProps {
    injuries: Injury[];
    onChange: (injuries: Injury[]) => void;
    className?: string;
}

export const PREDEFINED_INJURIES = [
    { name: 'Lower Back Pain', category: 'Back' },
    { name: 'Upper Back Strain', category: 'Back' },
    { name: 'Knee Pain', category: 'Legs' },
    { name: 'Runner\'s Knee', category: 'Legs' },
    { name: 'Shin Splints', category: 'Legs' },
    { name: 'Ankle Sprain', category: 'Legs' },
    { name: 'Shoulder Impingement', category: 'Upper Body' },
    { name: 'Tennis Elbow', category: 'Upper Body' },
    { name: 'Wrist Strain', category: 'Upper Body' },
    { name: 'Hamstring Strain', category: 'Legs' },
    { name: 'Hip Flexor Strain', category: 'Legs' },
    { name: 'Plantar Fasciitis', category: 'Feet' },
    { name: 'Achilles Tendinitis', category: 'Legs' },
    { name: 'Rotator Cuff Injury', category: 'Upper Body' },
    { name: 'Neck Strain', category: 'Back' }
]; 
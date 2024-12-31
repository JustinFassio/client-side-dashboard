import { createElement, useState, useEffect } from '@wordpress/element';
import { ProfileData, Gender, MedicalInfo } from '../../events';
import { profileService } from '../../assets/js/profileService';
import './ProfileForm.css';

interface ProfileFormProps {
    onSave?: (data: ProfileData) => void;
    onError?: (error: string) => void;
}

interface ValidationErrors {
    [key: string]: string;
}

const DEFAULT_MEDICAL_INFO: MedicalInfo = {
    hasInjuries: false,
    hasMedicalClearance: false
};

export function ProfileForm({ onSave, onError }: ProfileFormProps) {
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [errors, setErrors] = useState<ValidationErrors>({});
    const [formData, setFormData] = useState<ProfileData>({
        firstName: '',
        lastName: '',
        email: '',
        age: 0,
        gender: Gender.PREFER_NOT_TO_SAY,
        height: 0,
        weight: 0,
        medicalInfo: DEFAULT_MEDICAL_INFO,
        bio: '',
        fitnessGoals: '',
        preferredWorkoutTypes: []
    });

    useEffect(() => {
        loadProfile();
    }, []);

    const loadProfile = async () => {
        try {
            const data = await profileService.getCurrentProfile();
            setFormData(data);
        } catch (error) {
            onError?.('Failed to load profile data');
        } finally {
            setIsLoading(false);
        }
    };

    const validateForm = (): boolean => {
        const newErrors: ValidationErrors = {};

        // Basic info validation
        if (!formData.firstName.trim()) newErrors.firstName = 'First name is required';
        if (!formData.lastName.trim()) newErrors.lastName = 'Last name is required';
        if (!formData.email.trim()) newErrors.email = 'Email is required';
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
            newErrors.email = 'Invalid email format';
        }

        // Physical attributes validation
        if (formData.age < 13 || formData.age > 120) {
            newErrors.age = 'Age must be between 13 and 120';
        }
        if (formData.height < 100 || formData.height > 300) {
            newErrors.height = 'Height must be between 100cm and 300cm';
        }
        if (formData.weight < 30 || formData.weight > 300) {
            newErrors.weight = 'Weight must be between 30kg and 300kg';
        }

        // Medical info validation
        if (formData.medicalInfo.hasInjuries && !formData.medicalInfo.injuries?.trim()) {
            newErrors.injuries = 'Please describe your injuries';
        }
        if (formData.medicalInfo.hasMedicalClearance && !formData.medicalInfo.medicalClearanceDate) {
            newErrors.medicalClearanceDate = 'Please provide medical clearance date';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!validateForm()) {
            onError?.('Please fix the validation errors');
            return;
        }

        setIsSaving(true);

        try {
            const updatedProfile = await profileService.updateProfile(formData);
            onSave?.(updatedProfile);
        } catch (error) {
            onError?.('Failed to save profile changes');
        } finally {
            setIsSaving(false);
        }
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value, type } = e.target;
        
        setFormData(prev => {
            if (name.startsWith('medical.')) {
                const [, field] = name.split('.');
                return {
                    ...prev,
                    medicalInfo: {
                        ...prev.medicalInfo,
                        [field]: type === 'checkbox' ? (e.target as HTMLInputElement).checked : value
                    }
                };
            }

            if (type === 'number') {
                return {
                    ...prev,
                    [name]: parseFloat(value) || 0
                };
            }

            return {
                ...prev,
                [name]: value
            };
        });
    };

    if (isLoading) {
        return <div className="profile-form-loading">Loading profile...</div>;
    }

    return (
        <form className="profile-form" onSubmit={handleSubmit}>
            <section className="form-section">
                <h2>Basic Information</h2>
                <div className="form-group">
                    <label htmlFor="firstName">First Name *</label>
                    <input
                        type="text"
                        id="firstName"
                        name="firstName"
                        value={formData.firstName}
                        onChange={handleInputChange}
                        required
                        className={errors.firstName ? 'error' : ''}
                    />
                    {errors.firstName && <span className="error-message">{errors.firstName}</span>}
                </div>

                <div className="form-group">
                    <label htmlFor="lastName">Last Name *</label>
                    <input
                        type="text"
                        id="lastName"
                        name="lastName"
                        value={formData.lastName}
                        onChange={handleInputChange}
                        required
                        className={errors.lastName ? 'error' : ''}
                    />
                    {errors.lastName && <span className="error-message">{errors.lastName}</span>}
                </div>

                <div className="form-group">
                    <label htmlFor="email">Email *</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value={formData.email}
                        onChange={handleInputChange}
                        required
                        className={errors.email ? 'error' : ''}
                    />
                    {errors.email && <span className="error-message">{errors.email}</span>}
                </div>
            </section>

            <section className="form-section">
                <h2>Physical Information</h2>
                <div className="form-row">
                    <div className="form-group">
                        <label htmlFor="age">Age *</label>
                        <input
                            type="number"
                            id="age"
                            name="age"
                            value={formData.age || ''}
                            onChange={handleInputChange}
                            min="13"
                            max="120"
                            required
                            className={errors.age ? 'error' : ''}
                        />
                        {errors.age && <span className="error-message">{errors.age}</span>}
                    </div>

                    <div className="form-group">
                        <label htmlFor="gender">Gender</label>
                        <select
                            id="gender"
                            name="gender"
                            value={formData.gender}
                            onChange={handleInputChange}
                        >
                            {Object.entries(Gender).map(([key, value]) => (
                                <option key={value} value={value}>
                                    {key.toLowerCase().replace(/_/g, ' ')}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="form-row">
                    <div className="form-group">
                        <label htmlFor="height">Height (cm) *</label>
                        <input
                            type="number"
                            id="height"
                            name="height"
                            value={formData.height || ''}
                            onChange={handleInputChange}
                            min="100"
                            max="300"
                            required
                            className={errors.height ? 'error' : ''}
                        />
                        {errors.height && <span className="error-message">{errors.height}</span>}
                    </div>

                    <div className="form-group">
                        <label htmlFor="weight">Weight (kg) *</label>
                        <input
                            type="number"
                            id="weight"
                            name="weight"
                            value={formData.weight || ''}
                            onChange={handleInputChange}
                            min="30"
                            max="300"
                            required
                            className={errors.weight ? 'error' : ''}
                        />
                        {errors.weight && <span className="error-message">{errors.weight}</span>}
                    </div>
                </div>
            </section>

            <section className="form-section">
                <h2>Medical Information</h2>
                <div className="form-group checkbox-group">
                    <label>
                        <input
                            type="checkbox"
                            name="medical.hasInjuries"
                            checked={formData.medicalInfo.hasInjuries}
                            onChange={handleInputChange}
                        />
                        Do you have any injuries?
                    </label>
                </div>

                {formData.medicalInfo.hasInjuries && (
                    <div className="form-group">
                        <label htmlFor="injuries">Describe your injuries *</label>
                        <textarea
                            id="injuries"
                            name="medical.injuries"
                            value={formData.medicalInfo.injuries || ''}
                            onChange={handleInputChange}
                            className={errors.injuries ? 'error' : ''}
                        />
                        {errors.injuries && <span className="error-message">{errors.injuries}</span>}
                    </div>
                )}

                <div className="form-group checkbox-group">
                    <label>
                        <input
                            type="checkbox"
                            name="medical.hasMedicalClearance"
                            checked={formData.medicalInfo.hasMedicalClearance}
                            onChange={handleInputChange}
                        />
                        Do you have medical clearance?
                    </label>
                </div>

                {formData.medicalInfo.hasMedicalClearance && (
                    <div className="form-group">
                        <label htmlFor="medicalClearanceDate">Medical Clearance Date *</label>
                        <input
                            type="date"
                            id="medicalClearanceDate"
                            name="medical.medicalClearanceDate"
                            value={formData.medicalInfo.medicalClearanceDate || ''}
                            onChange={handleInputChange}
                            className={errors.medicalClearanceDate ? 'error' : ''}
                        />
                        {errors.medicalClearanceDate && (
                            <span className="error-message">{errors.medicalClearanceDate}</span>
                        )}
                    </div>
                )}

                <div className="form-group">
                    <label htmlFor="medicalNotes">Additional Medical Notes</label>
                    <textarea
                        id="medicalNotes"
                        name="medical.medicalNotes"
                        value={formData.medicalInfo.medicalNotes || ''}
                        onChange={handleInputChange}
                    />
                </div>
            </section>

            <section className="form-section">
                <h2>Additional Information</h2>
                <div className="form-group">
                    <label htmlFor="bio">Bio</label>
                    <textarea
                        id="bio"
                        name="bio"
                        value={formData.bio || ''}
                        onChange={handleInputChange}
                        placeholder="Tell us about yourself..."
                    />
                </div>

                <div className="form-group">
                    <label htmlFor="fitnessGoals">Fitness Goals</label>
                    <textarea
                        id="fitnessGoals"
                        name="fitnessGoals"
                        value={formData.fitnessGoals || ''}
                        onChange={handleInputChange}
                        placeholder="What are your fitness goals?"
                    />
                </div>
            </section>

            <div className="form-actions">
                <button type="submit" disabled={isSaving}>
                    {isSaving ? 'Saving...' : 'Save Profile'}
                </button>
            </div>
        </form>
    );
} 
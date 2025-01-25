import React, { useCallback } from 'react';
import { useForm } from 'react-hook-form';
import { ProfileData } from '../../types/profile';
import { useProfileValidation } from '../../hooks/useProfileValidation';
import { UnitConversion } from '../../utils/unitConversion';
import { Button } from '../../../../dashboard/components/Button';

interface ProfileFormProps {
    profile: ProfileData;
    onSubmit: (data: ProfileData) => void;
    onCancel: () => void;
    isSubmitting?: boolean;
    useImperial?: boolean;
}

export const ProfileForm: React.FC<ProfileFormProps> = ({ 
    profile, 
    onSubmit, 
    onCancel,
    isSubmitting = false,
    useImperial = false
}) => {
    const { 
        register, 
        handleSubmit, 
        formState: { errors }, 
        setValue, 
        setError,
        watch 
    } = useForm<ProfileData>({
        defaultValues: profile
    });
    
    const { validateProfile } = useProfileValidation();

    // Watch height and weight fields for unit conversion
    const heightCm = watch('heightCm') as number;
    const weightKg = watch('weightKg') as number;

    // Format display values based on unit preference
    const displayHeight = heightCm ? UnitConversion.formatHeight(heightCm, useImperial) : '';
    const displayWeight = weightKg ? UnitConversion.formatWeight(weightKg, useImperial) : '';

    const handleHeightChange = useCallback((value: string) => {
        if (useImperial) {
            const [feet, inches] = value.split("'");
            const cm = UnitConversion.feetAndInchesToCm(
                parseInt(feet) || 0,
                parseInt(inches?.replace('"', '')) || 0
            );
            setValue('heightCm', cm, { shouldValidate: true });
        } else {
            setValue('heightCm', parseInt(value) || 0, { shouldValidate: true });
        }
    }, [useImperial, setValue]);

    const handleWeightChange = useCallback((value: string) => {
        if (useImperial) {
            const lbs = parseInt(value) || 0;
            setValue('weightKg', UnitConversion.lbsToKg(lbs), { shouldValidate: true });
        } else {
            setValue('weightKg', parseInt(value) || 0, { shouldValidate: true });
        }
    }, [useImperial, setValue]);

    const onSubmitHandler = async (data: ProfileData) => {
        // Ensure arrays are properly formatted before validation
        const formattedData = {
            ...data,
            fitnessGoals: Array.isArray(data.fitnessGoals) ? 
                data.fitnessGoals.filter(Boolean) : // Filter out any null/undefined values
                [],
            equipment: Array.isArray(data.equipment) ? 
                data.equipment.filter(Boolean) : 
                []
        };

        const validation = validateProfile(formattedData);
        if (!validation.isValid) {
            // Handle validation errors
            if (validation.fieldErrors) {
                Object.entries(validation.fieldErrors).forEach(([field, messages]) => {
                    const message = messages.join(', ');
                    setError(field as keyof ProfileData, {
                        type: 'manual',
                        message
                    });
                });
            }
            return;
        }

        try {
            await onSubmit(formattedData);
        } catch (error) {
            console.error('Profile update failed:', error);
            setError('root', {
                type: 'manual',
                message: 'Failed to update profile'
            });
        }
    };

    return (
        <form 
            onSubmit={handleSubmit(onSubmitHandler)} 
            className="profile-form"
            aria-label="Profile Update Form"
            role="form"
        >
            <div className="form-field">
                <label htmlFor="username">Username</label>
                <input
                    id="username"
                    type="text"
                    {...register("username")}
                    aria-invalid={!!errors.username}
                />
                {errors.username && (
                    <span role="alert" className="error-message">
                        {errors.username.message}
                    </span>
                )}
            </div>

            <div className="form-field">
                <label htmlFor="displayName">Display Name</label>
                <input
                    id="displayName"
                    type="text"
                    {...register("displayName")}
                    aria-invalid={!!errors.displayName}
                />
                {errors.displayName && (
                    <span role="alert" className="error-message">
                        {errors.displayName.message}
                    </span>
                )}
            </div>

            <div className="form-field">
                <label htmlFor="heightCm">Height {useImperial ? "(ft'in\")" : "(cm)"}</label>
                <input
                    id="heightCm"
                    type="text"
                    value={displayHeight}
                    onChange={(e) => handleHeightChange(e.target.value)}
                    aria-invalid={!!errors.heightCm}
                />
                {errors.heightCm && (
                    <span role="alert" className="error-message" data-testid="height-error">
                        {errors.heightCm.message || 'Invalid height'}
                    </span>
                )}
            </div>

            <div className="form-field">
                <label htmlFor="weightKg">Weight {useImperial ? "(lbs)" : "(kg)"}</label>
                <input
                    id="weightKg"
                    type="text"
                    value={displayWeight}
                    onChange={(e) => handleWeightChange(e.target.value)}
                    aria-invalid={!!errors.weightKg}
                />
                {errors.weightKg && (
                    <span role="alert" className="error-message" data-testid="weight-error">
                        {errors.weightKg.message || 'Invalid weight'}
                    </span>
                )}
            </div>

            <div className="form-field">
                <label htmlFor="experienceLevel">Experience Level</label>
                <select
                    id="experienceLevel"
                    {...register("experienceLevel", { required: "Experience level is required" })}
                    aria-invalid={errors.experienceLevel ? "true" : "false"}
                >
                    <option value="">Select Experience Level</option>
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                </select>
                {errors.experienceLevel && (
                    <span role="alert" className="error-message">
                        {errors.experienceLevel.message || 'Please select an experience level'}
                    </span>
                )}
            </div>

            <div className="form-field">
                <label htmlFor="equipment">Available Equipment</label>
                <select
                    id="equipment"
                    multiple
                    {...register("equipment")}
                >
                    <option value="dumbbells">Dumbbells</option>
                    <option value="barbell">Barbell</option>
                    <option value="kettlebell">Kettlebell</option>
                    <option value="resistance_bands">Resistance Bands</option>
                    <option value="pull_up_bar">Pull-up Bar</option>
                    <option value="bench">Bench</option>
                </select>
                {errors.equipment && (
                    <span role="alert" className="error-message">
                        {errors.equipment.message || 'Please select available equipment'}
                    </span>
                )}
            </div>

            <div className="form-field">
                <label htmlFor="fitnessGoals">Fitness Goals</label>
                <select
                    id="fitnessGoals"
                    multiple
                    {...register("fitnessGoals", {
                        validate: (value) => {
                            if (!Array.isArray(value) || value.length === 0) {
                                return 'Please select at least one fitness goal';
                            }
                            const validGoals = ['strength', 'muscle_gain', 'fat_loss', 'endurance', 'flexibility'];
                            // Ensure consistent case handling
                            const validatedGoals = Array.isArray(value) ? 
                                value.filter(Boolean).map(v => v.toLowerCase()) : 
                                [];
                            return validatedGoals.every(goal => validGoals.includes(goal)) || 
                                'Invalid fitness goal selected';
                        }
                    })}
                    defaultValue={profile.fitnessGoals || []}
                    onChange={(e) => {
                        const selectedOptions = Array.from(e.target.selectedOptions);
                        const selectedValues = selectedOptions.map(option => option.value.toLowerCase());
                        setValue('fitnessGoals', selectedValues, { shouldValidate: true });
                    }}
                >
                    <option value="strength">Strength</option>
                    <option value="muscle_gain">Muscle Gain</option>
                    <option value="fat_loss">Fat Loss</option>
                    <option value="endurance">Endurance</option>
                    <option value="flexibility">Flexibility</option>
                </select>
                {errors.fitnessGoals && (
                    <span role="alert" className="error-message">
                        {errors.fitnessGoals.message || 'Please select your fitness goals'}
                    </span>
                )}
            </div>

            <div className="form-actions">
                <Button
                    type="submit"
                    variant="primary"
                    feature="profile"
                    disabled={isSubmitting}
                    aria-busy={isSubmitting}
                >
                    {isSubmitting ? 'Saving...' : 'Save'}
                </Button>
                <Button
                    type="button"
                    variant="secondary"
                    feature="profile"
                    onClick={onCancel}
                    disabled={isSubmitting}
                >
                    Cancel
                </Button>
            </div>
        </form>
    );
}; 
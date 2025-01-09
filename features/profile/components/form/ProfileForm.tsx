import React from 'react';
import { useForm } from 'react-hook-form';
import { ProfileData } from '../../types/profile';
import { useProfileErrors } from '../../hooks/useProfileErrors';

interface ProfileFormProps {
    profile: ProfileData;
    onSubmit: (data: ProfileData) => void;
    onCancel: () => void;
    isSubmitting?: boolean;
}

export const ProfileForm: React.FC<ProfileFormProps> = ({ 
    profile, 
    onSubmit, 
    onCancel,
    isSubmitting = false 
}) => {
    const { register, handleSubmit, formState: { errors } } = useForm<ProfileData>({
        defaultValues: profile
    });
    const { getErrorMessage } = useProfileErrors();

    const onSubmitHandler = async (data: ProfileData) => {
        try {
            await onSubmit(data);
        } catch (error) {
            console.error('Profile update failed:', error);
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
                <label htmlFor="firstName">First Name</label>
                <input
                    id="firstName"
                    type="text"
                    aria-invalid={errors.firstName ? "true" : "false"}
                    {...register("firstName", { 
                        required: "First name is required",
                        minLength: { value: 2, message: "First name must be at least 2 characters" }
                    })}
                />
                {errors.firstName && (
                    <span role="alert" className="error">{getErrorMessage(errors.firstName)}</span>
                )}
            </div>

            <div className="form-field">
                <label htmlFor="lastName">Last Name</label>
                <input
                    id="lastName"
                    type="text"
                    aria-invalid={errors.lastName ? "true" : "false"}
                    {...register("lastName", { 
                        required: "Last name is required",
                        minLength: { value: 2, message: "Last name must be at least 2 characters" }
                    })}
                />
                {errors.lastName && (
                    <span role="alert" className="error">{getErrorMessage(errors.lastName)}</span>
                )}
            </div>

            <div className="form-field">
                <label htmlFor="email">Email</label>
                <input
                    id="email"
                    type="email"
                    aria-invalid={errors.email ? "true" : "false"}
                    {...register("email", { 
                        required: "Email is required",
                        pattern: {
                            value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                            message: "Invalid email address"
                        }
                    })}
                />
                {errors.email && (
                    <span role="alert" className="error">{getErrorMessage(errors.email)}</span>
                )}
            </div>

            <div className="form-actions">
                <button 
                    type="submit" 
                    disabled={isSubmitting}
                    aria-busy={isSubmitting}
                >
                    {isSubmitting ? 'Saving...' : 'Save'}
                </button>
                <button 
                    type="button" 
                    onClick={onCancel}
                    disabled={isSubmitting}
                >
                    Cancel
                </button>
            </div>
        </form>
    );
}; 
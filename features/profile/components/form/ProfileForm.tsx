import React, { useReducer, useCallback, useEffect } from 'react';
import { ProfileData } from '../../types/profile';
import { ValidationService } from '../../services/ValidationService';
import { FormValidationResult } from '../../types/validation';
import { Events } from '../../../../dashboard/core/events';
import { PROFILE_EVENTS } from '../../types/events';
import BasicSection from './sections/BasicSection';
import PhysicalSection from './sections/PhysicalSection';
import MedicalSection from './sections/MedicalSection';
import { SaveAlert } from '../SaveAlert';

// Form sections
const SECTIONS = [
    { id: 'basic', label: 'Basic Information', Component: BasicSection },
    { id: 'physical', label: 'Physical Information', Component: PhysicalSection },
    { id: 'medical', label: 'Medical Information', Component: MedicalSection }
] as const;

type SectionId = typeof SECTIONS[number]['id'];

// Initial form state
const initialState: Partial<ProfileData> = {
    username: '',
    email: '',
    displayName: '',
    firstName: '',
    lastName: '',
    age: null,
    gender: '',
    height: null,
    weight: null,
    fitnessLevel: null,
    activityLevel: null,
    medicalConditions: [],
    exerciseLimitations: [],
    medications: ''
};

// Form actions
type FormAction =
    | { type: 'UPDATE_FIELD'; field: keyof ProfileData; value: any }
    | { type: 'SET_VALIDATION'; validation: FormValidationResult }
    | { type: 'SET_SAVING'; isSaving: boolean }
    | { type: 'SET_LOADING'; isLoading: boolean }
    | { type: 'SET_SAVE_ERROR'; error: string | null }
    | { type: 'SET_SAVE_SUCCESS'; success: boolean }
    | { type: 'RESET_SAVE_STATUS' }
    | { type: 'SET_SECTION'; section: SectionId }
    | { type: 'SET_INITIAL_DATA'; data: Partial<ProfileData> };

// Form state interface
interface FormState {
    data: Partial<ProfileData>;
    validation: FormValidationResult;
    isSaving: boolean;
    isLoading: boolean;
    saveError: string | null;
    saveSuccess: boolean;
    currentSection: SectionId;
}

// Form reducer
const formReducer = (state: FormState, action: FormAction): FormState => {
    switch (action.type) {
        case 'UPDATE_FIELD':
            return {
                ...state,
                data: { ...state.data, [action.field]: action.value }
            };
        case 'SET_VALIDATION':
            return {
                ...state,
                validation: action.validation
            };
        case 'SET_SAVING':
            return {
                ...state,
                isSaving: action.isSaving
            };
        case 'SET_LOADING':
            return {
                ...state,
                isLoading: action.isLoading
            };
        case 'SET_SAVE_ERROR':
            return {
                ...state,
                saveError: action.error,
                isSaving: false
            };
        case 'SET_SAVE_SUCCESS':
            return {
                ...state,
                saveSuccess: action.success,
                isSaving: false
            };
        case 'RESET_SAVE_STATUS':
            return {
                ...state,
                saveSuccess: false,
                saveError: null
            };
        case 'SET_SECTION':
            return {
                ...state,
                currentSection: action.section
            };
        case 'SET_INITIAL_DATA':
            return {
                ...state,
                data: action.data,
                isLoading: false
            };
        default:
            return state;
    }
};

export const ProfileForm: React.FC = () => {
    const [state, dispatch] = useReducer(formReducer, {
        data: initialState,
        validation: { isValid: true, fieldErrors: {}, generalErrors: [] },
        isSaving: false,
        isLoading: true,
        saveError: null,
        saveSuccess: false,
        currentSection: 'basic'
    });

    const validationService = new ValidationService();

    // Load initial data
    useEffect(() => {
        const handleProfileData = (data: ProfileData) => {
            dispatch({ type: 'SET_INITIAL_DATA', data });
        };

        Events.emit(PROFILE_EVENTS.FETCH_REQUEST, { type: PROFILE_EVENTS.FETCH_REQUEST });
        Events.on(PROFILE_EVENTS.FETCH_SUCCESS, handleProfileData);

        return () => {
            Events.off(PROFILE_EVENTS.FETCH_SUCCESS, handleProfileData);
        };
    }, []);

    const handleFieldChange = useCallback((name: string, value: any) => {
        dispatch({ type: 'UPDATE_FIELD', field: name as keyof ProfileData, value });
        dispatch({ type: 'RESET_SAVE_STATUS' });

        // Validate the form after field update
        const formValidation = validationService.validateForm({ ...state.data, [name]: value });
        dispatch({ type: 'SET_VALIDATION', validation: formValidation });
    }, [state.data]);

    const handleSubmit = useCallback(async () => {
        dispatch({ type: 'RESET_SAVE_STATUS' });
        
        // Validate the entire form
        const formValidation = validationService.validateForm(state.data);
        if (!formValidation.isValid) {
            dispatch({ type: 'SET_VALIDATION', validation: formValidation });
            return;
        }

        // Start save process
        dispatch({ type: 'SET_SAVING', isSaving: true });

        try {
            // Emit update request event
            Events.emit(PROFILE_EVENTS.UPDATE_REQUEST, {
                type: PROFILE_EVENTS.UPDATE_REQUEST,
                payload: state.data
            });

            // Handle successful save
            dispatch({ type: 'SET_SAVE_SUCCESS', success: true });
            Events.emit(PROFILE_EVENTS.UPDATE_SUCCESS, {
                type: PROFILE_EVENTS.UPDATE_SUCCESS,
                payload: state.data
            });
        } catch (error) {
            // Handle save error
            const errorMessage = error instanceof Error ? error.message : 'Failed to save profile';
            dispatch({ type: 'SET_SAVE_ERROR', error: errorMessage });
            Events.emit(PROFILE_EVENTS.UPDATE_ERROR, {
                type: PROFILE_EVENTS.UPDATE_ERROR,
                error: { message: errorMessage }
            });
        }
    }, [state.data]);

    const handleSectionChange = (section: SectionId) => {
        dispatch({ type: 'SET_SECTION', section });
    };

    if (state.isLoading) {
        return (
            <div className="profile-form-loading">
                <div className="spinner"></div>
                <p>Loading profile data...</p>
            </div>
        );
    }

    const CurrentSection = SECTIONS.find(s => s.id === state.currentSection)?.Component;

    return (
        <div className="profile-form">
            <SaveAlert
                success={state.saveSuccess}
                error={state.saveError}
                onDismiss={() => dispatch({ type: 'RESET_SAVE_STATUS' })}
            />

            <div className="section-navigation">
                {SECTIONS.map(({ id, label }) => (
                    <button
                        key={id}
                        className={`nav-button ${state.currentSection === id ? 'active' : ''}`}
                        onClick={() => handleSectionChange(id)}
                    >
                        {label}
                    </button>
                ))}
            </div>

            {CurrentSection && (
                <CurrentSection
                    data={state.data}
                    onChange={handleFieldChange}
                    validation={state.validation}
                />
            )}

            <div className="form-actions">
                <button 
                    type="button" 
                    onClick={handleSubmit}
                    disabled={state.isSaving || !state.validation.isValid}
                >
                    {state.isSaving ? 'Saving...' : 'Save Profile'}
                </button>
            </div>

            {!state.validation.isValid && state.validation.generalErrors.length > 0 && (
                <div className="form-errors">
                    {state.validation.generalErrors.map((error, index) => (
                        <div key={index} className="error-message">
                            {error}
                        </div>
                    ))}
                </div>
            )}

            <style>{`
                .profile-form {
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                }

                .profile-form-loading {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    min-height: 200px;
                }

                .spinner {
                    width: 40px;
                    height: 40px;
                    border: 3px solid #f3f3f3;
                    border-top: 3px solid #0070f3;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin-bottom: 16px;
                }

                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }

                .section-navigation {
                    display: flex;
                    gap: 8px;
                    margin-bottom: 24px;
                    border-bottom: 1px solid #eaeaea;
                    padding-bottom: 16px;
                }

                .nav-button {
                    padding: 8px 16px;
                    border: none;
                    background: none;
                    cursor: pointer;
                    font-size: 16px;
                    color: #666;
                    border-bottom: 2px solid transparent;
                    transition: all 0.2s;
                }

                .nav-button:hover {
                    color: #0070f3;
                }

                .nav-button.active {
                    color: #0070f3;
                    border-bottom-color: #0070f3;
                }

                .form-actions {
                    margin-top: 30px;
                    text-align: center;
                }

                .form-actions button {
                    padding: 10px 20px;
                    font-size: 16px;
                    background-color: #0070f3;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    transition: background-color 0.2s;
                }

                .form-actions button:hover:not(:disabled) {
                    background-color: #0051cc;
                }

                .form-actions button:disabled {
                    background-color: #ccc;
                    cursor: not-allowed;
                }

                .form-errors {
                    margin-top: 20px;
                    padding: 15px;
                    border: 1px solid #ff4444;
                    border-radius: 4px;
                    background-color: #fff5f5;
                }

                .error-message {
                    color: #ff4444;
                    margin: 5px 0;
                }
            `}</style>
        </div>
    );
};

export default ProfileForm; 
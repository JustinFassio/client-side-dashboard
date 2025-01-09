import { FieldError } from 'react-hook-form';

export const useProfileErrors = () => {
    const getErrorMessage = (error: FieldError | undefined): string => {
        if (!error) return '';
        
        if (typeof error.message === 'string') {
            return error.message;
        }

        switch (error.type) {
            case 'required':
                return 'This field is required';
            case 'minLength':
                return 'Value is too short';
            case 'maxLength':
                return 'Value is too long';
            case 'pattern':
                return 'Invalid format';
            default:
                return 'Invalid value';
        }
    };

    return {
        getErrorMessage
    };
}; 
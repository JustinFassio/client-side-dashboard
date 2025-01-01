import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { Events } from '../../../../dashboard/core/events';
import { PROFILE_EVENTS } from '../../../types/events';
import ProfileForm from '../ProfileForm';

// Mock the Events module
jest.mock('../../../../dashboard/core/events', () => ({
    Events: {
        emit: jest.fn(),
        on: jest.fn(),
        off: jest.fn()
    }
}));

describe('ProfileForm', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('shows loading state initially', () => {
        render(<ProfileForm />);
        expect(screen.getByText('Loading profile data...')).toBeInTheDocument();
        expect(screen.getByTestId('spinner')).toBeInTheDocument();
    });

    it('loads initial profile data', async () => {
        const mockProfileData = {
            displayName: 'John Doe',
            email: 'john@example.com',
            age: 25
        };

        render(<ProfileForm />);

        // Verify fetch request was emitted
        expect(Events.emit).toHaveBeenCalledWith(
            PROFILE_EVENTS.FETCH_REQUEST,
            expect.any(Object)
        );

        // Simulate successful data fetch
        const fetchSuccessHandler = (Events.on as jest.Mock).mock.calls.find(
            call => call[0] === PROFILE_EVENTS.FETCH_SUCCESS
        )[1];
        fetchSuccessHandler(mockProfileData);

        // Wait for loading state to clear
        await waitFor(() => {
            expect(screen.queryByText('Loading profile data...')).not.toBeInTheDocument();
        });

        // Verify data is displayed
        expect(screen.getByDisplayValue('John Doe')).toBeInTheDocument();
        expect(screen.getByDisplayValue('john@example.com')).toBeInTheDocument();
        expect(screen.getByDisplayValue('25')).toBeInTheDocument();
    });

    it('renders all form sections and navigation', async () => {
        render(<ProfileForm />);
        
        // Simulate data load
        const fetchSuccessHandler = (Events.on as jest.Mock).mock.calls.find(
            call => call[0] === PROFILE_EVENTS.FETCH_SUCCESS
        )[1];
        fetchSuccessHandler({});

        await waitFor(() => {
            // Check for section navigation buttons
            expect(screen.getByText('Basic Information')).toBeInTheDocument();
            expect(screen.getByText('Physical Information')).toBeInTheDocument();
            expect(screen.getByText('Medical Information')).toBeInTheDocument();
        });
    });

    it('handles section navigation correctly', async () => {
        render(<ProfileForm />);
        
        // Simulate data load
        const fetchSuccessHandler = (Events.on as jest.Mock).mock.calls.find(
            call => call[0] === PROFILE_EVENTS.FETCH_SUCCESS
        )[1];
        fetchSuccessHandler({});

        await waitFor(() => {
            // Initially shows Basic Information section
            expect(screen.getByText('Basic Information')).toHaveClass('active');
        });

        // Navigate to Physical Information section
        fireEvent.click(screen.getByText('Physical Information'));
        expect(screen.getByText('Physical Information')).toHaveClass('active');
        expect(screen.getByText('Height (cm)')).toBeInTheDocument();

        // Navigate to Medical Information section
        fireEvent.click(screen.getByText('Medical Information'));
        expect(screen.getByText('Medical Information')).toHaveClass('active');
        expect(screen.getByText('Medical Conditions')).toBeInTheDocument();
    });

    it('maintains form state across section changes', async () => {
        render(<ProfileForm />);
        
        // Simulate data load
        const fetchSuccessHandler = (Events.on as jest.Mock).mock.calls.find(
            call => call[0] === PROFILE_EVENTS.FETCH_SUCCESS
        )[1];
        fetchSuccessHandler({});

        await waitFor(() => {
            // Fill in basic information
            fireEvent.change(screen.getByLabelText('Display Name'), { 
                target: { value: 'John Doe' } 
            });
        });

        // Navigate to Physical Information
        fireEvent.click(screen.getByText('Physical Information'));
        fireEvent.change(screen.getByLabelText('Height (cm)'), { 
            target: { value: '180' } 
        });

        // Navigate back to Basic Information
        fireEvent.click(screen.getByText('Basic Information'));
        expect(screen.getByDisplayValue('John Doe')).toBeInTheDocument();
    });

    it('handles field changes correctly', async () => {
        render(<ProfileForm />);
        
        // Simulate data load
        const fetchSuccessHandler = (Events.on as jest.Mock).mock.calls.find(
            call => call[0] === PROFILE_EVENTS.FETCH_SUCCESS
        )[1];
        fetchSuccessHandler({});

        await waitFor(() => {
            // Test text input
            const displayNameInput = screen.getByLabelText('Display Name');
            fireEvent.change(displayNameInput, { target: { value: 'John Doe' } });
            expect(displayNameInput).toHaveValue('John Doe');

            // Test number input
            const ageInput = screen.getByLabelText('Age');
            fireEvent.change(ageInput, { target: { value: '25' } });
            expect(ageInput).toHaveValue(25);

            // Test select input
            const genderSelect = screen.getByLabelText('Gender');
            fireEvent.change(genderSelect, { target: { value: 'male' } });
            expect(genderSelect).toHaveValue('male');
        });
    });

    it('validates required fields', async () => {
        render(<ProfileForm />);
        
        // Simulate data load
        const fetchSuccessHandler = (Events.on as jest.Mock).mock.calls.find(
            call => call[0] === PROFILE_EVENTS.FETCH_SUCCESS
        )[1];
        fetchSuccessHandler({});

        await waitFor(() => {
            // Try to submit without required fields
            const submitButton = screen.getByText('Save Profile');
            fireEvent.click(submitButton);

            // Check for validation messages
            expect(screen.getByText('Display Name is required')).toBeInTheDocument();
            expect(screen.getByText('Email is required')).toBeInTheDocument();
        });
    });

    it('validates email format', async () => {
        render(<ProfileForm />);
        
        // Simulate data load
        const fetchSuccessHandler = (Events.on as jest.Mock).mock.calls.find(
            call => call[0] === PROFILE_EVENTS.FETCH_SUCCESS
        )[1];
        fetchSuccessHandler({});

        await waitFor(() => {
            // Enter invalid email
            const emailInput = screen.getByLabelText('Email');
            fireEvent.change(emailInput, { target: { value: 'invalid-email' } });

            // Try to submit
            const submitButton = screen.getByText('Save Profile');
            fireEvent.click(submitButton);

            // Check for validation message
            expect(screen.getByText('Please enter a valid email address')).toBeInTheDocument();
        });
    });

    it('emits events on successful form submission', async () => {
        render(<ProfileForm />);
        
        // Simulate data load
        const fetchSuccessHandler = (Events.on as jest.Mock).mock.calls.find(
            call => call[0] === PROFILE_EVENTS.FETCH_SUCCESS
        )[1];
        fetchSuccessHandler({});

        await waitFor(() => {
            // Fill in required fields
            fireEvent.change(screen.getByLabelText('Display Name'), { 
                target: { value: 'John Doe' } 
            });
            fireEvent.change(screen.getByLabelText('Email'), { 
                target: { value: 'john@example.com' } 
            });

            // Submit form
            const submitButton = screen.getByText('Save Profile');
            fireEvent.click(submitButton);

            // Verify events were emitted
            expect(Events.emit).toHaveBeenCalledWith(
                PROFILE_EVENTS.UPDATE_REQUEST,
                expect.objectContaining({
                    type: PROFILE_EVENTS.UPDATE_REQUEST,
                    payload: expect.objectContaining({
                        displayName: 'John Doe',
                        email: 'john@example.com'
                    })
                })
            );
        });
    });

    it('cleans up event listeners on unmount', () => {
        const { unmount } = render(<ProfileForm />);
        unmount();
        
        expect(Events.off).toHaveBeenCalledWith(
            PROFILE_EVENTS.FETCH_SUCCESS,
            expect.any(Function)
        );
    });
}); 
import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Navigation } from '../index';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { Feature, FeatureContext, FeatureMetadata } from '@dashboard/contracts/Feature';

class MockFeature implements Feature {
  constructor(
    public readonly identifier: string,
    public readonly metadata: FeatureMetadata
  ) {}

  async register(_context: FeatureContext): Promise<void> {}
  async init(): Promise<void> {}
  isEnabled(): boolean { return true; }
  render(): JSX.Element | null { return null; }
  async cleanup(): Promise<void> {}
}

describe('Navigation', () => {
  const mockFeatures: Feature[] = [
    new MockFeature('dashboard', {
      name: 'Dashboard',
      description: 'Main dashboard',
      order: 1
    }),
    new MockFeature('profile', {
      name: 'Profile', 
      description: 'User profile',
      order: 2
    }),
    new MockFeature('workouts', {
      name: 'Workouts',
      description: 'Workout plans',
      order: 3
    }),
    new MockFeature('settings', {
      name: 'Settings',
      description: 'User settings',
      order: 4
    })
  ];

  const renderWithRouter = (ui: React.ReactElement, { route = '/' } = {}) => {
    window.history.pushState({}, 'Test page', route);
    
    return render(
      <MemoryRouter initialEntries={[route]}>
        <Routes>
          <Route path="/*" element={ui} />
        </Routes>
      </MemoryRouter>
    );
  };

  beforeAll(() => {
    // Mock window.athleteDashboardData
    window.athleteDashboardData = {
      apiUrl: 'http://test.local/wp-json',
      nonce: 'test-nonce',
      siteUrl: 'http://test.local',
      debug: false,
      userId: 1,
      feature: {
        name: 'dashboard',
        label: 'Dashboard'
      }
    };
  });

  it('renders all navigation links', () => {
    renderWithRouter(<Navigation features={mockFeatures} />);

    expect(screen.getByRole('button', { name: /dashboard/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /profile/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /workouts/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /settings/i })).toBeInTheDocument();
  });

  it('highlights active link based on current route', () => {
    renderWithRouter(<Navigation features={mockFeatures} currentFeature="profile" />);

    const profileButton = screen.getByRole('button', { name: /profile/i });
    expect(profileButton).toHaveClass('active');
    
    const dashboardButton = screen.getByRole('button', { name: /dashboard/i });
    expect(dashboardButton).not.toHaveClass('active');
  });

  it('navigates to correct route when clicking links', async () => {
    renderWithRouter(<Navigation features={mockFeatures} />);

    const profileButton = screen.getByRole('button', { name: /profile/i });
    await userEvent.click(profileButton);
    
    expect(window.location.search).toContain('dashboard_feature=profile');
  });
}); 
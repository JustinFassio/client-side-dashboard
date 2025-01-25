import React from 'react';
import { render, act } from '@testing-library/react';
import { ProfileFeature } from '../../ProfileFeature';
import { mockContext, mockProfileData } from '../fixtures/profile';
import { performance } from 'perf_hooks';

describe('ProfileFeature Performance', () => {
    let profileFeature: ProfileFeature;

    beforeEach(() => {
        profileFeature = new ProfileFeature();
    });

    it('should initialize within performance budget', async () => {
        const start = performance.now();
        
        await profileFeature.register(mockContext);
        await profileFeature.init();
        
        const end = performance.now();
        const initTime = end - start;
        
        expect(initTime).toBeLessThan(500); // 500ms budget for initialization
    });

    it('should render initial state within performance budget', async () => {
        await profileFeature.register(mockContext);
        await profileFeature.init();

        const start = performance.now();
        
        const { container } = render(profileFeature.render({ userId: 1 }));
        
        const end = performance.now();
        const renderTime = end - start;
        
        expect(renderTime).toBeLessThan(100); // 100ms budget for initial render
    });

    it('should update profile within performance budget', async () => {
        await profileFeature.register(mockContext);
        await profileFeature.init();

        const { container } = render(profileFeature.render({ userId: 1 }));

        const start = performance.now();
        
        await act(async () => {
            await profileFeature.handleEvent({
                type: 'PROFILE_UPDATE',
                payload: {
                    ...mockProfileData,
                    basic: {
                        ...mockProfileData.basic,
                        name: 'Performance Test'
                    }
                }
            });
        });
        
        const end = performance.now();
        const updateTime = end - start;
        
        expect(updateTime).toBeLessThan(200); // 200ms budget for updates
    });

    it('should handle concurrent updates efficiently', async () => {
        await profileFeature.register(mockContext);
        await profileFeature.init();

        const { container } = render(profileFeature.render({ userId: 1 }));

        const start = performance.now();
        
        await Promise.all([
            profileFeature.handleEvent({
                type: 'PROFILE_UPDATE',
                payload: {
                    ...mockProfileData,
                    basic: { ...mockProfileData.basic, name: 'Test 1' }
                }
            }),
            profileFeature.handleEvent({
                type: 'PROFILE_UPDATE',
                payload: {
                    ...mockProfileData,
                    basic: { ...mockProfileData.basic, name: 'Test 2' }
                }
            }),
            profileFeature.handleEvent({
                type: 'PROFILE_UPDATE',
                payload: {
                    ...mockProfileData,
                    basic: { ...mockProfileData.basic, name: 'Test 3' }
                }
            })
        ]);
        
        const end = performance.now();
        const concurrentTime = end - start;
        
        expect(concurrentTime).toBeLessThan(600); // 600ms budget for concurrent updates
    });

    it('should maintain performance with large datasets', async () => {
        const largeProfile = {
            ...mockProfileData,
            medical: {
                ...mockProfileData.medical,
                conditions: Array(1000).fill('Test Condition'),
                medications: Array(1000).fill('Test Medication'),
                allergies: Array(1000).fill('Test Allergy')
            },
            injuries: Array(1000).fill({
                type: 'Test Injury',
                date: '2023-01-01',
                status: 'active'
            })
        };

        await profileFeature.register(mockContext);
        await profileFeature.init();

        const start = performance.now();
        
        const { container } = render(profileFeature.render({ userId: 1 }));
        
        await act(async () => {
            await profileFeature.handleEvent({
                type: 'PROFILE_UPDATE',
                payload: largeProfile
            });
        });
        
        const end = performance.now();
        const largeDataTime = end - start;
        
        expect(largeDataTime).toBeLessThan(1000); // 1s budget for large dataset
    });

    it('should maintain smooth navigation between sections', async () => {
        await profileFeature.register(mockContext);
        await profileFeature.init();

        const { container } = render(profileFeature.render({ userId: 1 }));

        const sections = ['basic', 'medical', 'account', 'physical'];
        const navigationTimes: number[] = [];

        for (const section of sections) {
            const start = performance.now();
            
            await act(async () => {
                await profileFeature.handleEvent({
                    type: 'SECTION_CHANGE',
                    payload: { section }
                });
            });
            
            const end = performance.now();
            navigationTimes.push(end - start);
        }

        const averageNavigationTime = navigationTimes.reduce((a, b) => a + b) / navigationTimes.length;
        expect(averageNavigationTime).toBeLessThan(50); // 50ms budget for section navigation
    });
}); 
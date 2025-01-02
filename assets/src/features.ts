import { Feature } from '../../dashboard/contracts/Feature';
import { ProfileFeature } from '../../features/profile/ProfileFeature';
import { OverviewFeature } from '../../features/overview/OverviewFeature';

// Initialize features
const features: Feature[] = [
    new OverviewFeature(),
    new ProfileFeature()
];

export default features; 
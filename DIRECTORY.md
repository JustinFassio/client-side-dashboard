Root Directory

ATHLETE-DASHBOARD-CHILD/
├── assets/
├── bin/
├── dashboard/
├── features/
├── includes/
├── node_modules/
├── tests/
├── vendor/
├── .gitignore
├── ARCHITECTURE.md
├── athlete-dashboard.php
├── composer.lock
├── footer.php
├── functions.php
├── header.php
├── jest.config.js
├── jest.setup.js
├── package-lock.json
├── package.json
├── README.md
├── ROADMAP.md
├── style.css
├── tsconfig.json
└── webpack.config.js


Assets Directory Structure


assets/
│   ├── build/
│   ├── src/
│   │   ├── components/
│   │   │   └── App.tsx
│   │   ├── styles/
│   │   ├── features.ts
│   │   └── main.tsx


Dashboard Directory Structure


dashboard/
├── api
│   └──  profile-endpoint.php
├── components
│   ├── DashboardShell
│   │   ├── DashboardShell.css
│   │   └── index.tsx
│   ├── ErrorBoundary
│   │   └── index.tsx
│   ├── FeatureRouter
│   │   └── index.tsx
│   ├── LoadingState
│   │   └── index.tsx
│   ├── Navigation
│   │   ├── __tests__
│   │   │   └── Navigation.test.tsx
│   │   ├── Navigation.css
│   │   └── index.tsx
│   ├── Spinner
│   │   ├── Spinner.css
│   │   ├── index.d.ts
│   │   └── index.tsx
├── constants
│   └── api.ts
├── contracts
│   ├── Events.ts
│   └── Feature.ts
├── core
│   ├── config
│   │   ├── debug.php
│   │   └── environment.php
│   ├── services
│   │   └── BaseFeatureService.ts
│   ├── store
│   │   └── index.ts
│   ├── testing
│   │   └── FeatureTestUtils.ts
│   ├── config.ts
│   ├── dashboardbridge.php
│   ├── events.ts
│   ├── Feature.ts
│   ├── FeatureRegistry.ts
│   ├──types.ts
│   ├── config.ts
│   ├── dashboardbridge.php
│   ├── events.ts
│   ├── Feature.ts
│   ├── FeatureRegistry.ts
│   └── types.ts
├── features
│   ├── overview
│   │   ├── components
│   │   │   ├── OverviewLayout.css
│   │   │   └── OverviewLayout.tsx
│   │   ├── services
│   │   │   └── OverviewService.ts
│   │   └── OverviewFeature.tsx
├── hooks
│   └── useUser.ts
├── services
│   └── api.ts
├── styles
│   ├── base
│   │   ├── layout.css
│   │   ├── reset.css
│   │   └── typography.css
│   ├── components
│   │   └── DashboardShell.css
│   ├── main.css
│   └── variables.css
├── templates
│   ├── dashboard.php
│   ├── feature-router.php
│   ├── footer-minimal.php
│   └── header-minimal.php
├── testing
│   ├── mocks
│   │   ├── index.ts
│   │   └── mocks.ts
├── types
│   ├── api.ts
│   ├── config.d.ts
│   ├── config.ts
│   ├── feature.ts
│   ├── global.d.ts
│   └── wordpress.d.ts
├── utils
│   └── date.ts
└── events.ts



Features Directory

features/
├── auth/
│   ├── api/
│   │   └── registration-endpoints.php
│   └── types/
│       └── registration.ts
├── overview/
│   ├── components/
│   │   └── layout/
│   │       └── index.tsx
│   └── OverviewFeature.tsx
├── profile/
│   ├── __tests__/
│   │   ├── components/
│   │   ├── endpoints/
│   │   │   └── profile-endpoint.test.php
│   │   ├── events/
│   │   │   └── ProfileEvents.test.ts
│   │   └── services/
│   ├── api/
│   │   └── profile-endpoints.php
│   ├── assets/
│   │   ├── js/
│   │   │   └── profileService.ts
│   │   └── styles/
│   │       ├── base/
│   │       │   ├── forms.css
│   │       │   └── layout.css
│   │       ├── components/
│   │       │   ├── CoreSection.css
│   │       │   ├── InjuryTracker.css
│   │       │   ├── PhysicalMetrics.css
│   │       │   ├── ProfileForm.css
│   │       │   └── index.css
│   ├── components/
│   │   ├── form/
│   │   │   ├── __tests__/
│   │   │   │   └── ProfileForm.test.tsx
│   │   │   ├── fields/
│   │   │   │   └── FormField.tsx
│   │   │   └── sections/
│   │   │       ├── AccountSection.tsx
│   │   │       ├── BasicSection.tsx
│   │   │       ├── MedicalSection.tsx
│   │   │       ├── PhysicalSection.css
│   │   │       └── PhysicalSection.tsx
│   │   ├── InjuryTracker/
│   │   │   ├── index.tsx
│   │   │   ├── styles.css
│   │   │   └── types.ts
│   │   ├── layout/
│   │   │   ├── index.ts
│   │   │   ├── index.tsx
│   │   │   └── ProfileLayout.tsx
│   │   ├── PhysicalMetricsDisplay/
│   │   │   ├── index.tsx
│   │   │   ├── PhysicalMetricField.tsx
│   │   │   └── styles.scss
│   │   ├── SaveAlert.tsx
│   │   └── Section/
│   │       └── index.tsx
│   │   ├── Tracker/
│   │   │   ├── index.tsx
│   │   │   └── styles.css
│   ├── config/
           ├── index.ts
│   │   └── meta_keys.php
│   ├── context/
│   │   └── ProfileContext.tsx
│   ├── events/
│   │   ├── __tests__/
│   │   │   └── events.test.ts
│   │   ├── compatibility.ts
│   │   ├── constants.ts
│   │   ├── events.ts
│   │   ├── handlers.ts
│   │   ├── index.ts
│   │   ├── types.ts
│   │   └── utils.ts
│   ├── services/
│   │   ├── ProfileService.ts
│   │   └── ValidationService.ts
│   ├── types/
│   │   ├── events.ts
│   │   ├── physical-metrics.ts
│   │   ├── profile.ts
│   │   ├── validation.ts
│   │   └── window.d.ts
│   ├── utils
│   │   ├── config.php
│   │   └── validation.ts
│   ├── ProfileFeature.ts
│   ├── ProfileFeature.tsx
│   └── README.md
└── user/
    └── context/
        └── UserContext.tsx


Includes Directory

includes/
├── admin/
│   └── user-profile.php
├── features/profile
│   └── meta_keys.php
├── rest-api/
│   ├── __tests__/
│   │      └── overview-controller.test.php
│   ├── class-overview-controller.php
│   ├── profile-endpoints.php
├── class-rest-api.php
└── rest-api.php

Tests Directory


tests/
├── php/
│   └── endpoints/
│       └── class-profile-endpoint-test.php
├── framework/
├── reports/
│   └── summary.txt
└── README.md





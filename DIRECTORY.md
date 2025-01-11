# Directory Structure

```
.
├── ARCHITECTURE.md
├── DIRECTORY.md
├── README.md
├── ROADMAP.md
├── assets
│   ├── build
│   ├── css
│   │   ├── admin.css
│   │   └── cache-stats-widget.css
│   ├── js
│   │   ├── admin.js
│   │   └── cache-stats-widget.js
│   └── src
│       ├── components
│       │   └── App.tsx
│       ├── features.ts
│       ├── main.tsx
│       └── styles
├── bin
│   └── run-tests.sh
├── composer.lock
├── dashboard
│   ├── api
│   │   └── profile-endpoint.php
│   ├── components
│   │   ├── DashboardShell
│   │   ├── ErrorBoundary
│   │   ├── ErrorMessage
│   │   ├── FeatureRouter
│   │   ├── LoadingSpinner
│   │   ├── LoadingState
│   │   ├── Navigation
│   │   └── Spinner
│   ├── constants
│   ├── contracts
│   ├── core
│   ├── events.ts
│   ├── features
│   ├── hooks
│   ├── services
│   ├── styles
│   ├── templates
│   ├── testing
│   ├── types
│   └── utils
├── docs
│   ├── CACHING.md
│   ├── CHANGELOG.md
│   ├── CONTRIBUTING.md
│   ├── DEPLOYMENT.md
│   ├── FEATURE_AUDIT.md
│   ├── SERVICE_LAYER_PLAN.md
│   ├── TESTING.md
│   └── api
│       ├── ENDPOINTS.md
│       ├── ERROR_HANDLING.md
│       ├── GETTING_STARTED.md
│       ├── RATE_LIMITING_AND_VALIDATION.md
│       └── README.md
├── features
│   ├── README.template.md
│   ├── auth
│   ├── equipment
│   ├── overview
│   ├── profile
│   ├── user
│   └── workout-generator
├── footer.php
├── functions.php
├── header.php
├── includes
│   ├── admin
│   │   ├── class-cache-stats-widget.php
│   │   └── user-profile.php
│   ├── class-athlete-dashboard.php
│   ├── class-rest-api.php
│   ├── config
│   │   ├── cache-config.php
│   │   └── endpoints.php
│   ├── rest-api
│   │   ├── __tests__
│   │   ├── class-overview-controller.php
│   │   ├── class-profile-controller.php
│   │   ├── class-rate-limiter.php
│   │   ├── class-request-validator.php
│   │   ├── class-rest-controller-base.php
│   │   └── profile-endpoints.php
│   ├── rest-api.php
│   └── services
│       ├── class-cache-monitor.php
│       ├── class-cache-service.php
│       └── class-cache-warmer.php
├── jest.config.js
├── jest.setup.js
├── package-lock.json
├── package.json
├── phpunit.xml
├── style.css
├── tests
│   ├── README.md
│   ├── bootstrap.php
│   ├── php
│   │   ├── TestCase.php
│   │   ├── endpoints
│   │   ├── framework
│   │   ├── helpers.php
│   │   ├── rest-api
│   │   └── services
│   └── reports
│       └── summary.txt
├── tsconfig.json
└── webpack.config.js
```

Note: This tree view excludes `node_modules`, `vendor`, and `.git` directories for clarity. The project contains 121 directories and 256 files in total.





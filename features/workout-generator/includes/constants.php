<?php
/**
 * Constants for the Workout Generator feature
 */

namespace AthleteDashboard\Features\WorkoutGenerator;

// User Tiers
const TIER_FOUNDATION = 'foundation';
const TIER_PERFORMANCE = 'performance';
const TIER_TRANSFORMATION = 'transformation';

// Default tier settings
const DEFAULT_TIER = TIER_FOUNDATION;

// Rate limiting defaults
const DEFAULT_RATE_WINDOW = 3600; // 1 hour in seconds 
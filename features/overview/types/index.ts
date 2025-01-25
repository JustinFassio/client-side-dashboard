import {
    ApiResponse,
    FeatureConfig
} from '../../../dashboard/types';

/**
 * Overview feature configuration
 */
export interface OverviewConfig extends FeatureConfig {
    widgets: {
        recentActivity: boolean;
        progressMetrics: boolean;
        upcomingWorkouts: boolean;
        goals: boolean;
    };
    refresh: {
        interval: number;
        autoRefresh: boolean;
    };
    display: {
        defaultTimeRange: DateRange;
        maxMetrics: number;
        chartType: 'line' | 'bar';
    };
}

/**
 * Activity interface
 */
export interface Activity {
    id: string;
    type: 'workout' | 'achievement' | 'goal' | 'milestone';
    title: string;
    description?: string;
    timestamp: number;
    data?: Record<string, any>;
    meta?: {
        icon?: string;
        color?: string;
        priority?: number;
    };
}

/**
 * Metric interface
 */
export interface Metric {
    id: string;
    name: string;
    value: number;
    unit?: string;
    trend?: {
        direction: 'up' | 'down' | 'stable';
        percentage: number;
    };
    target?: number;
    history?: Array<{
        timestamp: number;
        value: number;
    }>;
}

/**
 * Goal interface
 */
export interface Goal {
    id: string;
    title: string;
    description?: string;
    target: number;
    current: number;
    unit?: string;
    deadline?: number;
    status: 'pending' | 'in_progress' | 'completed' | 'failed';
    category?: string;
    progress: number;
}

/**
 * Workout interface
 */
export interface Workout {
    id: string;
    title: string;
    description?: string;
    scheduledTime: number;
    duration: number;
    type: string;
    difficulty: 'beginner' | 'intermediate' | 'advanced';
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
}

/**
 * Overview summary interface
 */
export interface OverviewSummary {
    recentActivity: Activity[];
    metrics: {
        workoutsCompleted: number;
        totalDuration: number;
        averageIntensity: number;
        [key: string]: number;
    };
    goals: Goal[];
    upcomingWorkouts: Workout[];
}

/**
 * Overview widget configuration
 */
export interface OverviewWidgetConfig extends WidgetConfig {
    type: 'activity' | 'metrics' | 'goals' | 'workouts';
    dataRefreshInterval?: number;
    displayOptions?: {
        showHeader?: boolean;
        showFooter?: boolean;
        maxItems?: number;
        chartType?: 'line' | 'bar' | 'pie';
    };
}

/**
 * Overview state interface
 */
export interface OverviewState {
    summary: OverviewSummary | null;
    widgets: OverviewWidgetConfig[];
    selectedTimeRange: DateRange;
    loading: boolean;
    error: DashboardError | null;
}

/**
 * Overview context interface
 */
export interface OverviewContext extends FeatureContext {
    state: OverviewState;
    refresh: () => Promise<void>;
    setTimeRange: (range: DateRange) => void;
    toggleWidget: (widgetId: string) => void;
    updateWidgetConfig: (widgetId: string, config: Partial<OverviewWidgetConfig>) => void;
}

/**
 * Overview event types
 */
export enum OverviewEventType {
    REFRESH_REQUEST = 'OVERVIEW_REFRESH_REQUEST',
    REFRESH_SUCCESS = 'OVERVIEW_REFRESH_SUCCESS',
    REFRESH_ERROR = 'OVERVIEW_REFRESH_ERROR',
    TIMERANGE_CHANGE = 'OVERVIEW_TIMERANGE_CHANGE',
    WIDGET_TOGGLE = 'OVERVIEW_WIDGET_TOGGLE',
    WIDGET_CONFIG_UPDATE = 'OVERVIEW_WIDGET_CONFIG_UPDATE'
}

/**
 * Overview event payloads
 */
export interface OverviewEventPayloads {
    [OverviewEventType.REFRESH_REQUEST]: void;
    [OverviewEventType.REFRESH_SUCCESS]: OverviewSummary;
    [OverviewEventType.REFRESH_ERROR]: DashboardError;
    [OverviewEventType.TIMERANGE_CHANGE]: DateRange;
    [OverviewEventType.WIDGET_TOGGLE]: string;
    [OverviewEventType.WIDGET_CONFIG_UPDATE]: {
        widgetId: string;
        config: Partial<OverviewWidgetConfig>;
    };
}

/**
 * Overview events
 */
export type OverviewEvent<T extends OverviewEventType> = DashboardEvent<OverviewEventPayloads[T]>;

/**
 * Overview error codes
 */
export enum OverviewErrorCode {
    REFRESH_FAILED = 'OVERVIEW_REFRESH_FAILED',
    INVALID_TIMERANGE = 'OVERVIEW_INVALID_TIMERANGE',
    WIDGET_ERROR = 'OVERVIEW_WIDGET_ERROR',
    DATA_FETCH_FAILED = 'OVERVIEW_DATA_FETCH_FAILED',
    NETWORK_ERROR = 'OVERVIEW_NETWORK_ERROR'
}

/**
 * Overview component props
 */
export interface OverviewDashboardProps {
    timeRange: DateRange;
    onTimeRangeChange: (range: DateRange) => void;
    refreshInterval?: number;
    className?: string;
}

export interface MetricsWidgetProps {
    metrics: Metric[];
    chartType?: 'line' | 'bar' | 'pie';
    onMetricClick?: (metric: Metric) => void;
    className?: string;
}

export interface ActivityWidgetProps {
    activities: Activity[];
    maxItems?: number;
    onActivityClick?: (activity: Activity) => void;
    className?: string;
}

export interface GoalsWidgetProps {
    goals: Goal[];
    onGoalClick?: (goal: Goal) => void;
    className?: string;
}

/**
 * Overview hook return type
 */
export interface UseOverview {
    summary: OverviewSummary | null;
    widgets: OverviewWidgetConfig[];
    selectedTimeRange: DateRange;
    loading: boolean;
    error: DashboardError | null;
    refresh: () => Promise<void>;
    setTimeRange: (range: DateRange) => void;
    toggleWidget: (widgetId: string) => void;
    updateWidgetConfig: (widgetId: string, config: Partial<OverviewWidgetConfig>) => void;
} 
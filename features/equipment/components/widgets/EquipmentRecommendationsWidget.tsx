import React, { useEffect, useState } from 'react';
import { Equipment } from '../../types/equipment-types';
import { equipmentAIService } from '../../services/ai/equipment-ai-service';
import { LoadingSpinner } from '../../../../dashboard/components/LoadingSpinner';
import { ErrorMessage } from '../../../../dashboard/components/ErrorMessage';

interface EquipmentRecommendationsWidgetProps {
    equipment: Equipment[];
    userGoals: string[];
    fitnessLevel: string;
    className?: string;
}

interface EquipmentRecommendation {
    type: 'purchase' | 'optimization' | 'maintenance';
    priority: 'high' | 'medium' | 'low';
    description: string;
    reason: string;
    suggestedActions: string[];
}

export const EquipmentRecommendationsWidget: React.FC<EquipmentRecommendationsWidgetProps> = ({
    equipment,
    userGoals,
    fitnessLevel,
    className
}) => {
    const [recommendations, setRecommendations] = useState<EquipmentRecommendation[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchRecommendations = async () => {
            setLoading(true);
            setError(null);

            const response = await equipmentAIService.getEquipmentRecommendations(
                equipment,
                userGoals,
                fitnessLevel
            );

            if (response.error) {
                setError(response.error.message);
            } else if (response.data) {
                setRecommendations(response.data);
            }

            setLoading(false);
        };

        fetchRecommendations();
    }, [equipment, userGoals, fitnessLevel]);

    if (loading) {
        return <LoadingSpinner message="Loading recommendations..." />;
    }

    if (error) {
        return <ErrorMessage error={error} />;
    }

    const getPriorityClass = (priority: EquipmentRecommendation['priority']) => {
        switch (priority) {
            case 'high':
                return 'priority-high';
            case 'medium':
                return 'priority-medium';
            case 'low':
                return 'priority-low';
            default:
                return '';
        }
    };

    const getTypeIcon = (type: EquipmentRecommendation['type']) => {
        switch (type) {
            case 'purchase':
                return '🛒';
            case 'optimization':
                return '⚡';
            case 'maintenance':
                return '🔧';
            default:
                return '💡';
        }
    };

    return (
        <div className={`equipment-recommendations-widget ${className || ''}`}>
            <div className="widget-header">
                <h2>AI Recommendations</h2>
                <p>Personalized suggestions for your equipment setup</p>
            </div>

            {recommendations.length === 0 ? (
                <div className="no-recommendations">
                    <p>No recommendations at this time. Your equipment setup looks good!</p>
                </div>
            ) : (
                <div className="recommendations-list">
                    {recommendations.map((recommendation, index) => (
                        <div
                            key={index}
                            className={`recommendation-card ${getPriorityClass(recommendation.priority)}`}
                        >
                            <div className="recommendation-header">
                                <span className="recommendation-type">
                                    {getTypeIcon(recommendation.type)} {recommendation.type}
                                </span>
                                <span className={`priority-badge ${getPriorityClass(recommendation.priority)}`}>
                                    {recommendation.priority} priority
                                </span>
                            </div>

                            <div className="recommendation-content">
                                <h3>{recommendation.description}</h3>
                                <p className="recommendation-reason">{recommendation.reason}</p>

                                {recommendation.suggestedActions.length > 0 && (
                                    <div className="suggested-actions">
                                        <h4>Suggested Actions:</h4>
                                        <ul>
                                            {recommendation.suggestedActions.map((action, actionIndex) => (
                                                <li key={actionIndex}>{action}</li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}; 
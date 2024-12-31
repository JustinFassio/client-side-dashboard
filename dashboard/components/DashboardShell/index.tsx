import { createElement, Component } from '@wordpress/element';
import './DashboardShell.css';

interface DashboardShellProps {}

export const DashboardShell: React.FC<DashboardShellProps> = () => {
  return (
    <div className="dashboard-shell">
      <h1>Your Athlete Dashboard</h1>
    </div>
  );
}; 
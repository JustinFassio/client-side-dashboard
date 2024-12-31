import { createRoot } from '@wordpress/element';
import { DashboardShell } from '../../dashboard/components/DashboardShell';
import './styles/main.css';

const container = document.getElementById('dashboard-root');
if (container) {
  const root = createRoot(container);
  root.render(<DashboardShell />);
}
import { createElement } from '@wordpress/element';
import { createRoot } from '@wordpress/element';
import { DashboardShell } from '../../dashboard/components/DashboardShell';
import './styles/main.css';

const root = document.getElementById('dashboard-root');
if (root) {
    createRoot(root).render(<DashboardShell />);
}
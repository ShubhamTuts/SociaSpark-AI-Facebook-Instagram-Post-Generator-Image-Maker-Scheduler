import {
	BarChart3,
	CalendarDays,
	DatabaseZap,
	FilePenLine,
	Lightbulb,
	Link2,
	ListChecks,
	Settings,
	Sparkles,
	Wand2,
} from './icons';

export const routes = [
	{ id: 'welcome', label: 'Setup', icon: ListChecks },
	{ id: 'dashboard', label: 'Dashboard', icon: BarChart3 },
	{ id: 'create', label: 'Create Post', icon: FilePenLine },
	{ id: 'studio', label: 'AI Studio', icon: Wand2 },
	{ id: 'brand', label: 'Brand Intelligence', icon: DatabaseZap },
	{ id: 'calendar', label: 'Calendar', icon: CalendarDays },
	{ id: 'ideas', label: 'Ideas', icon: Lightbulb },
	{ id: 'connections', label: 'Connections', icon: Link2 },
	{ id: 'settings', label: 'Settings', icon: Settings },
	{ id: 'logs', label: 'Activity', icon: Sparkles },
	{ id: 'upgrade', label: 'Roadmap', icon: Sparkles },
];

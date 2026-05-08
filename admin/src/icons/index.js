import { createElement } from '@wordpress/element';

const svg = ( paths ) => ( props ) =>
	createElement(
		'svg',
		{
			viewBox: '0 0 24 24',
			width: 18,
			height: 18,
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 1.8,
			strokeLinecap: 'round',
			strokeLinejoin: 'round',
			'aria-hidden': true,
			...props,
		},
		paths
	);

export const BarChart3 = svg( [
	createElement( 'path', { d: 'M3 3v18h18', key: 'a' } ),
	createElement( 'path', { d: 'M18 17V9', key: 'b' } ),
	createElement( 'path', { d: 'M13 17V5', key: 'c' } ),
	createElement( 'path', { d: 'M8 17v-3', key: 'd' } ),
] );
export const CalendarDays = svg( [
	createElement( 'path', { d: 'M8 2v4M16 2v4M3 10h18', key: 'a' } ),
	createElement( 'rect', {
		x: 3,
		y: 4,
		width: 18,
		height: 18,
		rx: 2,
		key: 'b',
	} ),
] );
export const DatabaseZap = svg( [
	createElement( 'ellipse', { cx: 12, cy: 5, rx: 8, ry: 3, key: 'a' } ),
	createElement( 'path', {
		d: 'M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5',
		key: 'b',
	} ),
	createElement( 'path', { d: 'M4 11v6c0 1.7 3.6 3 8 3h1', key: 'c' } ),
	createElement( 'path', { d: 'm18 13-3 5h4l-3 4', key: 'd' } ),
] );
export const FilePenLine = svg( [
	createElement( 'path', {
		d: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z',
		key: 'a',
	} ),
	createElement( 'path', {
		d: 'M14 2v6h6M12 18h-1l-1-1 5-5 2 2-5 5z',
		key: 'b',
	} ),
] );
export const Lightbulb = svg( [
	createElement( 'path', {
		d: 'M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12c.7.6 1 1.3 1 2h6c0-.7.3-1.4 1-2a7 7 0 0 0-4-12z',
		key: 'a',
	} ),
] );
export const Link2 = svg( [
	createElement( 'path', {
		d: 'M9 17H7a5 5 0 0 1 0-10h2M15 7h2a5 5 0 0 1 0 10h-2M8 12h8',
		key: 'a',
	} ),
] );
export const ListChecks = svg( [
	createElement( 'path', {
		d: 'm3 6 1.5 1.5L8 4M3 12l1.5 1.5L8 10M3 18l1.5 1.5L8 16M11 6h10M11 12h10M11 18h10',
		key: 'a',
	} ),
] );
export const Settings = svg( [
	createElement( 'path', {
		d: 'M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5z',
		key: 'a',
	} ),
	createElement( 'path', {
		d: 'M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9L4.2 7a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3h.1a1.7 1.7 0 0 0 .9-1.5V3a2 2 0 1 1 4 0v.1c0 .7.4 1.3 1 1.5a1.7 1.7 0 0 0 1.9-.3l.1-.1A2 2 0 1 1 19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1c.2.6.8.9 1.5.9h.1a2 2 0 1 1 0 4h-.1c-.7 0-1.3.4-1.5 1z',
		key: 'b',
	} ),
] );
export const Sparkles = svg( [
	createElement( 'path', {
		d: 'M12 3 9.5 9.5 3 12l6.5 2.5L12 21l2.5-6.5L21 12l-6.5-2.5zM5 3v4M3 5h4M19 17v4M17 19h4',
		key: 'a',
	} ),
] );
export const Wand2 = svg( [
	createElement( 'path', { d: 'm15 4 5 5M13 6l5 5M4 20l12-12', key: 'a' } ),
	createElement( 'path', {
		d: 'M5 4v3M3.5 5.5h3M19 16v3M17.5 17.5h3',
		key: 'b',
	} ),
] );
export const Send = svg( [
	createElement( 'path', { d: 'm22 2-7 20-4-9-9-4zM22 2 11 13', key: 'a' } ),
] );
export const ImageIcon = svg( [
	createElement( 'rect', {
		x: 3,
		y: 3,
		width: 18,
		height: 18,
		rx: 2,
		key: 'a',
	} ),
	createElement( 'path', { d: 'm21 15-5-5L5 21M8.5 8.5h.01', key: 'b' } ),
] );
export const AlertTriangle = svg( [
	createElement( 'path', {
		d: 'M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z',
		key: 'a',
	} ),
	createElement( 'path', { d: 'M12 9v4M12 17h.01', key: 'b' } ),
] );
export const CheckCircle = svg( [
	createElement( 'path', { d: 'M22 11.1V12a10 10 0 1 1-5.9-9.1', key: 'a' } ),
	createElement( 'path', { d: 'm22 4-10 10.01-3-3', key: 'b' } ),
] );
export const Clock = svg( [
	createElement( 'circle', { cx: 12, cy: 12, r: 10, key: 'a' } ),
	createElement( 'path', { d: 'M12 6v6l4 2', key: 'b' } ),
] );
export const RefreshCw = svg( [
	createElement( 'path', { d: 'M21 12a9 9 0 0 1-15 6.7L3 16', key: 'a' } ),
	createElement( 'path', {
		d: 'M3 21v-5h5M3 12a9 9 0 0 1 15-6.7L21 8',
		key: 'b',
	} ),
	createElement( 'path', { d: 'M21 3v5h-5', key: 'c' } ),
] );
export const Save = svg( [
	createElement( 'path', {
		d: 'M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z',
		key: 'a',
	} ),
	createElement( 'path', { d: 'M17 21v-8H7v8M7 3v5h8', key: 'b' } ),
] );
export const UploadCloud = svg( [
	createElement( 'path', { d: 'M16 16l-4-4-4 4M12 12v9', key: 'a' } ),
	createElement( 'path', {
		d: 'M20.4 18.1A5 5 0 0 0 18 8.7 7 7 0 1 0 5.4 15',
		key: 'b',
	} ),
] );

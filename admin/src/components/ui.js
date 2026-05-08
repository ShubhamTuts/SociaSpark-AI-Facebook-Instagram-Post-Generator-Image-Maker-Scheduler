import { createElement } from '@wordpress/element';

export const h = createElement;

export function Button( {
	children,
	variant = 'secondary',
	icon: Icon,
	busy = false,
	...props
} ) {
	return h(
		'button',
		{
			className: `ssai-button ssai-button-${ variant }`,
			type: 'button',
			disabled: busy || props.disabled,
			...props,
		},
		Icon ? h( Icon ) : null,
		h( 'span', null, busy ? 'Working...' : children )
	);
}

export function Field( { label, help, children } ) {
	return h(
		'label',
		{ className: 'ssai-field' },
		h( 'span', null, label ),
		children,
		help ? h( 'small', null, help ) : null
	);
}

export function TextInput( props ) {
	return h( 'input', { className: 'ssai-input', ...props } );
}

export function TextArea( props ) {
	return h( 'textarea', {
		className: 'ssai-textarea',
		rows: props.rows || 5,
		...props,
	} );
}

export function Select( { options = [], ...props } ) {
	return h(
		'select',
		{ className: 'ssai-input', ...props },
		options.map( ( option ) =>
			h(
				'option',
				{ value: option.value ?? option, key: option.value ?? option },
				option.label ?? option
			)
		)
	);
}

export function Panel( { children, className = '' } ) {
	return h( 'section', { className: `ssai-panel ${ className }` }, children );
}

export function Badge( { children, tone = 'neutral' } ) {
	return h(
		'span',
		{ className: `ssai-badge ssai-badge-${ tone }` },
		children
	);
}

export function SectionHeader( { title, body, actions } ) {
	return h(
		'div',
		{ className: 'ssai-section-header' },
		h(
			'div',
			null,
			h( 'h2', null, title ),
			body ? h( 'p', null, body ) : null
		),
		actions || null
	);
}

export function EmptyState( { title, body, action } ) {
	return h(
		'div',
		{ className: 'ssai-empty' },
		h( 'strong', null, title ),
		body ? h( 'p', null, body ) : null,
		action || null
	);
}

export function Skeleton() {
	return h( 'div', { className: 'ssai-skeleton', 'aria-hidden': true } );
}

export function Metric( { label, value, tone = 'neutral' } ) {
	return h(
		'section',
		{ className: `ssai-metric ssai-metric-${ tone }` },
		h( 'span', null, label ),
		h( 'strong', null, value )
	);
}

export function HelpText( { children, tone = 'neutral' } ) {
	return h( 'p', { className: `ssai-help ssai-help-${ tone }` }, children );
}

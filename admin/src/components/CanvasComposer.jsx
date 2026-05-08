import { useEffect, useRef, useState } from '@wordpress/element';
import { api } from '../api';
import { Button, Field, h, Select, TextArea, TextInput } from './ui';
import { Save } from '../icons';

const sizes = {
	square: [ 1080, 1080 ],
	portrait: [ 1080, 1350 ],
	story: [ 1080, 1920 ],
};

const palettes = {
	spark: {
		background: '#f7fbff',
		accent: '#1877f2',
		accentTwo: '#e1306c',
		ink: '#182033',
		muted: '#526071',
	},
	evergreen: {
		background: '#f3faf6',
		accent: '#0f766e',
		accentTwo: '#f59e0b',
		ink: '#14211f',
		muted: '#50605c',
	},
	editorial: {
		background: '#fbfaf7',
		accent: '#111827',
		accentTwo: '#ef4444',
		ink: '#171717',
		muted: '#5f6368',
	},
};

export default function CanvasComposer( {
	initialFormat = 'square',
	onSaved,
} ) {
	const canvasRef = useRef();
	const [ state, setState ] = useState( {
		format: initialFormat,
		palette: 'spark',
		headline: 'Make the next post useful',
		subheadline: 'Brand-aware social creative from WordPress',
		cta: 'Start today',
	} );
	const [ busy, setBusy ] = useState( false );

	useEffect( () => {
		setState( ( current ) => ( { ...current, format: initialFormat } ) );
	}, [ initialFormat ] );

	useEffect( () => {
		draw();
	} );

	const update = ( key, value ) => setState( { ...state, [ key ]: value } );

	const draw = () => {
		const [ width, height ] = sizes[ state.format ] || sizes.square;
		const canvas = canvasRef.current;
		if ( ! canvas ) {
			return;
		}
		canvas.width = width;
		canvas.height = height;
		const palette = palettes[ state.palette ] || palettes.spark;
		const ctx = canvas.getContext( '2d' );
		const margin = Math.round( width * 0.085 );
		const headlineSize = Math.round( width * 0.072 );
		const subSize = Math.round( width * 0.034 );

		ctx.clearRect( 0, 0, width, height );
		ctx.fillStyle = palette.background;
		ctx.fillRect( 0, 0, width, height );

		const ribbon = ctx.createLinearGradient(
			width * 0.58,
			0,
			width,
			height
		);
		ribbon.addColorStop( 0, palette.accent );
		ribbon.addColorStop( 1, palette.accentTwo );
		ctx.fillStyle = ribbon;
		ctx.globalAlpha = 0.14;
		roundRect(
			ctx,
			width * 0.58,
			margin,
			width * 0.34,
			height - margin * 2,
			34
		);
		ctx.fill();
		ctx.globalAlpha = 1;

		ctx.fillStyle = palette.accent;
		roundRect( ctx, margin, margin, 82, 82, 26 );
		ctx.fill();
		ctx.fillStyle = '#fff';
		ctx.font = `800 ${ Math.round(
			width * 0.044
		) }px system-ui, sans-serif`;
		ctx.fillText( 'S', margin + 28, margin + 55 );

		ctx.fillStyle = palette.ink;
		ctx.font = `780 ${ headlineSize }px system-ui, sans-serif`;
		wrap(
			ctx,
			state.headline,
			margin,
			height * 0.34,
			width * 0.68,
			headlineSize * 1.08,
			5
		);

		ctx.fillStyle = palette.muted;
		ctx.font = `430 ${ subSize }px system-ui, sans-serif`;
		wrap(
			ctx,
			state.subheadline,
			margin,
			height * 0.61,
			width * 0.62,
			subSize * 1.32,
			3
		);

		const ctaWidth = Math.max(
			250,
			ctx.measureText( state.cta ).width + 92
		);
		ctx.fillStyle = palette.ink;
		roundRect( ctx, margin, height - margin - 96, ctaWidth, 84, 42 );
		ctx.fill();
		ctx.fillStyle = '#fff';
		ctx.font = `760 ${ Math.round(
			width * 0.03
		) }px system-ui, sans-serif`;
		ctx.fillText( state.cta, margin + 42, height - margin - 43 );
	};

	const save = async () => {
		setBusy( true );
		try {
			draw();
			const dataUrl = canvasRef.current.toDataURL( 'image/png' );
			const saved = await api.saveGenerated( {
				data_url: dataUrl,
				title: state.headline,
			} );
			onSaved?.( saved );
		} finally {
			setBusy( false );
		}
	};

	return h(
		'div',
		{ className: 'ssai-composer' },
		h( 'h3', null, 'Native image composer' ),
		h(
			'div',
			{ className: 'ssai-form-grid' },
			h(
				Field,
				{ label: 'Format' },
				h( Select, {
					value: state.format,
					onChange: ( event ) =>
						update( 'format', event.target.value ),
					options: [ 'square', 'portrait', 'story' ],
				} )
			),
			h(
				Field,
				{ label: 'Theme' },
				h( Select, {
					value: state.palette,
					onChange: ( event ) =>
						update( 'palette', event.target.value ),
					options: [
						{ value: 'spark', label: 'SociaSpark' },
						{ value: 'evergreen', label: 'Evergreen' },
						{ value: 'editorial', label: 'Editorial' },
					],
				} )
			)
		),
		h(
			Field,
			{ label: 'Headline' },
			h( TextArea, {
				rows: 3,
				value: state.headline,
				onChange: ( event ) => update( 'headline', event.target.value ),
			} )
		),
		h(
			Field,
			{ label: 'Subheadline' },
			h( TextInput, {
				value: state.subheadline,
				onChange: ( event ) =>
					update( 'subheadline', event.target.value ),
			} )
		),
		h(
			Field,
			{ label: 'CTA' },
			h( TextInput, {
				value: state.cta,
				onChange: ( event ) => update( 'cta', event.target.value ),
			} )
		),
		h( 'canvas', {
			ref: canvasRef,
			className: `ssai-canvas-preview ssai-canvas-${ state.format }`,
		} ),
		h(
			Button,
			{ variant: 'primary', icon: Save, busy, onClick: save },
			'Save Graphic'
		)
	);
}

function wrap( ctx, text, x, y, maxWidth, lineHeight, maxLines ) {
	const words = String( text ).split( ' ' );
	let line = '';
	let lineCount = 0;
	words.forEach( ( word ) => {
		if ( lineCount >= maxLines ) {
			return;
		}
		const next = `${ line } ${ word }`.trim();
		if ( ctx.measureText( next ).width > maxWidth && line ) {
			ctx.fillText( line, x, y );
			line = word;
			y += lineHeight;
			lineCount += 1;
		} else {
			line = next;
		}
	} );
	if ( lineCount < maxLines ) {
		ctx.fillText( line, x, y );
	}
}

function roundRect( ctx, x, y, width, height, radius ) {
	ctx.beginPath();
	ctx.moveTo( x + radius, y );
	ctx.arcTo( x + width, y, x + width, y + height, radius );
	ctx.arcTo( x + width, y + height, x, y + height, radius );
	ctx.arcTo( x, y + height, x, y, radius );
	ctx.arcTo( x, y, x + width, y, radius );
	ctx.closePath();
}

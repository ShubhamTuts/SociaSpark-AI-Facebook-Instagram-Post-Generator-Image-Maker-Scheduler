import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { api } from './api';
import CanvasComposer from './components/CanvasComposer';
import {
	Badge,
	Button,
	EmptyState,
	Field,
	HelpText,
	Metric,
	Panel,
	SectionHeader,
	Select,
	Skeleton,
	TextArea,
	TextInput,
	h,
} from './components/ui';
import {
	AlertTriangle,
	CalendarDays,
	CheckCircle,
	Clock,
	ImageIcon,
	RefreshCw,
	Save,
	Send,
	Sparkles,
	UploadCloud,
} from './icons';
import {
	contentTypes,
	defaultProviderOptions,
	imageFormats,
	toneOptions,
} from './store';
import { routes } from './routes';
import logoMark from './assets/sociaspark-mark.svg';

const initialComposer = {
	source: 'manual',
	provider: 'openai',
	text_model: '',
	image_model: '',
	tone: toneOptions[ 0 ],
	content_type: 'standard post',
	platform: 'facebook_instagram',
	selectedVariation: 0,
	schedule_mode: 'same',
	format: 'square',
};

export default function App() {
	const [ route, setRouteId ] = useState( 'dashboard' );
	const [ routeState, setRouteState ] = useState( null );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		if ( ! notice ) {
			return undefined;
		}
		const timeout = window.setTimeout( () => setNotice( null ), 5200 );
		return () => window.clearTimeout( timeout );
	}, [ notice ] );

	const run = async ( work, success = 'Done.' ) => {
		try {
			const result = await work();
			setNotice( { tone: 'success', text: success } );
			return result;
		} catch ( error ) {
			setNotice( {
				tone: 'error',
				text: error?.message || 'Request failed.',
				detail: error?.data?.remediation || '',
			} );
			throw error;
		}
	};

	const setRoute = ( nextRoute, nextState = null ) => {
		setRouteId( nextRoute );
		setRouteState( nextState );
	};

	return h(
		'div',
		{ className: 'ssai-app' },
		h( Sidebar, { route, setRoute } ),
		h(
			'main',
			{ className: 'ssai-main' },
			notice && h( Toast, { notice, onClose: () => setNotice( null ) } ),
			route === 'welcome' && h( Welcome, { run, setRoute } ),
			route === 'dashboard' && h( Dashboard, { setRoute } ),
			route === 'create' &&
				h( CreatePost, { run, setRoute, routeState } ),
			route === 'studio' && h( AIStudio, { run } ),
			route === 'brand' && h( BrandIntelligence, { run } ),
			route === 'calendar' && h( CalendarView, { run } ),
			route === 'ideas' && h( Ideas, { run, setRoute } ),
			route === 'connections' && h( Connections, { run } ),
			route === 'settings' && h( SettingsPage, { run } ),
			route === 'logs' && h( Logs ),
			route === 'upgrade' && h( Roadmap )
		)
	);
}

function Sidebar( { route, setRoute } ) {
	return h(
		'aside',
		{ className: 'ssai-sidebar' },
		h(
			'button',
			{
				className: 'ssai-brand-lockup',
				type: 'button',
				onClick: () => setRoute( 'dashboard' ),
			},
			h( 'img', { src: logoMark, alt: '', 'aria-hidden': true } ),
			h( 'span', null, 'SociaSpark AI' ),
			h( Badge, { tone: 'brand' }, 'Free Core' )
		),
		h(
			'nav',
			{ 'aria-label': 'SociaSpark AI' },
			routes.map( ( item ) =>
				h(
					'button',
					{
						key: item.id,
						className: route === item.id ? 'is-active' : '',
						type: 'button',
						onClick: () => setRoute( item.id ),
					},
					h( item.icon ),
					h( 'span', null, item.label )
				)
			)
		),
		h(
			'div',
			{ className: 'ssai-sidebar-note' },
			h( Sparkles ),
			h(
				'span',
				null,
				'Brand-aware creation and Meta scheduling stay inside WordPress.'
			)
		)
	);
}

function Toast( { notice, onClose } ) {
	return h(
		'div',
		{ className: `ssai-toast ssai-toast-${ notice.tone }`, role: 'status' },
		notice.tone === 'error' ? h( AlertTriangle ) : h( CheckCircle ),
		h(
			'div',
			null,
			h( 'strong', null, notice.text ),
			notice.detail ? h( 'p', null, notice.detail ) : null
		),
		h(
			'button',
			{
				type: 'button',
				onClick: onClose,
				'aria-label': 'Dismiss notice',
			},
			'x'
		)
	);
}

function Welcome( { run, setRoute } ) {
	const { data, loading, reload } = useLoader( api.dashboard );
	const providerReady = Boolean(
		data?.provider?.openai ||
			data?.provider?.gemini ||
			data?.provider?.claude
	);
	const connectionReady = Boolean( data?.counts?.connections );
	const brandReady = Boolean( data?.brand?.profile );
	const items = [
		{
			id: 'settings',
			label: 'AI provider',
			body: 'Add at least one text provider and choose default models.',
			ready: providerReady,
		},
		{
			id: 'connections',
			label: 'Meta publishing',
			body: 'Connect a Facebook Page or Instagram professional account.',
			ready: connectionReady,
		},
		{
			id: 'brand',
			label: 'Brand Intelligence',
			body: 'Scan WordPress content or upload approved examples.',
			ready: brandReady,
		},
		{
			id: 'create',
			label: 'First scheduled post',
			body: 'Generate variations, attach media, and queue a job.',
			ready: Boolean(
				data?.counts?.scheduled || data?.counts?.published
			),
		},
	];

	return h(
		'div',
		null,
		h( PageHeader, {
			eyebrow: 'Setup',
			title: 'Prepare SociaSpark AI',
			body: 'Finish the pieces that make the free Facebook and Instagram workflow reliable.',
			actions: h(
				Button,
				{ icon: RefreshCw, onClick: reload },
				loading ? 'Checking' : 'Refresh'
			),
		} ),
		loading
			? h( SkeletonGrid )
			: h(
					'div',
					{ className: 'ssai-setup-grid' },
					items.map( ( item, index ) =>
						h(
							'button',
							{
								key: item.id,
								className: item.ready
									? 'is-ready'
									: 'needs-work',
								type: 'button',
								onClick: () => setRoute( item.id ),
							},
							h( 'span', null, `0${ index + 1 }` ),
							h( 'strong', null, item.label ),
							h( 'p', null, item.body ),
							h(
								Badge,
								{ tone: item.ready ? 'success' : 'warning' },
								item.ready ? 'Ready' : 'Needs setup'
							)
						)
					)
			  ),
		h(
			'div',
			{ className: 'ssai-action-row' },
			h(
				Button,
				{ variant: 'primary', onClick: () => setRoute( 'create' ) },
				'Create Post'
			),
			h(
				Button,
				{
					onClick: () =>
						run(
							() =>
								api.testProvider( {
									provider: 'openai',
									mode: 'text',
								} ),
							'OpenAI text check complete.'
						),
				},
				'Quick Provider Check'
			)
		)
	);
}

function Dashboard( { setRoute } ) {
	const { data, loading, reload } = useLoader( api.dashboard );
	const counts = data?.counts || {};
	const brand = data?.brand?.profile || {};
	const providerReady = Boolean(
		data?.provider?.openai ||
			data?.provider?.gemini ||
			data?.provider?.claude
	);
	const connectionReady = Boolean( counts.connections );
	const brandReady = Boolean( brand?.profile || brand?.voice );

	return h(
		'div',
		null,
		h( PageHeader, {
			eyebrow: 'Dashboard',
			title: 'Publishing control room',
			body: 'Create, review, schedule, and monitor Facebook and Instagram content from one WordPress admin surface.',
			actions: h(
				Button,
				{ icon: RefreshCw, onClick: reload },
				'Refresh'
			),
		} ),
		h(
			'div',
			{ className: 'ssai-readiness' },
			h( ReadinessButton, {
				label: 'AI provider',
				ready: providerReady,
				onClick: () => setRoute( 'settings' ),
			} ),
			h( ReadinessButton, {
				label: 'Meta connection',
				ready: connectionReady,
				onClick: () => setRoute( 'connections' ),
			} ),
			h( ReadinessButton, {
				label: 'Brand profile',
				ready: brandReady,
				onClick: () => setRoute( 'brand' ),
			} )
		),
		h(
			'div',
			{ className: 'ssai-metrics' },
			h( Metric, { label: 'Drafts', value: counts.drafts || 0 } ),
			h( Metric, { label: 'Scheduled', value: counts.scheduled || 0 } ),
			h( Metric, { label: 'Published', value: counts.published || 0 } ),
			h( Metric, {
				label: 'Failed jobs',
				value: counts.failed || 0,
				tone: counts.failed ? 'error' : 'neutral',
			} ),
			h( Metric, { label: 'Connected', value: counts.connections || 0 } )
		),
		h(
			'div',
			{ className: 'ssai-action-row' },
			h(
				Button,
				{
					variant: 'primary',
					icon: Send,
					onClick: () => setRoute( 'create' ),
				},
				'Create Post'
			),
			h(
				Button,
				{ icon: Sparkles, onClick: () => setRoute( 'studio' ) },
				'Open AI Studio'
			),
			h(
				Button,
				{ icon: CalendarDays, onClick: () => setRoute( 'calendar' ) },
				'Open Calendar'
			)
		),
		loading
			? h( SkeletonGrid )
			: h(
					'div',
					{ className: 'ssai-grid-two' },
					h(
						Panel,
						null,
						h( SectionHeader, {
							title: 'Next actions',
							body: 'Setup and publishing checks that need attention.',
						} ),
						h( ActionList, {
							items: data?.next_actions || [],
							setRoute,
						} )
					),
					h(
						Panel,
						null,
						h( SectionHeader, {
							title: 'Upcoming jobs',
							body: 'The scheduler processes due jobs every five minutes.',
						} ),
						h( JobList, {
							jobs: data?.upcoming_jobs || [],
							empty: 'No scheduled jobs yet.',
						} )
					),
					h(
						Panel,
						null,
						h( SectionHeader, {
							title: 'Recent posts',
							body: 'Drafts and published social posts created by SociaSpark.',
						} ),
						h( PostList, {
							posts: data?.recent_posts || [],
							onOpen: ( postId ) =>
								setRoute( 'create', { postId } ),
						} )
					),
					h(
						Panel,
						null,
						h( SectionHeader, {
							title: 'Recent activity',
							body: 'Safe logs for generation, publishing, and scheduler events.',
						} ),
						h( ActivityList, { items: data?.activity || [] } )
					)
			  )
	);
}

function ReadinessButton( { label, ready, onClick } ) {
	return h(
		'button',
		{
			className: ready ? 'is-ready' : 'needs-work',
			type: 'button',
			onClick,
		},
		h( 'strong', null, label ),
		h( 'span', null, ready ? 'Ready' : 'Needs setup' )
	);
}

function ActionList( { items, setRoute } ) {
	if ( ! items.length ) {
		return h( EmptyState, {
			title: 'Ready to publish',
			body: 'Your core setup is complete. Create the next post or review the queue.',
		} );
	}

	return h(
		'div',
		{ className: 'ssai-list' },
		items.map( ( item ) => {
			const target = item.id === 'create' ? 'create' : item.id;
			return h(
				'button',
				{
					key: `${ item.id }-${ item.label }`,
					className: 'ssai-action-card',
					type: 'button',
					onClick: () => setRoute( target ),
				},
				h( 'strong', null, item.label ),
				h( 'span', null, `Open ${ target }` )
			);
		} )
	);
}

function CreatePost( { run, setRoute, routeState } ) {
	const [ state, setState ] = useState( initialComposer );
	const [ models, setModels ] = useState( null );
	const [ wpResults, setWpResults ] = useState( [] );
	const [ ideas, setIdeas ] = useState( [] );
	const [ connections, setConnections ] = useState( [] );
	const [ variations, setVariations ] = useState( [] );
	const [ postId, setPostId ] = useState( null );
	const [ busy, setBusy ] = useState( '' );
	const [ mediaNotice, setMediaNotice ] = useState( '' );

	useEffect( () => {
		api.models().then( setModels );
		api.connections().then( setConnections );
		api.ideas().then( setIdeas );
	}, [] );

	useEffect( () => {
		if ( models && ! state.text_model ) {
			setState( ( current ) => ( {
				...current,
				text_model: models.defaults?.text_model || '',
				image_model: models.defaults?.image_model || '',
			} ) );
		}
	}, [ models, state.text_model ] );

	useEffect( () => {
		const draftId = Number( routeState?.postId || 0 );
		if ( ! draftId || draftId === postId ) {
			return undefined;
		}

		let active = true;
		setBusy( 'load-draft' );
		setMediaNotice( '' );

		api.getPost( draftId )
			.then( ( post ) => {
				if ( ! active || ! post ) {
					return;
				}

				setPostId( post.id );
				setVariations( [] );
				setState( ( current ) => ( {
					...current,
					source:
						post.source_type === 'idea_bank'
							? 'manual'
							: post.source_type || 'manual',
					title: post.title || '',
					idea:
						post.content_long ||
						post.content_facebook ||
						post.content_instagram ||
						'',
					wp_post_id: post.wp_post_id || '',
					content_facebook: post.content_facebook || '',
					content_instagram: post.content_instagram || '',
					media_id: post.media_id || '',
					media_url: post.media_url || '',
					scheduled_at: post.scheduled_at
						? post.scheduled_at.replace( ' ', 'T' ).slice( 0, 16 )
						: '',
				} ) );
			} )
			.catch( ( error ) => {
				if ( active ) {
					setMediaNotice(
						error?.message || 'Could not load the selected draft.'
					);
				}
			} )
			.finally( () => {
				if ( active ) {
					setBusy( '' );
				}
			} );

		return () => {
			active = false;
		};
	}, [ routeState, postId ] );

	const selected = variations[ state.selectedVariation ] || {};
	const activeFacebook =
		state.content_facebook ??
		selected.facebook_caption ??
		selected.caption ??
		'';
	const activeInstagram =
		state.content_instagram ??
		selected.instagram_caption ??
		selected.caption ??
		'';
	const connectedConnections = connections.filter(
		( connection ) => connection.status === 'connected'
	);
	const selectedAccounts = connectedConnections.filter(
		( connection ) => state[ accountKey( connection ) ]
	);
	const textModels = getTextModelOptions( models, state.provider );
	const imageModels = getImageModelOptions( models );

	const update = ( key, value ) => setState( { ...state, [ key ]: value } );

	const searchWp = async () => {
		setBusy( 'search' );
		try {
			const rows = await run(
				() => api.searchWp( state.wpQuery || '' ),
				'WordPress content loaded.'
			);
			setWpResults( rows );
		} finally {
			setBusy( '' );
		}
	};

	const fillAudience = async () => {
		setBusy( 'audience' );
		try {
			const output = await run(
				() =>
					api.generateCaption( {
						provider: state.provider,
						text_model: state.text_model,
						content_type: 'audience profile',
						idea: `Suggest a concise target audience, pain point, desired result, and CTA for this business idea: ${
							state.idea || state.title || 'our WordPress site'
						}`,
						audience: state.audience || '',
						tone: state.tone,
						cta: state.cta || '',
					} ),
				'Audience suggestions generated.'
			);
			const first = normalizeVariations( output )[ 0 ] || {};
			setState( {
				...state,
				audience: first.audience || first.hook || state.audience || '',
				pain_point: first.angle || state.pain_point || '',
				desired_result:
					first.platform_notes || state.desired_result || '',
				cta: first.cta || state.cta || '',
			} );
		} finally {
			setBusy( '' );
		}
	};

	const generate = async () => {
		setBusy( 'generate' );
		try {
			let output;
			if ( state.source === 'wordpress' && state.wp_post_id ) {
				output = await run(
					() =>
						api.repurpose( {
							wp_post_id: state.wp_post_id,
							provider: state.provider,
							text_model: state.text_model,
						} ),
					'WordPress content repurposed.'
				);
			} else {
				output = await run(
					() => api.generateCaption( state ),
					'Caption variations generated.'
				);
			}
			const rows = normalizeVariations( output );
			setVariations( rows );
			setState( {
				...state,
				selectedVariation: 0,
				content_facebook:
					rows[ 0 ]?.facebook_caption || rows[ 0 ]?.caption || '',
				content_instagram:
					rows[ 0 ]?.instagram_caption || rows[ 0 ]?.caption || '',
				image_prompt: rows[ 0 ]?.image_prompt || '',
			} );
		} finally {
			setBusy( '' );
		}
	};

	const generateImage = async () => {
		setBusy( 'image' );
		try {
			const media = await run(
				() =>
					api.generateImage( {
						idea:
							state.image_prompt ||
							selected.image_prompt ||
							state.idea ||
							activeFacebook,
						title:
							state.title || selected.title || 'SociaSpark image',
						format: state.format || 'square',
						image_model: state.image_model,
					} ),
				'AI image saved to Media Library.'
			);
			setState( { ...state, media_id: media.id, media_url: media.url } );
		} finally {
			setBusy( '' );
		}
	};

	const saveLeftovers = async () => {
		const leftovers = variations.filter(
			( variation, index ) => index !== state.selectedVariation
		);
		await Promise.all(
			leftovers.map( ( variation ) =>
				api.createIdea( {
					title:
						variation.title ||
						variation.hook ||
						'AI leftover variation',
					idea_text: variation.caption || JSON.stringify( variation ),
					source: 'ai_leftover',
					tags: 'ai,leftover',
				} )
			)
		);
		setRoute( 'ideas' );
	};

	const draftPayload = () => ( {
		title:
			state.title ||
			selected.title ||
			selected.hook ||
			state.idea ||
			'Untitled social post',
		source_type: state.source,
		wp_post_id: state.wp_post_id || 0,
		content_long: activeFacebook || activeInstagram || state.idea || '',
		content_facebook: activeFacebook,
		content_instagram: activeInstagram,
		media_id: state.media_id || 0,
		media_url: state.media_url || '',
		status: 'draft',
	} );

	const ensureDraft = async () => {
		if ( postId ) {
			await run(
				() => api.updatePost( postId, draftPayload() ),
				'Draft updated.'
			);
			return postId;
		}
		const created = await run(
			() => api.createPost( draftPayload() ),
			'Draft saved.'
		);
		setPostId( created.id );
		return created.id;
	};

	const queue = async ( now ) => {
		const id = await ensureDraft();
		if ( ! selectedAccounts.length ) {
			throw new Error(
				'Choose at least one connected Facebook or Instagram account.'
			);
		}
		const jobs = selectedAccounts.map( ( connection ) => {
			const platformTime =
				state.schedule_mode === 'split'
					? state[
							`scheduled_${ connection.platform }_${ connection.account_id }`
					  ]
					: state.scheduled_at;
			return {
				platform: connection.platform,
				platform_account_id: connection.account_id,
				scheduled_at: platformTime,
			};
		} );
		await run(
			() =>
				now
					? api.publishNow( { post_id: id, jobs } )
					: api.schedule( { post_id: id, jobs } ),
			now ? 'Publish job queued.' : 'Post scheduled.'
		);
	};

	return h(
		'div',
		null,
		h( PageHeader, {
			eyebrow: 'Create Post',
			title: 'One idea, platform-ready posts',
			body: 'Choose a source, generate variations, save useful leftovers, attach media, and publish or schedule.',
		} ),
		h(
			'div',
			{ className: 'ssai-flow' },
			h(
				Panel,
				{ className: 'ssai-flow-panel' },
				h( StepHead, { step: '1', title: 'Source' } ),
				h(
					Field,
					{ label: 'Content source' },
					h( Select, {
						value: state.source,
						onChange: ( event ) =>
							update( 'source', event.target.value ),
						options: [
							{ value: 'manual', label: 'Manual idea' },
							{
								value: 'wordpress',
								label: 'WordPress post/page/product',
							},
							{ value: 'idea_bank', label: 'Idea Bank item' },
						],
					} )
				),
				state.source === 'manual' &&
					h(
						Field,
						{ label: 'Main idea' },
						h( TextArea, {
							value: state.idea || '',
							onChange: ( event ) =>
								update( 'idea', event.target.value ),
							placeholder:
								'Example: Turn our latest service into a helpful Instagram and Facebook post.',
						} )
					),
				state.source === 'wordpress' &&
					h(
						'div',
						null,
						h(
							'div',
							{ className: 'ssai-inline' },
							h( TextInput, {
								value: state.wpQuery || '',
								onChange: ( event ) =>
									update( 'wpQuery', event.target.value ),
								placeholder: 'Search posts, pages, products',
							} ),
							h(
								Button,
								{ busy: busy === 'search', onClick: searchWp },
								'Search'
							)
						),
						h(
							'div',
							{ className: 'ssai-picker' },
							wpResults.map( ( item ) =>
								h(
									'button',
									{
										key: `${ item.type }-${ item.id }`,
										className:
											state.wp_post_id === item.id
												? 'is-selected'
												: '',
										type: 'button',
										onClick: () =>
											setState( {
												...state,
												wp_post_id: item.id,
												title: item.title,
												idea: item.excerpt,
											} ),
									},
									h( 'strong', null, item.title ),
									h(
										'span',
										null,
										`${ item.type } #${ item.id }`
									),
									h( 'p', null, item.excerpt )
								)
							)
						)
					),
				state.source === 'idea_bank' &&
					h(
						Field,
						{ label: 'Idea Bank item' },
						h( Select, {
							value: state.idea_id || '',
							onChange: ( event ) => {
								const idea = ideas.find(
									( row ) =>
										String( row.id ) === event.target.value
								);
								setState( {
									...state,
									idea_id: event.target.value,
									idea: idea?.idea_text || '',
									title: idea?.title || '',
								} );
							},
							options: [
								{ value: '', label: 'Choose an idea' },
							].concat(
								ideas.map( ( idea ) => ( {
									value: String( idea.id ),
									label: idea.title || `Idea #${ idea.id }`,
								} ) )
							),
						} )
					)
			),
			h(
				Panel,
				{ className: 'ssai-flow-panel' },
				h( StepHead, { step: '2', title: 'AI draft' } ),
				h(
					'div',
					{ className: 'ssai-form-grid' },
					h(
						Field,
						{ label: 'Provider' },
						h( Select, {
							value: state.provider,
							onChange: ( event ) =>
								setState( {
									...state,
									provider: event.target.value,
									text_model: getDefaultTextModel(
										models,
										event.target.value
									),
								} ),
							options: defaultProviderOptions,
						} )
					),
					h(
						Field,
						{ label: 'Text model' },
						h( Select, {
							value: state.text_model,
							onChange: ( event ) =>
								update( 'text_model', event.target.value ),
							options: textModels,
						} )
					),
					h(
						Field,
						{ label: 'Tone' },
						h( Select, {
							value: state.tone,
							onChange: ( event ) =>
								update( 'tone', event.target.value ),
							options: toneOptions,
						} )
					),
					h(
						Field,
						{ label: 'Content type' },
						h( Select, {
							value: state.content_type,
							onChange: ( event ) =>
								update( 'content_type', event.target.value ),
							options: contentTypes,
						} )
					)
				),
				h(
					'div',
					{ className: 'ssai-form-grid' },
					h(
						Field,
						{ label: 'Audience' },
						h( TextInput, {
							value: state.audience || '',
							onChange: ( event ) =>
								update( 'audience', event.target.value ),
							placeholder:
								'Small business owners, local customers, course buyers...',
						} )
					),
					h(
						Field,
						{ label: 'Pain point' },
						h( TextInput, {
							value: state.pain_point || '',
							onChange: ( event ) =>
								update( 'pain_point', event.target.value ),
						} )
					),
					h(
						Field,
						{ label: 'Desired result' },
						h( TextInput, {
							value: state.desired_result || '',
							onChange: ( event ) =>
								update( 'desired_result', event.target.value ),
						} )
					),
					h(
						Field,
						{ label: 'CTA' },
						h( TextInput, {
							value: state.cta || '',
							onChange: ( event ) =>
								update( 'cta', event.target.value ),
							placeholder:
								'Book a call, read the post, shop now...',
						} )
					)
				),
				h(
					'div',
					{ className: 'ssai-action-row' },
					h(
						Button,
						{
							icon: Sparkles,
							busy: busy === 'audience',
							onClick: fillAudience,
						},
						'AI Assist'
					),
					h(
						Button,
						{
							variant: 'primary',
							icon: Send,
							busy: busy === 'generate',
							onClick: generate,
						},
						'Generate 3 Variations'
					)
				),
				h( VariationPicker, {
					variations,
					selected: state.selectedVariation,
					onSelect: ( index ) =>
						setState( {
							...state,
							selectedVariation: index,
							content_facebook:
								variations[ index ]?.facebook_caption ||
								variations[ index ]?.caption ||
								'',
							content_instagram:
								variations[ index ]?.instagram_caption ||
								variations[ index ]?.caption ||
								'',
							image_prompt:
								variations[ index ]?.image_prompt || '',
						} ),
				} ),
				variations.length > 1 &&
					h(
						Button,
						{ icon: Save, onClick: saveLeftovers },
						'Save Unused Variations to Ideas'
					)
			),
			h(
				Panel,
				{ className: 'ssai-flow-panel' },
				h( StepHead, { step: '3', title: 'Media' } ),
				h(
					'div',
					{ className: 'ssai-form-grid' },
					h(
						Field,
						{ label: 'Image model' },
						h( Select, {
							value: state.image_model,
							onChange: ( event ) =>
								update( 'image_model', event.target.value ),
							options: imageModels,
						} )
					),
					h(
						Field,
						{ label: 'Image format' },
						h( Select, {
							value: state.format,
							onChange: ( event ) =>
								update( 'format', event.target.value ),
							options: imageFormats,
						} )
					)
				),
				h(
					Field,
					{ label: 'Image prompt' },
					h( TextArea, {
						rows: 4,
						value:
							state.image_prompt || selected.image_prompt || '',
						onChange: ( event ) =>
							update( 'image_prompt', event.target.value ),
					} )
				),
				h(
					'div',
					{ className: 'ssai-action-row' },
					h(
						Button,
						{
							icon: ImageIcon,
							busy: busy === 'image',
							onClick: generateImage,
						},
						'Generate AI Image'
					),
					h( MediaPickerButton, {
						onError: setMediaNotice,
						onPick: ( media ) => {
							setMediaNotice( '' );
							setState( {
								...state,
								media_id: media.id,
								media_url: media.url,
							} );
						},
					} ),
					state.media_id
						? h(
								Badge,
								{ tone: 'success' },
								`Media #${ state.media_id }`
						  )
						: null
				),
				mediaNotice
					? h( HelpText, { tone: 'error' }, mediaNotice )
					: null,
				h(
					Field,
					{ label: 'Public media URL' },
					h( TextInput, {
						value: state.media_url || '',
						onChange: ( event ) =>
							update( 'media_url', event.target.value ),
						placeholder: 'https://example.com/image.jpg',
					} )
				),
				h( CanvasComposer, {
					initialFormat: state.format,
					onSaved: ( media ) =>
						setState( {
							...state,
							media_id: media.id,
							media_url: media.url,
						} ),
				} )
			),
			h(
				Panel,
				{ className: 'ssai-flow-panel' },
				h( StepHead, { step: '4', title: 'Review and schedule' } ),
				h(
					'div',
					{ className: 'ssai-preview-grid' },
					h( PlatformPreview, {
						name: 'Facebook',
						value: activeFacebook,
						onChange: ( value ) =>
							update( 'content_facebook', value ),
					} ),
					h( PlatformPreview, {
						name: 'Instagram',
						value: activeInstagram,
						onChange: ( value ) =>
							update( 'content_instagram', value ),
					} )
				),
				h( AccountPicker, {
					connections: connectedConnections,
					state,
					setState,
				} ),
				h(
					'div',
					{
						className: 'ssai-segmented',
						role: 'group',
						'aria-label': 'Schedule mode',
					},
					[ 'same', 'split' ].map( ( mode ) =>
						h(
							'button',
							{
								key: mode,
								className:
									state.schedule_mode === mode
										? 'is-active'
										: '',
								type: 'button',
								onClick: () => update( 'schedule_mode', mode ),
							},
							mode === 'same' ? 'Same time' : 'Split times'
						)
					)
				),
				state.schedule_mode === 'same'
					? h(
							Field,
							{ label: 'Schedule time' },
							h( TextInput, {
								type: 'datetime-local',
								value: state.scheduled_at || '',
								onChange: ( event ) =>
									update(
										'scheduled_at',
										event.target.value
									),
							} )
					  )
					: selectedAccounts.map( ( connection ) =>
							h(
								Field,
								{
									key: accountKey( connection ),
									label: `${ connection.account_label } time`,
								},
								h( TextInput, {
									type: 'datetime-local',
									value:
										state[
											`scheduled_${ connection.platform }_${ connection.account_id }`
										] || '',
									onChange: ( event ) =>
										update(
											`scheduled_${ connection.platform }_${ connection.account_id }`,
											event.target.value
										),
								} )
							)
					  ),
				h(
					'div',
					{ className: 'ssai-action-row' },
					h(
						Button,
						{ icon: Save, onClick: ensureDraft },
						postId ? `Update Draft #${ postId }` : 'Save Draft'
					),
					h(
						Button,
						{ icon: Clock, onClick: () => queue( false ) },
						'Schedule'
					),
					h(
						Button,
						{
							variant: 'primary',
							icon: Send,
							onClick: () => queue( true ),
						},
						'Publish Now'
					)
				)
			)
		)
	);
}

function MediaPickerButton( { onPick, onError } ) {
	const openPicker = () => {
		if ( ! window.wp?.media ) {
			onError?.(
				'The WordPress Media Library is not available on this page.'
			);
			return;
		}
		const frame = window.wp.media( {
			title: 'Choose SociaSpark media',
			button: { text: 'Use this image' },
			multiple: false,
			library: { type: 'image' },
		} );
		frame.on( 'select', () => {
			const attachment = frame
				.state()
				.get( 'selection' )
				.first()
				.toJSON();
			onPick( { id: attachment.id, url: attachment.url } );
		} );
		frame.open();
	};

	return h(
		Button,
		{ icon: ImageIcon, onClick: openPicker },
		'Choose Media'
	);
}

function VariationPicker( { variations, selected, onSelect } ) {
	if ( ! variations.length ) {
		return h( EmptyState, {
			title: 'No variations yet',
			body: 'Generate three options, then select the strongest one for review.',
		} );
	}

	return h(
		'div',
		{ className: 'ssai-variations' },
		variations.map( ( item, index ) =>
			h(
				'button',
				{
					key: `${
						item.title || item.hook || 'variation'
					}-${ index }`,
					className: selected === index ? 'is-selected' : '',
					type: 'button',
					onClick: () => onSelect( index ),
				},
				h( Badge, null, `Option ${ index + 1 }` ),
				h(
					'strong',
					null,
					item.title || item.hook || `Variation ${ index + 1 }`
				),
				h( 'span', null, item.angle || item.platform_notes || '' ),
				h( 'p', null, captionText( item ) )
			)
		)
	);
}

function AccountPicker( { connections, state, setState } ) {
	if ( ! connections.length ) {
		return h( EmptyState, {
			title: 'No Meta accounts connected',
			body: 'Connect Facebook or Instagram before scheduling or publishing.',
		} );
	}

	return h(
		'div',
		null,
		h( SectionHeader, {
			title: 'Publish targets',
			body: 'Choose one or more connected accounts.',
		} ),
		h(
			'div',
			{ className: 'ssai-account-grid' },
			connections.map( ( connection ) => {
				const key = accountKey( connection );
				return h(
					'label',
					{ key, className: 'ssai-account-option' },
					h( 'input', {
						type: 'checkbox',
						checked: Boolean( state[ key ] ),
						onChange: ( event ) =>
							setState( {
								...state,
								[ key ]: event.target.checked,
							} ),
					} ),
					h(
						'span',
						null,
						h( 'strong', null, connection.account_label ),
						h(
							'small',
							null,
							`${ connection.platform } - ${ connection.account_id }`
						)
					),
					h(
						Badge,
						{
							tone:
								connection.status === 'connected'
									? 'success'
									: 'warning',
						},
						connection.status
					)
				);
			} )
		)
	);
}

function PlatformPreview( { name, value, onChange } ) {
	const count = ( value || '' ).length;

	return h(
		'div',
		{ className: 'ssai-platform-preview' },
		h(
			'div',
			null,
			h( 'strong', null, name ),
			h( Badge, null, `${ count } chars` )
		),
		h( TextArea, {
			rows: 8,
			value: value || '',
			onChange: ( event ) => onChange( event.target.value ),
		} )
	);
}

function AIStudio( { run } ) {
	const [ models, setModels ] = useState( null );
	const [ form, setForm ] = useState( {
		provider: 'openai',
		format: 'square',
	} );
	const [ output, setOutput ] = useState( null );
	const textModels = getTextModelOptions( models, form.provider );
	const imageModels = getImageModelOptions( models );

	useEffect( () => {
		api.models().then( ( data ) => {
			setModels( data );
			setForm( {
				provider: data.defaults?.text_provider || 'openai',
				text_model: data.defaults?.text_model || '',
				image_model: data.defaults?.image_model || '',
				format: 'square',
			} );
		} );
	}, [] );

	const update = ( key, value ) => setForm( { ...form, [ key ]: value } );

	return h(
		'div',
		null,
		h( PageHeader, {
			eyebrow: 'AI Studio',
			title: 'Generate reusable assets',
			body: 'Create captions, image prompts, images, and short-form video scripts without starting a scheduled post.',
		} ),
		h(
			'div',
			{ className: 'ssai-grid-two' },
			h(
				Panel,
				null,
				h(
					Field,
					{ label: 'Idea' },
					h( TextArea, {
						value: form.idea || '',
						onChange: ( event ) =>
							update( 'idea', event.target.value ),
					} )
				),
				h(
					'div',
					{ className: 'ssai-form-grid' },
					h(
						Field,
						{ label: 'Text provider' },
						h( Select, {
							value: form.provider,
							onChange: ( event ) =>
								setForm( {
									...form,
									provider: event.target.value,
									text_model: getDefaultTextModel(
										models,
										event.target.value
									),
								} ),
							options: defaultProviderOptions,
						} )
					),
					h(
						Field,
						{ label: 'Text model' },
						h( Select, {
							value: form.text_model || '',
							onChange: ( event ) =>
								update( 'text_model', event.target.value ),
							options: textModels,
						} )
					),
					h(
						Field,
						{ label: 'Image model' },
						h( Select, {
							value: form.image_model || '',
							onChange: ( event ) =>
								update( 'image_model', event.target.value ),
							options: imageModels,
						} )
					),
					h(
						Field,
						{ label: 'Image format' },
						h( Select, {
							value: form.format || 'square',
							onChange: ( event ) =>
								update( 'format', event.target.value ),
							options: imageFormats,
						} )
					)
				),
				h(
					'div',
					{ className: 'ssai-action-row' },
					h(
						Button,
						{
							variant: 'primary',
							icon: Send,
							onClick: async () =>
								setOutput(
									await run(
										() => api.generateCaption( form ),
										'Captions generated.'
									)
								),
						},
						'Captions'
					),
					h(
						Button,
						{
							icon: Sparkles,
							onClick: async () =>
								setOutput(
									await run(
										() => api.generateVideoScript( form ),
										'Video script generated.'
									)
								),
						},
						'Video Script'
					),
					h(
						Button,
						{
							icon: ImageIcon,
							onClick: async () =>
								setOutput(
									await run(
										() => api.generateImage( form ),
										'Image saved.'
									)
								),
						},
						'Image'
					)
				)
			),
			h(
				Panel,
				null,
				h( SectionHeader, {
					title: 'Output',
					body: 'Generated assets appear here and remain editable before publishing.',
				} ),
				h( ResultList, { result: output } )
			)
		)
	);
}

function BrandIntelligence( { run } ) {
	const { data, loading, reload } = useLoader( api.brandProfile );
	const [ candidates, setCandidates ] = useState( [] );
	const [ manual, setManual ] = useState( { source_type: 'manual' } );
	const [ uploadFile, setUploadFile ] = useState( null );
	const sources = data?.sources || [];
	const profile = data?.profile?.profile || {};

	const uploadSource = async () => {
		if ( ! uploadFile ) {
			throw new Error(
				'Choose a TXT, MD, CSV, or JSON brand file first.'
			);
		}
		await run(
			() =>
				api.uploadBrandSource(
					uploadFile,
					manual.title || uploadFile.name
				),
			'Brand file uploaded.'
		);
		setUploadFile( null );
		reload();
	};

	return h(
		'div',
		null,
		h( PageHeader, {
			eyebrow: 'Brand Intelligence',
			title: 'Approved sources only',
			body: 'Build a local profile from WordPress content, pasted examples, and uploaded files. AI analysis runs only when an admin clicks it.',
			actions: h(
				Button,
				{ icon: RefreshCw, onClick: reload },
				'Refresh'
			),
		} ),
		loading
			? h( SkeletonGrid )
			: h(
					'div',
					{ className: 'ssai-grid-two' },
					h(
						Panel,
						null,
						h( SectionHeader, {
							title: 'WordPress sources',
							body: 'Scan posts, pages, products, taxonomies, site settings, and image metadata.',
						} ),
						h(
							'div',
							{ className: 'ssai-inline' },
							h(
								Button,
								{
									icon: RefreshCw,
									onClick: async () =>
										setCandidates(
											await run(
												() => api.brandScan(),
												'Sources scanned.'
											)
										),
								},
								'Scan Site'
							)
						),
						h(
							'div',
							{ className: 'ssai-picker' },
							candidates.map( ( item ) =>
								h(
									'button',
									{
										key: `${ item.type }-${ item.id }`,
										type: 'button',
										onClick: async () => {
											await run(
												() =>
													api.addBrandSource( {
														source_type: item.type,
														source_id: item.id,
													} ),
												'Brand source added.'
											);
											reload();
										},
									},
									h( 'strong', null, item.title ),
									h( 'span', null, item.type ),
									h( 'p', null, item.excerpt || '' )
								)
							)
						)
					),
					h(
						Panel,
						null,
						h( SectionHeader, {
							title: 'Manual and file sources',
							body: 'Use approved brand examples, sales copy excerpts, FAQs, or positioning notes.',
						} ),
						h(
							Field,
							{ label: 'Title' },
							h( TextInput, {
								value: manual.title || '',
								onChange: ( event ) =>
									setManual( {
										...manual,
										title: event.target.value,
									} ),
							} )
						),
						h(
							Field,
							{ label: 'Pasted content' },
							h( TextArea, {
								value: manual.content || '',
								onChange: ( event ) =>
									setManual( {
										...manual,
										content: event.target.value,
									} ),
							} )
						),
						h(
							'div',
							{ className: 'ssai-action-row' },
							h(
								Button,
								{
									variant: 'primary',
									onClick: async () => {
										await run(
											() => api.addBrandSource( manual ),
											'Brand source added.'
										);
										setManual( { source_type: 'manual' } );
										reload();
									},
								},
								'Add Pasted Source'
							)
						),
						h(
							Field,
							{ label: 'Upload TXT, MD, CSV, or JSON' },
							h( TextInput, {
								type: 'file',
								accept: '.txt,.md,.csv,.json',
								onChange: ( event ) =>
									setUploadFile(
										event.target.files[ 0 ] || null
									),
							} )
						),
						h(
							Button,
							{ icon: UploadCloud, onClick: uploadSource },
							uploadFile
								? `Upload ${ uploadFile.name }`
								: 'Upload Brand File'
						)
					)
			  ),
		h(
			Panel,
			null,
			h( SectionHeader, {
				title: 'Approved sources',
				body: 'Only these excerpts are used for local or AI-assisted brand profiling.',
			} ),
			sources.length
				? h(
						'div',
						{ className: 'ssai-source-list' },
						sources.map( ( source ) =>
							h(
								'div',
								{ key: source.id },
								h( 'strong', null, source.title ),
								h( Badge, null, source.source_type ),
								h( 'p', null, source.excerpt ),
								h(
									Button,
									{
										onClick: async () => {
											await api.deleteBrandSource(
												source.id
											);
											reload();
										},
									},
									'Remove'
								)
							)
						)
				  )
				: h( EmptyState, {
						title: 'No approved sources yet',
						body: 'Scan WordPress content or add a manual source before building a profile.',
				  } ),
			h(
				'div',
				{ className: 'ssai-action-row' },
				h(
					Button,
					{
						onClick: async () => {
							await run(
								() => api.analyzeBrand( { use_ai: false } ),
								'Local Brand Intelligence built.'
							);
							reload();
						},
					},
					'Build Local Profile'
				),
				h(
					Button,
					{
						variant: 'primary',
						icon: Sparkles,
						onClick: async () => {
							await run(
								() => api.analyzeBrand( { use_ai: true } ),
								'AI Brand Intelligence built.'
							);
							reload();
						},
					},
					'Analyze With AI'
				)
			)
		),
		h(
			Panel,
			null,
			h( SectionHeader, {
				title: 'Current Brand Profile',
				body: 'Prompt context used for future generation when present.',
			} ),
			Object.keys( profile ).length
				? h( BrandProfile, { profile } )
				: h( EmptyState, {
						title: 'No profile built',
						body: 'Build a profile to make generated captions more specific to the site.',
				  } )
		)
	);
}

function BrandProfile( { profile } ) {
	return h(
		'div',
		{ className: 'ssai-profile-grid' },
		Object.keys( profile ).map( ( key ) =>
			h(
				'div',
				{ key },
				h( 'strong', null, key.replace( /_/g, ' ' ) ),
				h( 'p', null, stringifyValue( profile[ key ] ) )
			)
		)
	);
}

function CalendarView( { run } ) {
	const { data, loading, reload } = useLoader( api.jobs );
	const [ view, setView ] = useState( 'month' );
	const jobs = data || [];
	const filtered = filterCalendarJobs( jobs, view );

	return h(
		'div',
		null,
		h( PageHeader, {
			eyebrow: 'Calendar',
			title: 'Publishing queue',
			body: 'Switch views to inspect scheduled, failed, and published platform jobs.',
			actions: h(
				Button,
				{ icon: RefreshCw, onClick: reload },
				'Refresh'
			),
		} ),
		h(
			'div',
			{
				className: 'ssai-segmented',
				role: 'group',
				'aria-label': 'Calendar view',
			},
			[ 'month', 'week', 'list' ].map( ( item ) =>
				h(
					'button',
					{
						key: item,
						className: view === item ? 'is-active' : '',
						type: 'button',
						onClick: () => setView( item ),
					},
					item
				)
			)
		),
		loading
			? h( SkeletonGrid )
			: h(
					'div',
					{
						className:
							view === 'list'
								? 'ssai-list'
								: 'ssai-calendar-grid',
					},
					filtered.length
						? filtered.map( ( job ) =>
								h( CalendarJob, {
									key: job.id,
									job,
									onRetry: async () => {
										await run(
											() => api.retryJob( job.id ),
											'Retry queued.'
										);
										reload();
									},
								} )
						  )
						: h( EmptyState, {
								title: 'No jobs in this view',
								body: 'Scheduled and published jobs will appear here.',
						  } )
			  )
	);
}

function CalendarJob( { job, onRetry } ) {
	return h(
		'div',
		{ className: 'ssai-calendar-job' },
		h( 'span', null, formatDateTime( job.scheduled_at ) ),
		h( 'strong', null, job.title || 'Untitled post' ),
		h( 'small', null, `${ job.platform } - ${ job.platform_account_id }` ),
		h( Badge, { tone: statusTone( job.status ) }, job.status ),
		job.status === 'failed'
			? h( Button, { onClick: onRetry }, 'Retry' )
			: null,
		job.error_message ? h( 'p', null, job.error_message ) : null
	);
}

function Ideas( { run, setRoute } ) {
	const { data, loading, reload } = useLoader( api.ideas );
	const [ form, setForm ] = useState( {} );
	const ideas = data || [];

	return h(
		'div',
		null,
		h( PageHeader, {
			eyebrow: 'Ideas',
			title: 'Idea Bank',
			body: 'Capture raw ideas, AI leftovers, and campaign angles worth using later.',
			actions: h(
				Button,
				{ icon: RefreshCw, onClick: reload },
				'Refresh'
			),
		} ),
		h(
			'div',
			{ className: 'ssai-grid-two' },
			h(
				Panel,
				null,
				h( SectionHeader, {
					title: 'New idea',
					body: 'Keep small thoughts from disappearing before campaign day.',
				} ),
				h(
					Field,
					{ label: 'Title' },
					h( TextInput, {
						value: form.title || '',
						onChange: ( event ) =>
							setForm( { ...form, title: event.target.value } ),
					} )
				),
				h(
					Field,
					{ label: 'Idea' },
					h( TextArea, {
						value: form.idea_text || '',
						onChange: ( event ) =>
							setForm( {
								...form,
								idea_text: event.target.value,
							} ),
					} )
				),
				h(
					Button,
					{
						variant: 'primary',
						icon: Save,
						onClick: async () => {
							await run(
								() => api.createIdea( form ),
								'Idea saved.'
							);
							setForm( {} );
							reload();
						},
					},
					'Save Idea'
				)
			),
			h(
				Panel,
				null,
				h( SectionHeader, {
					title: 'Active ideas',
					body: 'Start a post from an existing angle.',
				} ),
				loading
					? h( SkeletonGrid )
					: h(
							'div',
							{ className: 'ssai-list' },
							ideas.length
								? ideas.map( ( idea ) =>
										h(
											'div',
											{
												className: 'ssai-idea-row',
												key: idea.id,
											},
											h(
												'strong',
												null,
												idea.title || 'Untitled idea'
											),
											h( 'p', null, idea.idea_text ),
											h(
												'div',
												{
													className:
														'ssai-action-row',
												},
												h( Badge, null, idea.status ),
												h(
													Button,
													{
														onClick: async () => {
															const created =
																await run(
																	() =>
																		api.createPostFromIdea(
																			idea.id
																		),
																	'Draft created from idea.'
																);
															setRoute(
																'create',
																{
																	postId: created?.id,
																}
															);
														},
													},
													'Create Draft'
												)
											)
										)
								  )
								: h( EmptyState, {
										title: 'No ideas yet',
										body: 'Save manual ideas or AI leftovers here.',
								  } )
					  )
			)
		)
	);
}

function Connections( { run } ) {
	const { data, loading, reload } = useLoader( api.connections );
	const [ form, setForm ] = useState( { platform: 'facebook' } );
	const [ testResult, setTestResult ] = useState( null );
	const rows = data || [];
	const update = ( key, value ) => setForm( { ...form, [ key ]: value } );

	const testConnection = async () => {
		const result = await run(
			() => api.testConnection( form ),
			'Meta connection test complete.'
		);
		setTestResult( result );
	};

	return h(
		'div',
		null,
		h( PageHeader, {
			eyebrow: 'Connections',
			title: 'Facebook and Instagram accounts',
			body: 'Manual Meta credentials stay encrypted server-side and are never returned to the dashboard.',
			actions: h(
				Button,
				{ icon: RefreshCw, onClick: reload },
				'Refresh'
			),
		} ),
		h(
			'div',
			{ className: 'ssai-grid-two' },
			h(
				Panel,
				null,
				h( SectionHeader, {
					title: 'Add connection',
					body: 'Use a Page token or Instagram professional account token with publishing permissions.',
				} ),
				h(
					Field,
					{ label: 'Platform' },
					h( Select, {
						value: form.platform,
						onChange: ( event ) =>
							update( 'platform', event.target.value ),
						options: [ 'facebook', 'instagram' ],
					} )
				),
				h(
					Field,
					{ label: 'Account label' },
					h( TextInput, {
						value: form.account_label || '',
						onChange: ( event ) =>
							update( 'account_label', event.target.value ),
					} )
				),
				h(
					Field,
					{ label: 'Page or Instagram account ID' },
					h( TextInput, {
						value: form.account_id || '',
						onChange: ( event ) =>
							update( 'account_id', event.target.value ),
					} )
				),
				h(
					Field,
					{ label: 'Access token' },
					h( TextInput, {
						type: 'password',
						value: form.access_token || '',
						onChange: ( event ) =>
							update( 'access_token', event.target.value ),
					} )
				),
				h(
					Field,
					{ label: 'Token expiry date' },
					h( TextInput, {
						type: 'datetime-local',
						value: form.token_expires_at || '',
						onChange: ( event ) =>
							update( 'token_expires_at', event.target.value ),
					} )
				),
				h(
					'div',
					{ className: 'ssai-action-row' },
					h(
						Button,
						{ icon: CheckCircle, onClick: testConnection },
						'Test Credentials'
					),
					h(
						Button,
						{
							variant: 'primary',
							icon: Save,
							onClick: async () => {
								await run(
									() => api.saveConnection( form ),
									'Connection saved.'
								);
								setForm( { platform: form.platform } );
								reload();
							},
						},
						'Save Connection'
					)
				),
				testResult
					? h(
							HelpText,
							{ tone: 'success' },
							testResult.message || 'Connection is reachable.'
					  )
					: null
			),
			h(
				Panel,
				null,
				h( SectionHeader, {
					title: 'Setup notes',
					body: 'Meta requires app permissions and public media URLs for Instagram publishing.',
				} ),
				h(
					'ol',
					{ className: 'ssai-steps' },
					h( 'li', null, 'Create or use a Meta developer app.' ),
					h(
						'li',
						null,
						'Generate a Page or Instagram token with publishing permissions.'
					),
					h( 'li', null, 'Save the account ID and token here.' ),
					h(
						'li',
						null,
						'Run a test before scheduling campaign content.'
					)
				)
			)
		),
		h(
			Panel,
			null,
			h( SectionHeader, {
				title: 'Connected accounts',
				body: 'Tokens are write-only in the dashboard.',
			} ),
			loading
				? h( SkeletonGrid )
				: h(
						'div',
						{ className: 'ssai-account-grid' },
						rows.length
							? rows.map( ( row ) =>
									h(
										'div',
										{
											key: row.id,
											className: 'ssai-connection-card',
										},
										h( 'strong', null, row.account_label ),
										h(
											'span',
											null,
											`${ row.platform } - ${ row.account_id }`
										),
										h(
											Badge,
											{ tone: statusTone( row.status ) },
											row.status
										),
										h(
											Button,
											{
												onClick: async () => {
													await run(
														() =>
															api.testConnection(
																{
																	connection_id:
																		row.id,
																}
															),
														'Saved connection is reachable.'
													);
												},
											},
											'Test'
										),
										h(
											Button,
											{
												onClick: async () => {
													await run(
														() =>
															api.deleteConnection(
																row.id
															),
														'Connection removed.'
													);
													reload();
												},
											},
											'Remove'
										)
									)
							  )
							: h( EmptyState, {
									title: 'No accounts connected',
									body: 'Add a Facebook Page or Instagram account to unlock publishing.',
							  } )
				  )
		)
	);
}

function SettingsPage( { run } ) {
	const { data, loading, reload } = useLoader( api.getSettings );
	const [ settings, setSettings ] = useState( null );
	const [ models, setModels ] = useState( null );

	useEffect( () => {
		api.models().then( setModels );
	}, [] );

	useEffect( () => {
		if ( data ) {
			setSettings( data );
		}
	}, [ data ] );

	if ( loading || ! settings ) {
		return h(
			'div',
			null,
			h( PageHeader, {
				eyebrow: 'Settings',
				title: 'Providers and defaults',
			} ),
			h( SkeletonGrid )
		);
	}

	const update = ( key, value ) =>
		setSettings( { ...settings, [ key ]: value } );
	const textProvider =
		settings.default_text_provider || settings.default_provider || 'openai';
	const textModels = getTextModelOptions( models, textProvider );
	const imageModels = getImageModelOptions( models );

	const testTextProvider = () =>
		run(
			() =>
				api.testProvider( {
					provider: textProvider,
					mode: 'text',
					model:
						settings.default_text_model ||
						settings.openai_text_model,
				} ),
			'Text provider check complete.'
		);
	const testImageProvider = () =>
		run(
			() =>
				api.testProvider( {
					provider: settings.default_image_provider || 'openai',
					mode: 'image',
					model:
						settings.default_image_model ||
						settings.openai_image_model,
				} ),
			'Image model check complete.'
		);

	return h(
		'div',
		null,
		h( PageHeader, {
			eyebrow: 'Settings',
			title: 'Providers and defaults',
			body: 'Secrets are write-only. Existing keys show configured status only.',
			actions: h(
				Button,
				{ icon: RefreshCw, onClick: reload },
				'Refresh'
			),
		} ),
		h(
			'div',
			{ className: 'ssai-grid-two' },
			h(
				Panel,
				null,
				h( SectionHeader, {
					title: 'Text generation',
					body: 'Choose the default provider and model for captions, scripts, repurposing, and Brand Intelligence.',
				} ),
				h(
					Field,
					{ label: 'Default text provider' },
					h( Select, {
						value: textProvider,
						onChange: ( event ) => {
							const provider = event.target.value;
							setSettings( {
								...settings,
								default_provider: provider,
								default_text_provider: provider,
								default_text_model: getDefaultTextModel(
									models,
									provider
								),
							} );
						},
						options: defaultProviderOptions,
					} )
				),
				h(
					Field,
					{ label: 'Default text model' },
					h( Select, {
						value: settings.default_text_model || '',
						onChange: ( event ) =>
							update( 'default_text_model', event.target.value ),
						options: textModels,
					} )
				),
				h(
					Field,
					{ label: 'OpenAI text model fallback' },
					h( TextInput, {
						value: settings.openai_text_model || '',
						onChange: ( event ) =>
							update( 'openai_text_model', event.target.value ),
					} )
				),
				h(
					Field,
					{ label: 'Gemini model' },
					h( TextInput, {
						value: settings.gemini_model || '',
						onChange: ( event ) =>
							update( 'gemini_model', event.target.value ),
					} )
				),
				h(
					Field,
					{ label: 'Claude model' },
					h( TextInput, {
						value: settings.claude_model || '',
						onChange: ( event ) =>
							update( 'claude_model', event.target.value ),
					} )
				),
				h(
					Button,
					{ icon: CheckCircle, onClick: testTextProvider },
					'Test Text Provider'
				)
			),
			h(
				Panel,
				null,
				h( SectionHeader, {
					title: 'Image generation',
					body: 'The free core uses OpenAI image generation and saves output to the WordPress Media Library.',
				} ),
				h(
					Field,
					{ label: 'Default image provider' },
					h( Select, {
						value: settings.default_image_provider || 'openai',
						onChange: ( event ) =>
							update(
								'default_image_provider',
								event.target.value
							),
						options: [ { value: 'openai', label: 'OpenAI' } ],
					} )
				),
				h(
					Field,
					{ label: 'Default image model' },
					h( Select, {
						value: settings.default_image_model || '',
						onChange: ( event ) =>
							update( 'default_image_model', event.target.value ),
						options: imageModels,
					} )
				),
				h(
					Field,
					{ label: 'OpenAI image model fallback' },
					h( TextInput, {
						value: settings.openai_image_model || '',
						onChange: ( event ) =>
							update( 'openai_image_model', event.target.value ),
					} )
				),
				h(
					Field,
					{ label: 'Default image size' },
					h( Select, {
						value: settings.openai_image_size || '1024x1024',
						onChange: ( event ) =>
							update( 'openai_image_size', event.target.value ),
						options: [
							{ value: '1024x1024', label: 'Square 1024x1024' },
							{ value: '1024x1536', label: 'Portrait 1024x1536' },
							{
								value: '1536x1024',
								label: 'Landscape 1536x1024',
							},
						],
					} )
				),
				h(
					Field,
					{ label: 'Default image quality' },
					h( Select, {
						value: settings.openai_image_quality || 'auto',
						onChange: ( event ) =>
							update(
								'openai_image_quality',
								event.target.value
							),
						options: [ 'auto', 'low', 'medium', 'high' ],
					} )
				),
				h(
					Button,
					{ icon: CheckCircle, onClick: testImageProvider },
					'Test Image Model'
				)
			),
			h(
				Panel,
				null,
				h( SectionHeader, {
					title: 'API keys',
					body: 'Leave a configured key blank to keep it. Enter __ssai_clear__ to remove it.',
				} ),
				[ 'openai_api_key', 'gemini_api_key', 'claude_api_key' ].map(
					( key ) =>
						h(
							Field,
							{
								key,
								label: `${ labelize( key ) }${
									settings[ `${ key }_configured` ]
										? ' configured'
										: ''
								}`,
							},
							h( TextInput, {
								type: 'password',
								value: settings[ key ] || '',
								placeholder: settings[ `${ key }_configured` ]
									? 'Leave blank to keep existing key'
									: '',
								onChange: ( event ) =>
									update( key, event.target.value ),
							} )
						)
				)
			),
			h(
				Panel,
				null,
				h( SectionHeader, {
					title: 'Brand defaults',
					body: 'These fields fill generation forms and local Brand Intelligence.',
				} ),
				[
					'business_name',
					'audience',
					'tone',
					'default_cta',
					'brand_words',
					'words_to_avoid',
				].map( ( key ) =>
					h(
						Field,
						{ key, label: labelize( key ) },
						h( TextArea, {
							rows: 3,
							value: settings[ key ] || '',
							onChange: ( event ) =>
								update( key, event.target.value ),
						} )
					)
				),
				h(
					'div',
					{ className: 'ssai-form-grid' },
					h(
						Field,
						{ label: 'Default posting time' },
						h( TextInput, {
							value: settings.default_posting_time || '',
							onChange: ( event ) =>
								update(
									'default_posting_time',
									event.target.value
								),
						} )
					),
					h(
						Field,
						{ label: 'Retry attempts' },
						h( TextInput, {
							type: 'number',
							min: 1,
							max: 10,
							value: settings.retry_attempts || 3,
							onChange: ( event ) =>
								update( 'retry_attempts', event.target.value ),
						} )
					)
				),
				h(
					'label',
					{ className: 'ssai-check-row' },
					h( 'input', {
						type: 'checkbox',
						checked: Boolean( settings.delete_data_on_uninstall ),
						onChange: ( event ) =>
							update(
								'delete_data_on_uninstall',
								event.target.checked
							),
					} ),
					h( 'span', null, 'Delete SociaSpark AI data on uninstall' )
				),
				h(
					'div',
					{ className: 'ssai-action-row' },
					h(
						Button,
						{
							variant: 'primary',
							icon: Save,
							onClick: async () => {
								const saved = await run(
									() => api.saveSettings( settings ),
									'Settings saved.'
								);
								setSettings( saved );
							},
						},
						'Save Settings'
					)
				)
			)
		)
	);
}

function Logs() {
	const { data, loading, reload } = useLoader( api.logs );
	return h(
		'div',
		null,
		h( PageHeader, {
			eyebrow: 'Activity',
			title: 'Operational log',
			body: 'Provider, scheduler, publishing, and Brand Intelligence events with secrets redacted.',
			actions: h(
				Button,
				{ icon: RefreshCw, onClick: reload },
				'Refresh'
			),
		} ),
		h(
			Panel,
			null,
			loading
				? h( SkeletonGrid )
				: h( ActivityList, { items: data || [] } )
		)
	);
}

function Roadmap() {
	const features = [
		'LinkedIn, X, Pinterest, TikTok, and YouTube Shorts add-ons',
		'Rendered video provider integrations',
		'Team approvals and agency workspaces',
		'Landing-page training with SSRF protection',
		'Best-time analytics and campaign generation',
	];

	return h(
		'div',
		null,
		h( PageHeader, {
			eyebrow: 'Roadmap',
			title: 'Add-on ready, free core intact',
			body: 'These are expansion areas for separate add-ons. Free Facebook and Instagram workflows remain usable without a license.',
		} ),
		h(
			'div',
			{ className: 'ssai-roadmap' },
			features.map( ( feature ) =>
				h(
					Panel,
					{ key: feature, className: 'ssai-roadmap-card' },
					h( Sparkles ),
					h( 'strong', null, feature ),
					h( Badge, null, 'Future add-on' )
				)
			)
		)
	);
}

function PostList( { posts = [], onOpen } ) {
	if ( ! posts.length ) {
		return h( EmptyState, {
			title: 'No posts yet',
			body: 'Create a draft or schedule your first post from the composer.',
		} );
	}

	return h(
		'div',
		{ className: 'ssai-post-list' },
		posts.map( ( post ) =>
			h(
				'div',
				{ key: post.id },
				h( 'strong', null, post.title || 'Untitled post' ),
				h( 'span', null, post.updated_at ),
				h( Badge, { tone: statusTone( post.status ) }, post.status ),
				post.jobs?.length
					? h(
							'small',
							null,
							`${ post.jobs.length } platform job(s)`
					  )
					: null,
				onOpen
					? h(
							Button,
							{ onClick: () => onOpen( post.id ) },
							post.status === 'draft' ? 'Open Draft' : 'Open'
					  )
					: null
			)
		)
	);
}

function JobList( { jobs = [], empty, onRetry } ) {
	if ( ! jobs.length ) {
		return h( EmptyState, {
			title: empty,
			body: 'Queue updates appear here after posts are scheduled.',
		} );
	}

	return h(
		'div',
		{ className: 'ssai-job-list' },
		jobs.map( ( job ) =>
			h(
				'div',
				{ key: job.id },
				h(
					'div',
					null,
					h( 'strong', null, job.title || 'Untitled post' ),
					h(
						'span',
						null,
						`${ job.platform } - ${ job.scheduled_at || 'now' }`
					)
				),
				h( Badge, { tone: statusTone( job.status ) }, job.status ),
				onRetry && job.status === 'failed'
					? h( Button, { onClick: () => onRetry( job.id ) }, 'Retry' )
					: null,
				job.error_message ? h( 'p', null, job.error_message ) : null
			)
		)
	);
}

function ActivityList( { items = [] } ) {
	if ( ! items.length ) {
		return h( EmptyState, {
			title: 'No activity yet',
			body: 'Publishing, generation, and scheduler messages will appear here.',
		} );
	}

	return h(
		'div',
		{ className: 'ssai-activity-list' },
		items.map( ( item ) => {
			const detail = [
				item.context?.provider,
				item.context?.model,
				item.context?.error_code,
				item.context?.status ? `HTTP ${ item.context.status }` : null,
			]
				.filter( Boolean )
				.join( ' | ' );
			const message = item.context?.message || item.message;

			return h(
				'div',
				{ key: item.id },
				h( Badge, { tone: statusTone( item.level ) }, item.level ),
				h( 'strong', null, item.event ),
				h( 'span', null, message ),
				detail ? h( 'small', null, detail ) : null,
				h( 'small', null, item.created_at )
			);
		} )
	);
}

function ResultList( { result } ) {
	const rows = useMemo( () => normalizeVariations( result ), [ result ] );
	if ( ! rows.length ) {
		return h( EmptyState, {
			title: 'No output yet',
			body: 'Generated captions, images, or scripts appear here.',
		} );
	}

	return h(
		'div',
		{ className: 'ssai-result-list' },
		rows.map( ( row, index ) =>
			h(
				'div',
				{
					className: 'ssai-result',
					key: `${ row.title || row.hook || 'result' }-${ index }`,
				},
				h(
					'strong',
					null,
					row.title || row.hook || row.id || `Output ${ index + 1 }`
				),
				h( 'pre', null, resultText( row ) )
			)
		)
	);
}

function PageHeader( { eyebrow, title, body, actions } ) {
	return h(
		'header',
		{ className: 'ssai-page-header' },
		h(
			'div',
			null,
			h( 'span', null, eyebrow ),
			h( 'h1', null, title ),
			body ? h( 'p', null, body ) : null
		),
		actions || null
	);
}

function StepHead( { step, title } ) {
	return h(
		'div',
		{ className: 'ssai-step-head' },
		h( Badge, { tone: 'brand' }, step ),
		h( 'h2', null, title )
	);
}

function SkeletonGrid() {
	return h(
		'div',
		{ className: 'ssai-skeleton-grid' },
		[ 0, 1, 2, 3 ].map( ( item ) => h( Skeleton, { key: item } ) )
	);
}

function useLoader( loader ) {
	const [ data, setData ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );
	const loaderRef = useRef( loader );

	useEffect( () => {
		loaderRef.current = loader;
	}, [ loader ] );

	const load = useCallback( () => {
		setLoading( true );
		setError( '' );
		return loaderRef
			.current()
			.then( setData )
			.catch( ( caught ) =>
				setError( caught?.message || 'Could not load data.' )
			)
			.finally( () => setLoading( false ) );
	}, [] );

	useEffect( () => {
		load();
	}, [ load ] );

	return { data, loading, error, reload: load };
}

function normalizeVariations( output ) {
	if ( ! output ) {
		return [];
	}
	if ( Array.isArray( output ) ) {
		return output;
	}
	if ( Array.isArray( output.options ) ) {
		return output.options;
	}
	if ( output.repurposed && typeof output.repurposed === 'object' ) {
		return [ output.repurposed ];
	}
	return [ output ];
}

function captionText( item ) {
	if ( item.facebook_caption ) {
		return item.facebook_caption;
	}
	if ( item.instagram_caption ) {
		return item.instagram_caption;
	}
	if ( item.caption ) {
		return item.caption;
	}
	return JSON.stringify( item );
}

function resultText( item ) {
	if ( item.caption ) {
		return item.caption;
	}
	if ( item.voiceover ) {
		return item.voiceover;
	}
	if ( item.url ) {
		return item.url;
	}
	if ( item.script ) {
		return stringifyValue( item.script );
	}
	return stringifyValue( item );
}

function stringifyValue( value ) {
	if ( Array.isArray( value ) ) {
		return value.join( ', ' );
	}
	if ( typeof value === 'object' && value !== null ) {
		return JSON.stringify( value, null, 2 );
	}
	return String( value || '' );
}

function getTextModelOptions( models, provider ) {
	if ( ! models?.text?.[ provider ] ) {
		return [];
	}
	return models.text[ provider ].map( ( model ) => ( {
		value: model.id,
		label: model.label,
	} ) );
}

function getImageModelOptions( models ) {
	if ( ! models?.image?.openai ) {
		return [];
	}
	return models.image.openai.map( ( model ) => ( {
		value: model.id,
		label: model.label,
	} ) );
}

function getDefaultTextModel( models, provider ) {
	const providerModels = models?.text?.[ provider ] || [];
	return providerModels[ 0 ]?.id || '';
}

function statusTone( status ) {
	if ( [ 'connected', 'published', 'success', 'info' ].includes( status ) ) {
		return 'success';
	}
	if ( [ 'failed', 'error', 'expired', 'revoked' ].includes( status ) ) {
		return 'error';
	}
	if (
		[ 'warning', 'scheduled', 'queued', 'publishing' ].includes( status )
	) {
		return 'warning';
	}
	return 'neutral';
}

function accountKey( connection ) {
	return `account_${ connection.platform }_${ connection.account_id }`;
}

function labelize( key ) {
	return key.replace( /_/g, ' ' );
}

function formatDateTime( value ) {
	if ( ! value ) {
		return 'Now';
	}
	return value;
}

function filterCalendarJobs( jobs, view ) {
	if ( view === 'list' ) {
		return jobs;
	}
	const now = new Date();
	const days = view === 'week' ? 7 : 35;
	const end = new Date( now.getTime() + days * 24 * 60 * 60 * 1000 );
	return jobs.filter( ( job ) => {
		const date = job.scheduled_at
			? new Date( job.scheduled_at.replace( ' ', 'T' ) )
			: now;
		return date <= end;
	} );
}

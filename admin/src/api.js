import apiFetch from '@wordpress/api-fetch';

const namespace = '/ssai/v1';

if ( window.ssaiAdmin?.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( window.ssaiAdmin.nonce ) );
}

export function request( path, method = 'GET', data = undefined ) {
	return apiFetch( {
		path: `${ namespace }${ path }`,
		method,
		data,
	} );
}

export async function upload( path, formData ) {
	const response = await window.fetch(
		`${ window.ssaiAdmin?.restUrl || namespace }${ path }`,
		{
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': window.ssaiAdmin?.nonce || '',
			},
			body: formData,
		}
	);
	const data = await response.json();
	if ( ! response.ok ) {
		const error = new Error( data?.message || 'Upload failed.' );
		error.data = data?.data || {};
		throw error;
	}
	return data;
}

export const api = {
	getSettings: () => request( '/settings' ),
	saveSettings: ( data ) => request( '/settings', 'POST', data ),
	dashboard: () => request( '/dashboard' ),
	models: () => request( '/ai/models' ),
	testProvider: ( data ) => request( '/ai/test-provider', 'POST', data ),
	connections: () => request( '/connections' ),
	saveConnection: ( data ) =>
		request( '/connections/meta/save', 'POST', data ),
	testConnection: ( data ) =>
		request( '/connections/meta/test', 'POST', data ),
	deleteConnection: ( id ) => request( `/connections/${ id }`, 'DELETE' ),
	ideas: () => request( '/ideas' ),
	createIdea: ( data ) => request( '/ideas', 'POST', data ),
	createPostFromIdea: ( id ) =>
		request( `/ideas/${ id }/create-post`, 'POST', {} ),
	posts: () => request( '/posts' ),
	getPost: ( id ) => request( `/posts/${ id }` ),
	createPost: ( data ) => request( '/posts', 'POST', data ),
	updatePost: ( id, data ) => request( `/posts/${ id }`, 'PUT', data ),
	generateCaption: ( data ) =>
		request( '/ai/generate-caption', 'POST', data ),
	generateImage: ( data ) => request( '/ai/generate-image', 'POST', data ),
	generateVideoScript: ( data ) =>
		request( '/ai/generate-video-script', 'POST', data ),
	repurpose: ( data ) => request( '/ai/repurpose-wp-post', 'POST', data ),
	searchWp: ( q ) =>
		request( `/wp-content/search?q=${ encodeURIComponent( q ) }` ),
	saveGenerated: ( data ) => request( '/media/save-generated', 'POST', data ),
	calendar: () => request( '/calendar' ),
	jobs: () => request( '/jobs' ),
	schedule: ( data ) => request( '/schedule', 'POST', data ),
	publishNow: ( data ) => request( '/publish-now', 'POST', data ),
	retryJob: ( jobId ) => request( '/jobs/retry', 'POST', { job_id: jobId } ),
	logs: () => request( '/logs' ),
	brandProfile: () => request( '/brand/profile' ),
	brandScan: ( search = '' ) =>
		request( '/brand/sources/scan', 'POST', { search } ),
	addBrandSource: ( data ) => request( '/brand/sources', 'POST', data ),
	deleteBrandSource: ( id ) => request( `/brand/sources/${ id }`, 'DELETE' ),
	analyzeBrand: ( data ) => request( '/brand/analyze', 'POST', data ),
	uploadBrandSource: ( file, title = '' ) => {
		const formData = new window.FormData();
		formData.append( 'file', file );
		formData.append( 'title', title );
		return upload( '/brand/sources/upload', formData );
	},
};

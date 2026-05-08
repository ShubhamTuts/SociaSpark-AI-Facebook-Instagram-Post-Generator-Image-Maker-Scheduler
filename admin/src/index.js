import './styles/admin.css';
import { createElement, render } from '@wordpress/element';
import App from './App';

const root = document.getElementById( 'ssai-admin-root' );

if ( root ) {
	render( createElement( App ), root );
}

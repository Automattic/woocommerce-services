/**
 * External dependencies
 */
import 'whatwg-fetch';

// Keep native fetch and its window receiver; whatwg-fetch only fills a missing API.
export default ( ...args ) => window.fetch( ...args );

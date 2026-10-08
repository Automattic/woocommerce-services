import type { Page } from '@playwright/test';

// Returns true only for a logged-in wp-admin view. Any evaluate error (e.g. the
// context closing mid-check) resolves to false; every caller treats false as
// "not authenticated" and fails closed (re-login or a failed assertion), so
// swallowing the error here cannot turn a real failure into a false green.
export const hasAuthenticatedAdminSession = async ( page: Page ) => {
	return page
		.evaluate( () => {
			if ( window.location.pathname.includes( 'wp-login.php' ) ) {
				return false;
			}

			if ( document.body?.classList.contains( 'wp-admin' ) ) {
				return true;
			}

			return Boolean(
				document.querySelector( '#wpadminbar' ) ??
					document.querySelector( '#adminmenuwrap' ) ??
					document.querySelector( '#adminmenu' )
			);
		} )
		.catch( () => false );
};

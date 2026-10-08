import { expect, type Page } from '@playwright/test';

// Text signatures WordPress/PHP print on a fatal (or fatal-class) error. Kept
// narrow on purpose: this flags genuine breakage (white screen of death, uncaught
// exceptions) rather than benign deprecation/notice output.
//
// Scope note: wp-env and QIT run with WP_DEBUG_DISPLAY off, so PHP's own "Fatal
// error: ..." text is NOT printed to the page - only WordPress's rendered
// critical-error page is. The English strings below are therefore a best-effort
// catch on top of the locale-independent DOM check in expectNoPhpErrors (the
// wp_die() error container), which is what actually holds on a non-English site.
// Non-fatal notices/warnings land in wp-content/debug.log, not the page, and are
// out of scope for this assertion (name your smoke test "no fatal PHP errors").
const PHP_ERROR_SIGNATURES = [
	'There has been a critical error on this website',
	'Fatal error',
	'Parse error',
	'Uncaught Error',
	'Uncaught TypeError',
];

/**
 * Assert the rendered page carries no fatal-class PHP error output.
 *
 * @param page Playwright page to inspect.
 */
export const expectNoPhpErrors = async ( page: Page ) => {
	const body = await page.locator( 'body' ).innerText();

	// A fatal that returns a blank-body HTTP 500 (display_errors off) matches none
	// of the signatures; assert the page actually rendered so a dead page cannot
	// read green through this helper.
	expect(
		body.trim().length,
		`Expected a rendered page body at ${ page.url() }`
	).toBeGreaterThan( 0 );

	// WordPress's wp_die() critical-error page uses a locale-independent
	// `#error-page` container (front-end WSOD) / `.wp-die-message` markup, so
	// detecting it catches a fatal even when the copy is translated.
	await expect(
		page.locator( '#error-page, .wp-die-message' ),
		`Expected no wp_die() error page at ${ page.url() }`
	).toHaveCount( 0 );

	for ( const signature of PHP_ERROR_SIGNATURES ) {
		expect(
			body,
			`Expected no PHP error on ${ page.url() }, found "${ signature }"`
		).not.toContain( signature );
	}
};

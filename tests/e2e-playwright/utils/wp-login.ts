import type { Page } from '@playwright/test';

interface LoginOptions {
	username?: string;
	password?: string;
}

export const loginToWpAdmin = async (
	page: Page,
	options: LoginOptions = {}
) => {
	// QIT seeds the admin as QIT_WP_USERNAME / QIT_WP_PASSWORD (see the qit-cli
	// EnvironmentVars mapping); prefer those in CI before the local WP_ADMIN_*
	// overrides so a changed QIT admin cannot silently break the fallback login.
	const username =
		options.username ??
		process.env.QIT_WP_USERNAME ??
		process.env.WP_ADMIN_USER ??
		'admin';
	const password =
		options.password ??
		process.env.QIT_WP_PASSWORD ??
		process.env.WP_ADMIN_PASSWORD ??
		'password';

	// This helper only runs from the auth setup after the stored session has been
	// cleared, so the visitor is always logged out and wp-login.php renders the
	// form. (WordPress keeps a logged-out visitor on wp-login.php; the redirect
	// off it below is what confirms a successful login.)
	await page.goto( 'wp-login.php' );

	const userInput = page.locator( '#user_login' );
	const passwordInput = page.locator( '#user_pass' );
	const submitButton = page.locator( '#wp-submit' );

	if (
		( await userInput.count() ) === 0 ||
		( await passwordInput.count() ) === 0 ||
		( await submitButton.count() ) === 0
	) {
		throw new Error(
			'Could not find WordPress login form fields. Ensure BASE_URL points to a WordPress site.'
		);
	}

	await userInput.fill( username );
	await passwordInput.fill( password );
	await submitButton.click();

	// Login succeeds once WordPress redirects off wp-login.php to an admin page.
	// Wait on 'commit' (the redirect response) rather than the default 'load':
	// the post-login landing page is a full WooCommerce admin dashboard whose
	// 'load' event (all analytics/React assets) can exceed the timeout under
	// video/trace capture, even though the session cookies are already set at
	// commit time. The stored session only needs those cookies.
	await page.waitForURL( /wp-admin|post\.php|admin\.php/, {
		timeout: 30_000,
		waitUntil: 'commit',
	} );
};

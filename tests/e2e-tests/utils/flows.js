const baseUrl = process.env.WP_BASE_URL;
const adminUserName = process.env.WP_ADMIN_USER_NAME;
const adminUserPassword = process.env.WP_ADMIN_USER_PW;

const WP_ADMIN_PLUGINS_PAGE = baseUrl + '/wp-admin/plugins.php';
const WP_ADMIN_LOGIN = baseUrl + '/wp-login.php';

const StoreOwnerFlow = {
    login: async () => {
        await page.goto( WP_ADMIN_LOGIN, {
			waitUntil: 'networkidle0',
		} );

		await expect( page.title() ).resolves.toMatch( 'Log In' );

		await page.type( '#user_login', adminUserName );
		await page.type( '#user_pass', adminUserPassword );

		await Promise.all( [
			page.click( 'input[type=submit]' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );
	},

	logout: async () => {
		await page.goto( baseUrl + 'wp-login.php?action=logout', {
			waitUntil: 'networkidle0',
		} );

		await expect( page ).toMatch( 'You are attempting to log out' );

		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			page.click( 'a' ),
		] );
	},

	openPluginsPage: async () => {
		await page.goto( WP_ADMIN_PLUGINS_PAGE, {
			waitUntil: 'networkidle0',
		} );
	},
}

module.exports = {
	StoreOwnerFlow
};

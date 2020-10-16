module.exports = require( './wp-calypso/.eslintrc' );
const { esLintConfig: baseConfig } = require( '@woocommerce/e2e-environment' );

module.exports.env.jest = true;
Object.assign( module.exports.globals, {
	...baseConfig,
    page: true,
    browser: true,
    context: true,
    jestPuppeteer: true,
    process: true,
    console: true,
    document: true,
    localStorage: true,
    window: true,
    setTimeout: true,
    alert: true,
    location: true,
    fetch: true,
    URL: true,
    atob: true,
    Blob: true,
    Response: true,
    global: true,
});

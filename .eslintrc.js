const calypsoLintConfig = require( './wp-calypso/.eslintrc' );
const { useE2EEsLintConfig } = require( '@woocommerce/e2e-environment' );

calypsoLintConfig.env.jest = true;
Object.assign( calypsoLintConfig.globals, {
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

module.exports = useE2EEsLintConfig( calypsoLintConfig );

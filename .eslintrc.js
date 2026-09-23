const calypsoLintConfig = require( './tasks/eslint/calypso.eslintrc.js' );

calypsoLintConfig.env.jest = true;
// Previously contributed by @woocommerce/e2e-environment's useE2EEsLintConfig wrapper.
// The rest of what that wrapper merged in - plugin:jest/recommended, the jest plugin and
// the puppeteer globals - is already present below or in the Calypso base config.
calypsoLintConfig.env[ 'jest/globals' ] = true;
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

// The store notices bundle is rendered by the React that WordPress provides, inside the
// Cart/Checkout blocks. React 19 rejects elements created by an older React runtime, so
// importing the plugin's bundled React here would break the block cart and checkout.
// Everything in this tree must build its elements with window.wp.element instead.
calypsoLintConfig.overrides = [
    ...( calypsoLintConfig.overrides || [] ),
    {
        files: [ 'client/store-notices.js', 'client/components/store/notices/*.js' ],
        rules: {
            'no-restricted-imports': [
                'error',
                {
                    paths: [
                        {
                            name: 'react',
                            message:
                                'This tree is rendered by the host React: build elements with window.wp.element instead. See client/store-notices.js.',
                        },
                    ],
                },
            ],
        },
    },
];

module.exports = calypsoLintConfig;

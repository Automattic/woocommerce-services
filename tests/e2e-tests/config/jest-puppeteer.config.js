/** @format */

module.exports = {
    launch: {
        // Required for the logged out and logged in tests so they don't share app state/token.
        browserContext: 'incognito',
        ignoreHTTPSErrors: true,
        // dumpio: true,
        args: [
            '--remote-debugging-port=9222',
            // '--ignore-certificate-errors',
            // '--ignore-certificate-errors-spki-list',
        ]
    }
};

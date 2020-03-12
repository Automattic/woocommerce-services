module.exports = require( './wp-calypso/.eslintrc' );

module.exports.env.jest = true;
Object.assign( module.exports.globals, {
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

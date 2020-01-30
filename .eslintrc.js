module.exports = require( './wp-calypso/.eslintrc' );

module.exports.env = { jest: true };
module.exports.globals = {
    page: true,
    browser: true,
    context: true,
    jestPuppeteer: true,
};

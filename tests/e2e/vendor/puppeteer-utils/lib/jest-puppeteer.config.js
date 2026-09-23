"use strict";

/**
 * For a detailed explanation of configuration properties, visit:
 * https://github.com/GoogleChrome/puppeteer/blob/master/docs/api.md#puppeteerlaunchoptions
 */

const {
  CI,
  E2E_DEBUG,
  PUPPETEER_HEADLESS,
  PUPPETEER_SLOWMO,
  E2E_EXE_PATH
} = process.env;
let executablePath = '';
let dumpio = false;
if (!CI && E2E_EXE_PATH !== '') {
  executablePath = E2E_EXE_PATH;
}
if (E2E_DEBUG) {
  dumpio = true;
}
module.exports = {
  launch: {
    headless: PUPPETEER_HEADLESS == 'false' ? false : true,
    devtools: PUPPETEER_HEADLESS == 'false' ? true : false,
    slowMo: parseInt(PUPPETEER_SLOWMO, 10) || 0,
    executablePath,
    dumpio,
    ignoreHTTPSErrors: true,
    args: ['--window-size=1920,1080', '--user-agent=puppeteer-debug'],
    // Required for the logged out and logged in tests so they don't share app state/token.
    browserContext: 'incognito'
  },
  /**
   * Switched to false to make tests work with WP.com due to Chromium error:
   * [0813/201724.630944:INFO:CONSOLE(1)] "Uncaught SyntaxError: Unexpected identifier", source: https://sp.analytics.yahoo.com/sp.pl?a=10000&jsonp=YAHOO.ywa.I13N.handleJSONResponse&b=WordPress.com%3A%20Create%20a%20Free%20Website%20or%20Blog&.yp=10014088&f=https%3A%2F%2Fwordpress.com%2F&enc=UTF-8 (1)
   * TODO: maybe switch back to true in a while
   */
  exitOnPageError: false
};
"use strict";

var _lodash = require("lodash");
var _slack = require("./reporters/slack");
var _screenshot = require("./reporters/screenshot");
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Override the test case method so we can take screenshots of assertion failures.
 *
 * See: https://github.com/smooth-code/jest-puppeteer/issues/131#issuecomment-469439666
 */
let currentBlock;
const {
  CI,
  E2E_DEBUG
} = process.env;

// Use wrap to preserve all previous `wrap`s
jasmine.getEnv().describe = (0, _lodash.wrap)(jasmine.getEnv().describe, (func, ...args) => {
  try {
    currentBlock = args[0];
    func(...args);
  } catch (e) {
    throw e;
  }
});
global.it = async (name, func) => {
  return await test(name, async () => {
    try {
      await func();
    } catch (error) {
      // If running tests in CI
      if (CI) {
        const filePath = await (0, _screenshot.takeScreenshot)(currentBlock, name);
        await (0, _slack.sendFailedTestMessageToSlack)({
          block: currentBlock,
          name,
          error
        });
        await (0, _slack.sendFailedTestScreenshotToSlack)(filePath);
      }
      if (E2E_DEBUG) {
        console.log(error);
        await jestPuppeteer.debug();
      }
      throw error;
    }
  });
};
jasmine.getEnv().addReporter({
  specStarted(result) {
    console.log(`Spec name: ${result.fullName}, description: ${result.description}`);
  }
});
jasmine.getEnv().addReporter({
  specStarted: result => jasmine.currentTest = result,
  specDone: result => jasmine.currentTest = result
});
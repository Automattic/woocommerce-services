"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
Object.defineProperty(exports, "jestPuppeteerConfig", {
  enumerable: true,
  get: function () {
    return _jestPuppeteer.default;
  }
});
exports.useJestPuppeteerConfig = void 0;
var _jestPuppeteer = _interopRequireDefault(require("./jest-puppeteer.config"));
function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
/**
 * Internal dependencies
 */

const useJestPuppeteerConfig = function () {
  process.env.JEST_PUPPETEER_CONFIG = `${__dirname}/jest-puppeteer.config.js`;
};
exports.useJestPuppeteerConfig = useJestPuppeteerConfig;
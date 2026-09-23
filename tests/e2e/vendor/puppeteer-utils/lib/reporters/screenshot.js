"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.takeScreenshot = takeScreenshot;
var _path = _interopRequireDefault(require("path"));
var _mkdirp = _interopRequireDefault(require("mkdirp"));
function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
/**
 * External dependencies
 */

const screenshotsPath = _path.default.resolve(__dirname, '../../reports/screenshots');
const toFilename = s => s.replace(/[^a-z0-9.-]+/gi, '-');
async function takeScreenshot(currentBlock, name) {
  const fileName = toFilename(`${new Date().toISOString()}-${currentBlock}-${name}.png`);
  const filePath = _path.default.join(screenshotsPath, fileName);
  _mkdirp.default.sync(screenshotsPath);
  await page.screenshot({
    path: filePath,
    fullPage: true
  });
  return filePath;
}
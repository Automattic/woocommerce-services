import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Function that waits until the page viewport has the required dimensions.
 * It is being used to address a problem where after using setViewport the execution may continue,
 * without the new dimensions being applied.
 * https://github.com/GoogleChrome/puppeteer/issues/1751
 *
 * @param {number} width  Width of the window.
 * @param {number} height Height of the window.
 */
export function waitForWindowDimensions(_x, _x2) {
  return _waitForWindowDimensions.apply(this, arguments);
}

function _waitForWindowDimensions() {
  _waitForWindowDimensions = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(width, height) {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return page.mainFrame().waitForFunction("window.innerWidth === ".concat(width, " && window.innerHeight === ").concat(height));

          case 2:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _waitForWindowDimensions.apply(this, arguments);
}
//# sourceMappingURL=wait-for-window-dimensions.js.map
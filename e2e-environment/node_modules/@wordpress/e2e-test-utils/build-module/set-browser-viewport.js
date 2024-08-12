import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Internal dependencies
 */
import { waitForWindowDimensions } from './wait-for-window-dimensions';
/**
 * Named viewport options.
 *
 * @typedef {"large"|"medium"|"small"} WPDimensionsName
 */

/**
 * Viewport dimensions object.
 *
 * @typedef {Object} WPViewportDimensions
 *
 * @property {number} width  Width, in pixels.
 * @property {number} height Height, in pixels.
 */

/**
 * Predefined viewport dimensions to reference by name.
 *
 * @enum {WPViewportDimensions}
 *
 * @type {Object<WPDimensionsName,WPViewportDimensions>}
 */

var PREDEFINED_DIMENSIONS = {
  large: {
    width: 960,
    height: 700
  },
  medium: {
    width: 768,
    height: 700
  },
  small: {
    width: 600,
    height: 700
  }
};
/**
 * Valid argument argument type from which to derive viewport dimensions.
 *
 * @typedef {WPDimensionsName|WPViewportDimensions} WPViewport
 */

/**
 * Sets browser viewport to specified type.
 *
 * @param {WPViewport} viewport Viewport name or dimensions object to assign.
 */

export function setBrowserViewport(_x) {
  return _setBrowserViewport.apply(this, arguments);
}

function _setBrowserViewport() {
  _setBrowserViewport = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee(viewport) {
    var dimensions;
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            dimensions = typeof viewport === 'string' ? PREDEFINED_DIMENSIONS[viewport] : viewport;
            _context.next = 3;
            return page.setViewport(dimensions);

          case 3:
            _context.next = 5;
            return waitForWindowDimensions(dimensions.width, dimensions.height);

          case 5:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _setBrowserViewport.apply(this, arguments);
}
//# sourceMappingURL=set-browser-viewport.js.map
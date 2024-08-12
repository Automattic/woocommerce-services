import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Regular expression matching a displayed PHP error within a markup string.
 *
 * @see https://github.com/php/php-src/blob/598175e/main/main.c#L1257-L1297
 *
 * @type {RegExp}
 */
var REGEXP_PHP_ERROR = /(<b>)?(Fatal error|Recoverable fatal error|Warning|Parse error|Notice|Strict Standards|Deprecated|Unknown error)(<\/b>)?: (.*?) in (.*?) on line (<b>)?\d+(<\/b>)?/;
/**
 * Returns a promise resolving to one of either a string or null. A string will
 * be resolved if an error message is present in the contents of the page. If no
 * error is present, a null value will be resolved instead. This requires the
 * environment be configured to display errors.
 *
 * @see http://php.net/manual/en/function.error-reporting.php
 *
 * @return {Promise<?string>} Promise resolving to a string or null, depending
 *                            whether a page error is present.
 */

export function getPageError() {
  return _getPageError.apply(this, arguments);
}

function _getPageError() {
  _getPageError = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee() {
    var content, match;
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return page.content();

          case 2:
            content = _context.sent;
            match = content.match(REGEXP_PHP_ERROR);
            return _context.abrupt("return", match ? match[0] : null);

          case 5:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _getPageError.apply(this, arguments);
}
//# sourceMappingURL=get-page-error.js.map
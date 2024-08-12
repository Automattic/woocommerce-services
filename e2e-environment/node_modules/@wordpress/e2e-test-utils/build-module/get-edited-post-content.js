import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Internal dependencies
 */
import { wpDataSelect } from './wp-data-select';
/**
 * Returns a promise which resolves with the edited post content (HTML string).
 *
 * @return {Promise} Promise resolving with post content markup.
 */

export function getEditedPostContent() {
  return _getEditedPostContent.apply(this, arguments);
}

function _getEditedPostContent() {
  _getEditedPostContent = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee() {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            return _context.abrupt("return", wpDataSelect('core/editor', 'getEditedPostContent'));

          case 1:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _getEditedPostContent.apply(this, arguments);
}
//# sourceMappingURL=get-edited-post-content.js.map
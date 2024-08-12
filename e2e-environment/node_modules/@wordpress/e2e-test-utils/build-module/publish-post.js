import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Internal dependencies
 */
import { openPublishPanel } from './open-publish-panel';
/**
 * Publishes the post, resolving once the request is complete (once a notice
 * is displayed).
 *
 * @return {Promise} Promise resolving when publish is complete.
 */

export function publishPost() {
  return _publishPost.apply(this, arguments);
}

function _publishPost() {
  _publishPost = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee() {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return openPublishPanel();

          case 2:
            _context.next = 4;
            return page.click('.editor-post-publish-button');

          case 4:
            return _context.abrupt("return", page.waitForSelector('.components-snackbar'));

          case 5:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _publishPost.apply(this, arguments);
}
//# sourceMappingURL=publish-post.js.map
import _regeneratorRuntime from "@babel/runtime/regenerator";
import _asyncToGenerator from "@babel/runtime/helpers/esm/asyncToGenerator";

/**
 * Publishes the post without the pre-publish checks,
 * resolving once the request is complete (once a notice is displayed).
 *
 * @return {Promise} Promise resolving when publish is complete.
 */
export function publishPostWithPrePublishChecksDisabled() {
  return _publishPostWithPrePublishChecksDisabled.apply(this, arguments);
}

function _publishPostWithPrePublishChecksDisabled() {
  _publishPostWithPrePublishChecksDisabled = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime.mark(function _callee() {
    return _regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return page.click('.editor-post-publish-button');

          case 2:
            return _context.abrupt("return", page.waitForSelector('.components-snackbar'));

          case 3:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return _publishPostWithPrePublishChecksDisabled.apply(this, arguments);
}
//# sourceMappingURL=publish-post-with-pre-publish-checks-disabled.js.map